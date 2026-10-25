<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use App\Models\UserAttendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminAttendanceController extends Controller
{
    /**
     * Calculate Haversine distance between two coordinates in meters.
     */
    public static function calculateDistanceMeters($lat1, $lon1, $lat2, $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }
        $lat1 = (float)$lat1;
        $lon1 = (float)$lon1;
        $lat2 = (float)$lat2;
        $lon2 = (float)$lon2;

        $earthRadius = 6371000; // Earth's radius in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }

    /**
     * Get or initialize the active attendance location configuration.
     */
    public function getLocationSetting(): JsonResponse
    {
        $setting = AttendanceSetting::first();

        if (!$setting) {
            // Default initial policy: Sangli Central Election Command HQ
            $setting = AttendanceSetting::create([
                'is_location_required'   => false, // Flexible by default until admin activates
                'allow_anywhere'          => true,
                'location_name'           => 'सांगली मध्यवर्ती निवडणूक नियंत्रण कक्ष (Sangli Central HQ)',
                'address'                 => 'स्टेशन रोड, विश्रामबाग जवळ, सांगली, महाराष्ट्र ४१६४१६',
                'latitude'                => 16.8524000,
                'longitude'               => 74.5815000,
                'radius_meters'           => 500, // 500 meters radius
                'enforce_strict_geofence' => true,
                'updated_by_name'         => 'System Admin',
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $setting,
        ]);
    }

    /**
     * Update admin designated attendance location and geofence enforcement.
     * "here give option for the admin for set location , if admin set any location then all neet to go on this place & if not then they can able to from anywhere"
     */
    public function updateLocationSetting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_location_required'   => 'required|boolean',
            'location_name'           => 'nullable|string|max:255',
            'address'                 => 'nullable|string|max:1000',
            'latitude'                => 'nullable|numeric',
            'longitude'               => 'nullable|numeric',
            'radius_meters'           => 'nullable|integer|min:20|max:50000',
            'allow_anywhere'          => 'nullable|boolean',
            'enforce_strict_geofence' => 'nullable|boolean',
        ]);

        $setting = AttendanceSetting::first();
        if (!$setting) {
            $setting = new AttendanceSetting();
        }

        $isRequired = (bool)$request->input('is_location_required', false);
        $setting->is_location_required   = $isRequired;
        $setting->allow_anywhere          = !$isRequired || (bool)$request->input('allow_anywhere', false);
        $setting->location_name           = $request->input('location_name', 'सांगली मध्यवर्ती कार्यालय');
        $setting->address                 = $request->input('address', 'सांगली, महाराष्ट्र');
        $setting->latitude                = $request->filled('latitude') ? (float)$request->input('latitude') : null;
        $setting->longitude               = $request->filled('longitude') ? (float)$request->input('longitude') : null;
        $setting->radius_meters           = (int)$request->input('radius_meters', 500);
        $setting->enforce_strict_geofence = (bool)$request->input('enforce_strict_geofence', true);

        $currentUser = $request->user();
        $setting->updated_by_name = $currentUser ? ($currentUser->full_name ?? 'Admin') : 'Admin Command HQ';
        $setting->save();

        // Log audit event if table exists
        try {
            DB::table('admin_audit_logs')->insert([
                'user_id'       => $currentUser ? $currentUser->id : 1,
                'user_name'     => $setting->updated_by_name,
                'action_type'   => 'ATTENDANCE_LOCATION_POLICY_UPDATED',
                'target_entity' => 'attendance_settings',
                'details'       => 'उपस्थिती स्थान धोरण बदलले: ' . ($isRequired ? "अनिवार्य स्थान [{$setting->location_name}]" : "सर्व ठिकाणांहून परवानगी"),
                'ip_address'    => $request->ip() ?? '127.0.0.1',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } catch (\Exception $e) {
            // Ignore audit table failure if schema differs
        }

        return response()->json([
            'success' => true,
            'message' => $isRequired
                ? "उपस्थिती स्थान यशस्वीरीत्या सेट केले! आता सर्व कर्मचाऱ्यांना ठरवलेल्या ठिकाणीच उपस्थिती नोंदवावी लागेल. (Designated location set: All users must check in at this place)."
                : "मुक्त उपस्थिती धोरण सुरू केले: वापरकर्ते कोणत्याही ठिकाणाहून उपस्थिती नोंदवू शकतात. (Location restriction removed: Users can attend from anywhere).",
            'data'    => $setting,
        ]);
    }

    /**
     * Get attendance records of all users with full address, geo-coordinates, photos, and compliance.
     * Admin Command HQ can check attendance of all users.
     */
    public function getAttendance(Request $request): JsonResponse
    {
        $dateFilter = $request->input('date'); // 'today', YYYY-MM-DD, or 'all'
        $search = trim((string)$request->input('search', ''));
        $role = $request->input('role'); // WORKER, OFFICER, ADMIN, or null
        $status = $request->input('status'); // Present, Punched In, Completed, Absent
        $compliance = $request->input('compliance'); // AT_REQUIRED_LOCATION, ANYWHERE_ALLOWED, OUTSIDE_RADIUS

        $todayStr = now()->toDateString();
        $targetDate = ($dateFilter && $dateFilter !== 'all' && $dateFilter !== 'today') ? $dateFilter : $todayStr;

        // Current location policy
        $locationSetting = AttendanceSetting::first();
        if (!$locationSetting) {
            $locationSetting = AttendanceSetting::create([
                'is_location_required'   => false,
                'allow_anywhere'          => true,
                'location_name'           => 'सांगली मध्यवर्ती कार्यालय',
                'address'                 => 'विश्रामबाग, सांगली, महाराष्ट्र',
                'latitude'                => 16.8524000,
                'longitude'               => 74.5815000,
                'radius_meters'           => 500,
                'enforce_strict_geofence' => true,
                'updated_by_name'         => 'System Admin',
            ]);
        }

        // Query attendances
        $query = DB::table('user_attendances as ua')
            ->leftJoin('users as u', 'ua.user_id', '=', 'u.id')
            ->select(
                'ua.id',
                'ua.user_id',
                DB::raw('COALESCE(ua.user_name, u.full_name) as user_name'),
                DB::raw('COALESCE(ua.user_role, (SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = u.id LIMIT 1), "WORKER") as user_role'),
                'u.phone as user_phone',
                'u.email as user_email',
                'u.profile_photo as user_photo',
                'ua.date',
                'ua.punch_in_time',
                'ua.punch_out_time',
                'ua.status',
                'ua.punch_in_photo',
                'ua.punch_out_photo',
                'ua.latitude',
                'ua.longitude',
                'ua.address',
                'ua.punch_out_latitude',
                'ua.punch_out_longitude',
                'ua.punch_out_address',
                'ua.location_name',
                'ua.distance_meters',
                'ua.compliance_status',
                'ua.device_info',
                'ua.notes',
                'ua.created_at',
                'ua.updated_at'
            );

        if ($dateFilter !== 'all') {
            $query->whereDate('ua.date', $targetDate);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('u.full_name', 'like', "%{$search}%")
                    ->orWhere('ua.user_name', 'like', "%{$search}%")
                    ->orWhere('u.phone', 'like', "%{$search}%")
                    ->orWhere('ua.address', 'like', "%{$search}%")
                    ->orWhere('ua.location_name', 'like', "%{$search}%");
            });
        }

        if (!empty($role)) {
            $query->where(function ($q) use ($role) {
                $q->where('ua.user_role', $role)
                    ->orWhereExists(function ($sub) use ($role) {
                        $sub->select(DB::raw(1))
                            ->from('user_roles as ur2')
                            ->join('roles as r2', 'ur2.role_id', '=', 'r2.id')
                            ->whereColumn('ur2.user_id', 'u.id')
                            ->where('r2.name', $role);
                    });
            });
        }

        if (!empty($status)) {
            $query->where('ua.status', $status);
        }

        if (!empty($compliance)) {
            $query->where('ua.compliance_status', $compliance);
        }

        $attendances = $query->orderBy('ua.date', 'desc')->orderBy('ua.id', 'desc')->get();

        // Calculate dynamic live statistics for the selected date
        $totalUsersCount = DB::table('users')->where('status', 'ACTIVE')->count();

        $presentTodayCount = DB::table('user_attendances')
            ->whereDate('date', $targetDate)
            ->distinct('user_id')
            ->count('user_id');

        $punchedInNowCount = DB::table('user_attendances')
            ->whereDate('date', $targetDate)
            ->whereNotNull('punch_in_time')
            ->where(function ($q) {
                $q->whereNull('punch_out_time')->orWhere('punch_out_time', '-');
            })
            ->count();

        $completedTodayCount = DB::table('user_attendances')
            ->whereDate('date', $targetDate)
            ->whereNotNull('punch_out_time')
            ->where('punch_out_time', '!=', '-')
            ->count();

        $atDesignatedLocationCount = DB::table('user_attendances')
            ->whereDate('date', $targetDate)
            ->where('compliance_status', 'AT_REQUIRED_LOCATION')
            ->count();

        $remoteAnywhereCount = DB::table('user_attendances')
            ->whereDate('date', $targetDate)
            ->where('compliance_status', 'ANYWHERE_ALLOWED')
            ->count();

        $absentCount = max(0, $totalUsersCount - $presentTodayCount);

        return response()->json([
            'success' => true,
            'data'    => [
                'attendances' => $attendances,
                'stats'       => [
                    'target_date'             => $targetDate,
                    'total_users'             => $totalUsersCount,
                    'present_today'           => $presentTodayCount,
                    'punched_in_now'          => $punchedInNowCount,
                    'completed_today'         => $completedTodayCount,
                    'absent_today'            => $absentCount,
                    'at_designated_location'  => $atDesignatedLocationCount,
                    'remote_anywhere'         => $remoteAnywhereCount,
                    'attendance_percentage'   => $totalUsersCount > 0 ? round(($presentTodayCount / $totalUsersCount) * 100, 1) : 0,
                ],
                'policy'      => $locationSetting,
            ],
        ]);
    }

    /**
     * Admin manual mark or override attendance record for a user.
     */
    public function manualMark(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'         => 'required|exists:users,id',
            'date'            => 'required|date',
            'status'          => 'required|string',
            'punch_in_time'   => 'nullable|string',
            'punch_out_time'  => 'nullable|string',
            'address'         => 'nullable|string',
            'notes'           => 'nullable|string',
        ]);

        $user = User::find($request->user_id);
        $roleName = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $user->id)
            ->value('roles.name') ?? 'WORKER';

        $existing = UserAttendance::where('user_id', $user->id)
            ->whereDate('date', $request->date)
            ->first();

        if ($existing) {
            $existing->update([
                'status'         => $request->status,
                'punch_in_time'  => $request->punch_in_time ?? $existing->punch_in_time,
                'punch_out_time' => $request->punch_out_time ?? $existing->punch_out_time,
                'address'        => $request->address ?? $existing->address,
                'notes'          => ($existing->notes ? $existing->notes . " | " : "") . "[Admin Override]: " . ($request->notes ?? 'Manually updated by Admin'),
            ]);
            $record = $existing;
        } else {
            $record = UserAttendance::create([
                'user_id'           => $user->id,
                'user_name'         => $user->full_name,
                'user_role'         => $roleName,
                'date'              => $request->date,
                'punch_in_time'     => $request->punch_in_time ?? '09:00 AM',
                'punch_out_time'    => $request->punch_out_time ?? '-',
                'status'            => $request->status,
                'address'           => $request->address ?? 'सांगली कार्यालय (मॅन्युअल नोंद)',
                'compliance_status' => 'MANUAL_VERIFIED',
                'notes'             => "[Admin Entry]: " . ($request->notes ?? 'Manually added by Admin HQ'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'उपस्थिती नोंद यशस्वीरीत्या अद्ययावत केली. (Attendance successfully recorded).',
            'data'    => $record,
        ]);
    }

    /**
     * Get a specific user's everyday presentation history with exact timestamps and addresses.
     * "while clicking any user admin can able to click his everyday presentation with time make sure all are done from the database"
     */
    public function getUserEverydayAttendance(Request $request, $userId): JsonResponse
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'वापरकर्ता डेटाबेसमध्ये आढळला नाही (User not found).',
            ], 404);
        }

        $roleName = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $userId)
            ->value('roles.name') ?? 'WORKER';

        $history = DB::table('user_attendances')
            ->where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->get();

        $enrichedHistory = $history->map(function ($record) {
            $record->day_name = date('l', strtotime($record->date));
            $record->day_name_mr = [
                'Monday'    => 'सोमवार',
                'Tuesday'   => 'मंगळवार',
                'Wednesday' => 'बुधवार',
                'Thursday'  => 'गुरूवार',
                'Friday'    => 'शुक्रवार',
                'Saturday'  => 'शनिवार',
                'Sunday'    => 'रविवार',
            ][$record->day_name] ?? $record->day_name;

            $record->duration = self::calculateDuration($record->punch_in_time, $record->punch_out_time);
            return $record;
        });

        $totalPresent = $history->whereIn('status', ['Present', 'Completed'])->count();
        $totalCompleted = $history->where('status', 'Completed')->count();
        $totalAtHQ = $history->where('compliance_status', 'AT_REQUIRED_LOCATION')->count();
        $totalRemote = $history->where('compliance_status', 'ANYWHERE_ALLOWED')->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'user'    => [
                    'id'            => $user->id,
                    'full_name'     => $user->full_name,
                    'phone'         => $user->phone,
                    'email'         => $user->email,
                    'role'          => $roleName,
                    'profile_photo' => $user->profile_photo,
                    'status'        => $user->status,
                ],
                'summary' => [
                    'total_days_logged' => $history->count(),
                    'total_present'     => $totalPresent,
                    'total_completed'   => $totalCompleted,
                    'total_at_hq'       => $totalAtHQ,
                    'total_remote'      => $totalRemote,
                ],
                'history' => $enrichedHistory,
            ],
        ]);
    }

    /**
     * Calculate working duration between Punch In and Punch Out timestamps.
     */
    public static function calculateDuration($inTime, $outTime): ?string
    {
        if (!$inTime || !$outTime || $outTime === '-' || $inTime === '-') {
            return null;
        }
        try {
            $in = \DateTime::createFromFormat('h:i A', trim($inTime));
            $out = \DateTime::createFromFormat('h:i A', trim($outTime));
            if ($in && $out) {
                if ($out < $in) {
                    $out->modify('+1 day');
                }
                $diff = $in->diff($out);
                return "{$diff->h}h {$diff->i}m";
            }
        } catch (\Exception $e) {
            // ignore
        }
        return null;
    }
}
