<?php

namespace App\Http\Controllers\Neta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NetaDashboardController extends Controller
{
    /**
     * GET /api/neta/overview
     * Returns constituency overview, key metrics, sentiment and countdown.
     */
    public function overview()
    {
        $profile = DB::table('neta_area_profiles')->first();
        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        // Live stats calculations
        $totalBooths = DB::table('neta_booth_progress')->count();
        $highCoverage = DB::table('neta_booth_progress')->where('coverage_pct', '>=', 80)->count();
        $mediumCoverage = DB::table('neta_booth_progress')->whereBetween('coverage_pct', [50, 79])->count();
        $lowCoverage = DB::table('neta_booth_progress')->where('coverage_pct', '<', 50)->count();

        $activeWorkers = DB::table('users as u')
            ->join('user_roles as ur', 'u.id', '=', 'ur.user_id')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->whereIn('r.name', ['WORKER', 'OFFICER'])
            ->where('u.status', 'ACTIVE')
            ->count();

        $totalWorkers = DB::table('users as u')
            ->join('user_roles as ur', 'u.id', '=', 'ur.user_id')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->whereIn('r.name', ['WORKER', 'OFFICER'])
            ->count();

        $urgentTasksCount = DB::table('neta_pending_tasks')->where('priority', 'urgent')->where('status', '!=', 'completed')->count();
        $upcomingMeetingsCount = DB::table('neta_meetings_tours')->where('status', 'scheduled')->count();
        $unreadAlertsCount = DB::table('neta_notifications_alerts')->where('is_unread', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'name_mr' => $profile->name_mr,
                    'name_en' => $profile->name_en,
                    'district_mr' => $profile->district_mr,
                    'district_en' => $profile->district_en,
                    'lok_sabha_mr' => $profile->lok_sabha_mr,
                    'lok_sabha_en' => $profile->lok_sabha_en,
                    'neta_title_mr' => $profile->neta_title_mr,
                    'neta_title_en' => $profile->neta_title_en,
                    'total_voters' => (int) $profile->total_voters,
                    'male_voters' => (int) $profile->male_voters,
                    'female_voters' => (int) $profile->female_voters,
                    'third_gender' => (int) $profile->third_gender,
                    'new_voters' => (int) $profile->new_voters,
                    'total_booths' => $totalBooths ?: (int) $profile->total_booths,
                    'sensitive_booths' => (int) $profile->sensitive_booths,
                    'surveyed_households' => (int) $profile->surveyed_households,
                    'target_households' => (int) $profile->target_households,
                    'active_karyakartas_today' => $activeWorkers ?: (int) $profile->active_karyakartas_today,
                    'total_karyakartas' => $totalWorkers ?: (int) $profile->total_karyakartas,
                    'days_to_election' => (int) $profile->days_to_election,
                    'sentiment' => [
                        'support' => (float) $profile->sentiment_support,
                        'neutral_swing' => (float) $profile->sentiment_neutral,
                        'opposition' => (float) $profile->sentiment_opposition,
                    ],
                ],
                'summary' => [
                    'high_coverage_booths' => $highCoverage,
                    'medium_coverage_booths' => $mediumCoverage,
                    'low_coverage_booths' => $lowCoverage,
                    'urgent_tasks_count' => $urgentTasksCount,
                    'upcoming_meetings_count' => $upcomingMeetingsCount,
                    'unread_alerts_count' => $unreadAlertsCount,
                ],
            ],
        ]);
    }

    /**
     * GET /api/neta/geography-filters
     * Returns dynamic dropdown options for District, Assembly, Taluka, Village, Incharge directly from database tables.
     */
    public function geographyFilters(Request $request)
    {
        // 1. Districts from DB
        $masterDistricts = DB::table('districts')
            ->select('id', 'district_code', 'district_name as name_en', 'district_name_mr as name_mr')
            ->orderBy('district_name_mr')
            ->get();

        $boothDistricts = DB::table('neta_booth_progress')
            ->select('district_mr', 'district_en')
            ->distinct()
            ->get();

        // 2. Assemblies (Vidhan Sabha) from DB
        $boothAssemblies = DB::table('neta_booth_progress')
            ->when($request->filled('district') && $request->district !== 'all', function ($q) use ($request) {
                $d = $request->district;
                $q->where(function ($sq) use ($d) {
                    $sq->where('district_mr', 'like', "%{$d}%")
                      ->orWhere('district_en', 'like', "%{$d}%");
                });
            })
            ->select('district_mr', 'district_en', 'assembly_mr', 'assembly_en')
            ->distinct()
            ->get();

        // 3. Talukas from DB
        $talukaQuery = DB::table('talukas')->select('id', 'district_code', 'taluka_code', 'taluka_name as name_en');
        if ($request->filled('district_code')) {
            $talukaQuery->where('district_code', $request->district_code);
        }
        $masterTalukas = $talukaQuery->orderBy('taluka_name')->take(50)->get();

        $boothTalukas = DB::table('neta_booth_progress')
            ->when($request->filled('district') && $request->district !== 'all', function ($q) use ($request) {
                $d = $request->district;
                $q->where(function ($sq) use ($d) {
                    $sq->where('district_mr', 'like', "%{$d}%")
                      ->orWhere('district_en', 'like', "%{$d}%");
                });
            })
            ->when($request->filled('assembly') && $request->assembly !== 'all', function ($q) use ($request) {
                $a = $request->assembly;
                $q->where(function ($sq) use ($a) {
                    $sq->where('assembly_mr', 'like', "%{$a}%")
                      ->orWhere('assembly_en', 'like', "%{$a}%");
                });
            })
            ->select('taluka_mr', 'taluka_en')
            ->distinct()
            ->get();

        // 4. Villages from DB
        $boothVillages = DB::table('neta_booth_progress')
            ->when($request->filled('taluka') && $request->taluka !== 'all', function ($q) use ($request) {
                $t = $request->taluka;
                $q->where(function ($sq) use ($t) {
                    $sq->where('taluka_mr', 'like', "%{$t}%")
                      ->orWhere('taluka_en', 'like', "%{$t}%");
                });
            })
            ->select('village_mr', 'village_en')
            ->distinct()
            ->get();

        // 5. Incharges & Booths from DB
        $boothIncharges = DB::table('neta_booth_progress')
            ->select('incharge_name', 'incharge_phone', 'booth_no', 'booth_name_mr', 'booth_name_en')
            ->distinct()
            ->orderBy('booth_no')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'master_districts' => $masterDistricts,
                'booth_districts' => $boothDistricts,
                'booth_assemblies' => $boothAssemblies,
                'master_talukas' => $masterTalukas,
                'booth_talukas' => $boothTalukas,
                'booth_villages' => $boothVillages,
                'incharges' => $boothIncharges,
            ],
        ]);
    }

    /**
     * GET /api/neta/booths
     * Returns granular booth progress list with optional filters.
     */
    public function booths(Request $request)
    {
        $query = DB::table('neta_booth_progress');

        if ($request->filled('district') && $request->district !== 'all') {
            $d = $request->district;
            $query->where(function ($q) use ($d) {
                $q->where('district_mr', 'like', "%{$d}%")
                  ->orWhere('district_en', 'like', "%{$d}%");
            });
        }

        if ($request->filled('assembly') && $request->assembly !== 'all') {
            $a = $request->assembly;
            $query->where(function ($q) use ($a) {
                $q->where('assembly_mr', 'like', "%{$a}%")
                  ->orWhere('assembly_en', 'like', "%{$a}%");
            });
        }

        if ($request->filled('taluka') && $request->taluka !== 'all') {
            $t = $request->taluka;
            $query->where(function ($q) use ($t) {
                $q->where('taluka_mr', 'like', "%{$t}%")
                  ->orWhere('taluka_en', 'like', "%{$t}%");
            });
        }

        if ($request->filled('village') && $request->village !== 'all') {
            $v = $request->village;
            $query->where(function ($q) use ($v) {
                $q->where('village_mr', 'like', "%{$v}%")
                  ->orWhere('village_en', 'like', "%{$v}%");
            });
        }

        if ($request->filled('incharge') && $request->incharge !== 'all') {
            $i = $request->incharge;
            $query->where('incharge_name', 'like', "%{$i}%");
        }

        if ($request->filled('booth_no') && $request->booth_no !== 'all') {
            $b = $request->booth_no;
            $query->where('booth_no', 'like', "%{$b}%");
        }

        if ($request->filled('coverage_filter')) {
            $cf = $request->coverage_filter;
            if ($cf === 'high') {
                $query->where('coverage_pct', '>=', 80);
            } elseif ($cf === 'medium') {
                $query->whereBetween('coverage_pct', [50, 79]);
            } elseif ($cf === 'low') {
                $query->where('coverage_pct', '<', 50);
            } elseif ($cf === 'sensitive') {
                $query->where('is_sensitive', true);
            }
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('booth_no', 'like', "%{$s}%")
                  ->orWhere('booth_name_mr', 'like', "%{$s}%")
                  ->orWhere('booth_name_en', 'like', "%{$s}%")
                  ->orWhere('incharge_name', 'like', "%{$s}%")
                  ->orWhere('incharge_phone', 'like', "%{$s}%")
                  ->orWhere('village_mr', 'like', "%{$s}%")
                  ->orWhere('village_en', 'like', "%{$s}%")
                  ->orWhere('taluka_mr', 'like', "%{$s}%")
                  ->orWhere('assembly_mr', 'like', "%{$s}%")
                  ->orWhere('district_mr', 'like', "%{$s}%");
            });
        }

        $booths = $query->orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $booths,
            'count' => $booths->count(),
        ]);
    }

    /**
     * GET /api/neta/karyakartas
     * Returns worker list sourced directly from the `users` table joined with `user_roles`, `roles`, and `neta_karyakartas`.
     */
    public function karyakartas(Request $request)
    {
        $query = DB::table('users as u')
            ->join('user_roles as ur', 'u.id', '=', 'ur.user_id')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->leftJoin('neta_karyakartas as nk', 'u.id', '=', 'nk.user_id')
            ->whereIn('r.name', ['WORKER', 'OFFICER']);

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('r.name', strtoupper($request->role));
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('u.status', 'ACTIVE');
            } elseif ($request->status === 'offline') {
                $query->whereIn('u.status', ['INACTIVE', 'SUSPENDED', 'PENDING']);
            } else {
                $query->where('u.status', strtoupper($request->status));
            }
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('u.full_name', 'like', "%{$s}%")
                  ->orWhere('u.phone', 'like', "%{$s}%")
                  ->orWhere('r.display_name', 'like', "%{$s}%")
                  ->orWhere('r.name', 'like', "%{$s}%")
                  ->orWhere('nk.assigned_area_mr', 'like', "%{$s}%")
                  ->orWhere('nk.assigned_area_en', 'like', "%{$s}%");
            });
        }

        $records = $query->select(
            'u.id as user_id',
            'u.full_name as name',
            'u.phone',
            'u.email',
            'u.status as user_status',
            'r.id as role_id',
            'r.name as role_name',
            'r.display_name as role_display',
            'nk.id as karyakarta_id',
            'nk.role_mr',
            'nk.role_en',
            'nk.assigned_area_mr',
            'nk.assigned_area_en',
            'nk.total_surveys',
            'nk.today_surveys',
            'nk.rating',
            'nk.coverage_pct',
            'nk.active_since',
            'nk.badges'
        )
        ->orderBy(DB::raw('COALESCE(nk.total_surveys, 0)'), 'desc')
        ->orderBy('u.full_name', 'asc')
        ->get();

        $records->transform(function ($w) {
            $w->id = $w->user_id; // Primary ID is users.id
            $w->status = strtoupper($w->user_status) === 'ACTIVE' ? 'active' : 'offline';
            $w->role_mr = $w->role_mr ?: ($w->role_name === 'OFFICER' ? 'कक्ष अधिकारी / समन्वयक' : 'फील्ड कार्यकर्ता / बूथ इनचार्ज');
            $w->role_en = $w->role_display ?: ($w->role_name === 'OFFICER' ? 'Leader / Officer' : 'Field Worker');
            $w->assigned_area_mr = $w->assigned_area_mr ?: 'सांगली कार्यक्षेत्र';
            $w->assigned_area_en = $w->assigned_area_en ?: 'Sangli Constituency';
            $w->total_surveys = (int) ($w->total_surveys ?? 0);
            $w->today_surveys = (int) ($w->today_surveys ?? 0);
            $w->rating = number_format((float) ($w->rating ?? 4.5), 2);
            $w->coverage_pct = (int) ($w->coverage_pct ?? 50);
            $w->badges = is_string($w->badges) ? json_decode($w->badges, true) : ($w->badges ?: []);
            return $w;
        });

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    /**
     * PUT /api/neta/karyakartas/{id}
     * Updates worker details directly in `users` and `user_roles` tables, and synchronizes `neta_karyakartas`.
     */
    public function updateKaryakarta(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'कार्यकर्ता युझर टेबलमध्ये सापडला नाही (User not found)'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:20|unique:users,phone,' . $id,
            'role_id' => 'nullable|integer|exists:roles,id',
            'role_name' => 'nullable|string',
            'status' => 'nullable|string|in:active,offline,ACTIVE,INACTIVE,PENDING,SUSPENDED',
            'assigned_area_mr' => 'nullable|string|max:255',
            'assigned_area_en' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $dbStatus = 'ACTIVE';
        if ($request->filled('status')) {
            $s = strtoupper($request->status);
            $dbStatus = ($s === 'OFFLINE' || $s === 'INACTIVE') ? 'INACTIVE' : 'ACTIVE';
        }

        // 1. Update directly in `users` table
        DB::table('users')->where('id', $id)->update([
            'full_name' => trim($request->name),
            'phone' => trim($request->phone),
            'status' => $dbStatus,
            'updated_at' => now(),
        ]);

        // 2. Update role directly in `user_roles` table
        $roleId = $request->role_id;
        if (!$roleId && $request->filled('role_name')) {
            $roleRecord = DB::table('roles')->where('name', strtoupper($request->role_name))->first();
            if ($roleRecord) {
                $roleId = $roleRecord->id;
            }
        }

        if ($roleId) {
            DB::table('user_roles')->where('user_id', $id)->delete();
            DB::table('user_roles')->insert([
                'user_id' => $id,
                'role_id' => $roleId,
                'created_at' => now(),
            ]);
        } else {
            $currentRole = DB::table('user_roles')->where('user_id', $id)->first();
            $roleId = $currentRole ? $currentRole->role_id : 1;
        }

        // 3. Keep neta_karyakartas synchronized
        $assignedRole = DB::table('roles')->where('id', $roleId)->first();
        $roleEn = $assignedRole ? $assignedRole->display_name : 'Field Worker';
        $roleMr = ($assignedRole && $assignedRole->name === 'OFFICER') ? 'कक्ष अधिकारी / समन्वयक' : 'फील्ड कार्यकर्ता / बूथ इनचार्ज';

        DB::table('neta_karyakartas')->updateOrInsert(
            ['user_id' => $id],
            [
                'name' => trim($request->name),
                'phone' => trim($request->phone),
                'role_mr' => $roleMr,
                'role_en' => $roleEn,
                'assigned_area_mr' => $request->assigned_area_mr ?? 'सांगली कार्यक्षेत्र',
                'assigned_area_en' => $request->assigned_area_en ?? 'Sangli Constituency',
                'status' => $dbStatus === 'ACTIVE' ? 'active' : 'offline',
                'updated_at' => now(),
            ]
        );

        $updatedUser = DB::table('users as u')
            ->join('user_roles as ur', 'u.id', '=', 'ur.user_id')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->leftJoin('neta_karyakartas as nk', 'u.id', '=', 'nk.user_id')
            ->where('u.id', $id)
            ->select(
                'u.id as user_id',
                'u.full_name as name',
                'u.phone',
                'u.status as user_status',
                'r.id as role_id',
                'r.name as role_name',
                'r.display_name as role_display',
                'nk.assigned_area_mr',
                'nk.assigned_area_en'
            )
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'कार्यकर्त्याचे नाव व भूमिका थेट Users टेबलमध्ये सेव्ह करण्यात आली.',
            'data' => $updatedUser,
        ]);
    }

    /**
     * POST /api/neta/karyakartas
     * Create a new worker directly into `users` table and assign role in `user_roles`.
     */
    public function storeKaryakarta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:20|unique:users,phone',
            'role_id' => 'nullable|integer|exists:roles,id',
            'role_name' => 'nullable|string',
            'status' => 'nullable|string',
            'assigned_area_mr' => 'nullable|string|max:255',
            'assigned_area_en' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $roleId = $request->role_id;
        if (!$roleId && $request->filled('role_name')) {
            $roleRecord = DB::table('roles')->where('name', strtoupper($request->role_name))->first();
            if ($roleRecord) {
                $roleId = $roleRecord->id;
            }
        }
        if (!$roleId) {
            $defaultWorkerRole = DB::table('roles')->where('name', 'WORKER')->first();
            $roleId = $defaultWorkerRole ? $defaultWorkerRole->id : 1;
        }

        $dbStatus = 'ACTIVE';
        if ($request->filled('status')) {
            $s = strtoupper($request->status);
            $dbStatus = ($s === 'OFFLINE' || $s === 'INACTIVE') ? 'INACTIVE' : 'ACTIVE';
        }

        // 1. Insert into users table
        $userId = DB::table('users')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'full_name' => trim($request->name),
            'phone' => trim($request->phone),
            'password_hash' => Hash::make('Worker@123'),
            'status' => $dbStatus,
            'registration_source' => 'ADMIN_CREATED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Insert into user_roles table
        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => now(),
        ]);

        $assignedRole = DB::table('roles')->where('id', $roleId)->first();
        $roleEn = $assignedRole ? $assignedRole->display_name : 'Field Worker';
        $roleMr = ($assignedRole && $assignedRole->name === 'OFFICER') ? 'कक्ष अधिकारी / समन्वयक' : 'फील्ड कार्यकर्ता / बूथ इनचार्ज';

        // 3. Insert into neta_karyakartas table
        DB::table('neta_karyakartas')->insert([
            'user_id' => $userId,
            'name' => trim($request->name),
            'phone' => trim($request->phone),
            'role_mr' => $roleMr,
            'role_en' => $roleEn,
            'assigned_area_mr' => $request->assigned_area_mr ?? 'सांगली कार्यक्षेत्र',
            'assigned_area_en' => $request->assigned_area_en ?? 'Sangli Constituency',
            'total_surveys' => 0,
            'today_surveys' => 0,
            'rating' => 5.00,
            'status' => $dbStatus === 'ACTIVE' ? 'active' : 'offline',
            'coverage_pct' => 0,
            'active_since' => now()->toDateString(),
            'badges' => json_encode(['New Joinee']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'नवीन कार्यकर्ता थेट Users टेबलमध्ये यशस्वीरित्या तयार करण्यात आला.',
            'user_id' => $userId,
        ], 201);
    }

    /**
     * GET /api/neta/roles
     * Returns list of roles from `roles` table.
     */
    public function roles()
    {
        $roles = DB::table('roles')->select('id', 'name', 'display_name', 'description')->get();
        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * POST /api/neta/karyakartas/{id}/task
     * Assign a direct task to a specific karyakarta (by user_id or worker id).
     */
    public function assignKaryakartaTask(Request $request, $id)
    {
        $worker = DB::table('neta_karyakartas')->where('user_id', $id)->orWhere('id', $id)->first();
        if (!$worker) {
            $user = DB::table('users')->where('id', $id)->first();
            if ($user) {
                $worker = (object) ['name' => $user->full_name];
            } else {
                return response()->json(['success' => false, 'message' => 'Worker not found'], 404);
            }
        }

        $validator = Validator::make($request->all(), [
            'task_note' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $taskId = DB::table('neta_pending_tasks')->insertGetId([
            'title_mr' => 'थेट कार्य: ' . mb_substr($request->task_note, 0, 60),
            'title_en' => 'Direct Task: ' . mb_substr($request->task_note, 0, 60),
            'category_mr' => 'बूथ व थेट संपर्क',
            'category_en' => 'Field Outreach',
            'assigned_to' => $worker->name,
            'priority' => 'high',
            'deadline' => now()->addDays(2)->toDateString(),
            'status' => 'pending',
            'notes_mr' => $request->task_note,
            'notes_en' => $request->task_note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task assigned and dispatched successfully.',
            'task_id' => $taskId,
        ]);
    }

    /**
     * GET /api/neta/meetings
     * Returns scheduled and completed visits & meetings.
     */
    public function meetings(Request $request)
    {
        $query = DB::table('neta_meetings_tours');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $meetings = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $meetings,
        ]);
    }

    /**
     * POST /api/neta/meetings
     * Schedule a new tour or meeting.
     */
    public function storeMeeting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title_mr' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|string|max:100',
            'venue_mr' => 'required|string|max:255',
            'type_mr' => 'nullable|string|max:100',
            'expected_attendees' => 'nullable|string|max:100',
            'incharge' => 'nullable|string|max:150',
            'agenda_mr' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $id = DB::table('neta_meetings_tours')->insertGetId([
            'title_mr' => $request->title_mr,
            'title_en' => $request->title_en ?: $request->title_mr,
            'date' => $request->date,
            'time' => $request->time,
            'venue_mr' => $request->venue_mr,
            'venue_en' => $request->venue_en ?: $request->venue_mr,
            'type_mr' => $request->type_mr ?: 'नागरिक संवाद दौरा',
            'type_en' => $request->type_en ?: 'Public Townhall Tour',
            'expected_attendees' => $request->expected_attendees ?: '२५०+ नागरिक',
            'incharge' => $request->incharge,
            'status' => 'scheduled',
            'priority' => $request->priority ?: 'high',
            'agenda_mr' => $request->agenda_mr,
            'agenda_en' => $request->agenda_en ?: $request->agenda_mr,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $created = DB::table('neta_meetings_tours')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Meeting scheduled successfully.',
            'data' => $created,
        ]);
    }

    /**
     * PATCH /api/neta/meetings/{id}/status
     * Toggle meeting status between completed and scheduled.
     */
    public function updateMeetingStatus(Request $request, $id)
    {
        $meeting = DB::table('neta_meetings_tours')->where('id', $id)->first();
        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'Meeting not found'], 404);
        }

        $nextStatus = $request->status ?: ($meeting->status === 'scheduled' ? 'completed' : 'scheduled');

        DB::table('neta_meetings_tours')->where('id', $id)->update([
            'status' => $nextStatus,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meeting status updated successfully.',
            'status' => $nextStatus,
        ]);
    }

    /**
     * GET /api/neta/tasks
     * Returns pending tasks and follow-ups.
     */
    public function tasks(Request $request)
    {
        $query = DB::table('neta_pending_tasks');

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'urgent') {
                $query->where('priority', 'urgent');
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('title_mr', 'like', "%{$s}%")
                  ->orWhere('assigned_to', 'like', "%{$s}%")
                  ->orWhere('category_mr', 'like', "%{$s}%");
            });
        }

        $tasks = $query->orderBy('priority', 'desc')->orderBy('deadline', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * POST /api/neta/tasks
     * Create a new pending task.
     */
    public function storeTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title_mr' => 'required|string|max:255',
            'category_mr' => 'nullable|string|max:100',
            'assigned_to' => 'required|string|max:150',
            'priority' => 'required|in:urgent,high,medium,low',
            'deadline' => 'required|date',
            'notes_mr' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $id = DB::table('neta_pending_tasks')->insertGetId([
            'title_mr' => $request->title_mr,
            'title_en' => $request->title_en ?: $request->title_mr,
            'category_mr' => $request->category_mr ?: 'नागरी समस्या निवारण',
            'category_en' => $request->category_en ?: 'Civic Grievance',
            'assigned_to' => $request->assigned_to,
            'priority' => $request->priority,
            'deadline' => $request->deadline,
            'status' => 'pending',
            'notes_mr' => $request->notes_mr,
            'notes_en' => $request->notes_en ?: $request->notes_mr,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $created = DB::table('neta_pending_tasks')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'data' => $created,
        ]);
    }

    /**
     * PATCH /api/neta/tasks/{id}/status
     * Update task status (pending, in_progress, completed).
     */
    public function updateTaskStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $task = DB::table('neta_pending_tasks')->where('id', $id)->first();
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        DB::table('neta_pending_tasks')->where('id', $id)->update([
            'status' => $request->status,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task status updated.',
            'status' => $request->status,
        ]);
    }

    /**
     * GET /api/neta/reports
     * Returns structured intelligence reports from DB.
     */
    public function reports()
    {
        $rows = DB::table('neta_intelligence_reports')->get();
        $formatted = [];

        foreach ($rows as $row) {
            $formatted[$row->report_key] = json_decode($row->data_json, true);
        }

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    /**
     * GET /api/neta/field-updates
     * Returns live feed of field notes and ground updates.
     */
    public function fieldUpdates(Request $request)
    {
        $query = DB::table('neta_field_updates');

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $updates = $query->orderBy('id', 'desc')->get();

        $updates->transform(function ($u) {
            $u->time_ago = \Carbon\Carbon::parse($u->created_at)->diffForHumans();
            $u->timestamp = \Carbon\Carbon::parse($u->created_at)->format('Y-m-d H:i');
            return $u;
        });

        return response()->json([
            'success' => true,
            'data' => $updates,
        ]);
    }

    /**
     * POST /api/neta/field-updates
     * Publish a new ground update to feed.
     */
    public function storeFieldUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title_mr' => 'required|string|max:255',
            'detail_mr' => 'required|string',
            'type' => 'required|in:positive,grievance,opposition,warning',
            'location' => 'nullable|string|max:150',
            'reporter_name' => 'nullable|string|max:150',
            'booth_ref' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $id = DB::table('neta_field_updates')->insertGetId([
            'title_mr' => $request->title_mr,
            'title_en' => $request->title_en ?: $request->title_mr,
            'detail_mr' => $request->detail_mr,
            'detail_en' => $request->detail_en ?: $request->detail_mr,
            'type' => $request->type,
            'location' => $request->location ?: 'सांगली मध्यवर्ती कार्यालय',
            'reporter_name' => $request->reporter_name ?: 'मा. नेता संपर्क कक्ष',
            'reporter_role' => 'क्षेत्रीय समन्वयक',
            'booth_ref' => $request->booth_ref ?: 'मध्यवर्ती कक्ष',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $created = DB::table('neta_field_updates')->where('id', $id)->first();
        $created->time_ago = 'Just now';
        $created->timestamp = now()->format('Y-m-d H:i');

        return response()->json([
            'success' => true,
            'message' => 'Update posted successfully.',
            'data' => $created,
        ]);
    }

    /**
     * GET /api/neta/notifications
     * Returns priority notifications and alerts.
     */
    public function notifications(Request $request)
    {
        $query = DB::table('neta_notifications_alerts');

        if ($request->filled('filter')) {
            if ($request->filter === 'unread') {
                $query->where('is_unread', true);
            } elseif ($request->filter === 'critical') {
                $query->where('severity', 'critical');
            }
        }

        $alerts = $query->orderBy('id', 'desc')->get();

        $alerts->transform(function ($a) {
            $a->time = \Carbon\Carbon::parse($a->created_at)->diffForHumans();
            return $a;
        });

        $unreadCount = DB::table('neta_notifications_alerts')->where('is_unread', true)->count();

        return response()->json([
            'success' => true,
            'data' => $alerts,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * POST /api/neta/notifications/broadcast
     * Broadcast an urgent alert notice to field workers.
     */
    public function broadcastNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'severity' => 'nullable|string|in:critical,warning,info,success',
            'title_mr' => 'nullable|string|max:150',
            'target_group' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $severity = $request->severity ?: 'warning';
        $titleMr = $request->title_mr ?: ('नेत्यांचा थेट आदेश: ' . mb_substr($request->message, 0, 45) . '...');

        $id = DB::table('neta_notifications_alerts')->insertGetId([
            'severity' => $severity,
            'title_mr' => $titleMr,
            'title_en' => 'Leader Direct Directive: ' . mb_substr($request->message, 0, 45) . '...',
            'detail_mr' => $request->message,
            'detail_en' => $request->message,
            'is_unread' => true,
            'target_group' => $request->target_group ?: 'all_workers',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $created = DB::table('neta_notifications_alerts')->where('id', $id)->first();
        $created->time = 'Just now';

        return response()->json([
            'success' => true,
            'message' => 'Alert broadcasted successfully.',
            'data' => $created,
        ]);
    }

    /**
     * PATCH /api/neta/notifications/{id}/read
     * Toggle read status.
     */
    public function toggleNotificationRead($id)
    {
        $alert = DB::table('neta_notifications_alerts')->where('id', $id)->first();
        if (!$alert) {
            return response()->json(['success' => false, 'message' => 'Alert not found'], 404);
        }

        $newUnread = !$alert->is_unread;
        DB::table('neta_notifications_alerts')->where('id', $id)->update([
            'is_unread' => $newUnread,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'is_unread' => $newUnread,
        ]);
    }

    /**
     * POST /api/neta/notifications/mark-all-read
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead()
    {
        DB::table('neta_notifications_alerts')->update([
            'is_unread' => false,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'All alerts marked as read.',
        ]);
    }

    /**
     * GET /api/neta/crm/citizens
     * Returns searchable citizen list with metrics and filters.
     */
    public function crmCitizens(Request $request)
    {
        $query = DB::table('neta_citizens');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('full_name_mr', 'like', "%{$s}%")
                    ->orWhere('full_name_en', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('voter_id_number', 'like', "%{$s}%")
                    ->orWhere('family_head_name', 'like', "%{$s}%")
                    ->orWhere('address_mr', 'like', "%{$s}%");
            });
        }

        if ($request->filled('booth_no') && $request->booth_no !== 'all') {
            $query->where('booth_no', $request->booth_no);
        }

        if ($request->filled('sentiment') && $request->sentiment !== 'all') {
            $query->where('sentiment', $request->sentiment);
        }

        if ($request->filled('voter_category') && $request->voter_category !== 'all') {
            $query->where('voter_category', $request->voter_category);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $totalCount = (clone $query)->count();
        $citizens = $query->orderBy('id', 'desc')->get();

        // Attach counts & decode JSON
        $citizens->transform(function ($c) {
            $c->schemes_benefitted = json_decode($c->schemes_benefitted ?? '[]', true) ?: [];
            $c->interactions_count = DB::table('neta_crm_interactions')->where('citizen_id', $c->id)->count();
            $c->pending_issues_count = DB::table('neta_crm_issues')->where('citizen_id', $c->id)->where('status', '!=', 'resolved')->count();
            return $c;
        });

        // Global stats for CRM overview
        $stats = [
            'total_citizens' => DB::table('neta_citizens')->count(),
            'supportive' => DB::table('neta_citizens')->where('sentiment', 'anukul')->count(),
            'neutral' => DB::table('neta_citizens')->where('sentiment', 'tatstha')->count(),
            'opposition' => DB::table('neta_citizens')->where('sentiment', 'virodhak')->count(),
            'total_issues' => DB::table('neta_crm_issues')->count(),
            'pending_issues' => DB::table('neta_crm_issues')->where('status', '!=', 'resolved')->count(),
            'interactions_logged' => DB::table('neta_crm_interactions')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $citizens,
            'total' => $totalCount,
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/neta/crm/citizens/{id}
     * Returns complete 360° profile of a citizen with interaction logs, issues, and meetings.
     */
    public function crmCitizenDetail($id)
    {
        $citizen = DB::table('neta_citizens')->where('id', $id)->first();
        if (!$citizen) {
            return response()->json(['success' => false, 'message' => 'Citizen not found'], 404);
        }

        $citizen->schemes_benefitted = json_decode($citizen->schemes_benefitted ?? '[]', true) ?: [];

        $interactions = DB::table('neta_crm_interactions')
            ->where('citizen_id', $id)
            ->orderBy('interaction_date', 'desc')
            ->get();

        $issues = DB::table('neta_crm_issues')
            ->where('citizen_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $meetings = DB::table('neta_crm_meetings_attended')
            ->where('citizen_id', $id)
            ->orderBy('meeting_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'citizen' => $citizen,
                'interactions' => $interactions,
                'issues' => $issues,
                'meetings' => $meetings,
            ],
        ]);
    }

    /**
     * POST /api/neta/crm/citizens
     * Store new citizen/voter contact.
     */
    public function storeCrmCitizen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name_mr' => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'booth_no' => 'required|string|max:50',
            'address_mr' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $sentimentLabels = [
            'anukul' => 'भक्कम अनुकूल',
            'tatstha' => 'तटस्थ / संवाद आवश्यक',
            'virodhak' => 'विरोधी / मन वळवणे गरजेचे',
        ];

        $sentiment = $request->sentiment ?: 'anukul';
        $label = $sentimentLabels[$sentiment] ?? 'अनुकूल';

        $id = DB::table('neta_citizens')->insertGetId([
            'voter_id_number' => $request->voter_id_number ?: ('MH/44/282/' . rand(100000, 999999)),
            'full_name_mr' => $request->full_name_mr,
            'full_name_en' => $request->full_name_en,
            'phone' => $request->phone,
            'alternate_phone' => $request->alternate_phone,
            'age' => $request->age ?: 35,
            'gender' => $request->gender ?: 'male',
            'occupation_mr' => $request->occupation_mr ?: 'नागरिक',
            'family_head_name' => $request->family_head_name ?: $request->full_name_mr,
            'family_members_count' => $request->family_members_count ?: 3,
            'address_mr' => $request->address_mr,
            'address_en' => $request->address_en,
            'district_mr' => $request->district_mr ?: 'सांगली',
            'assembly_mr' => '२८२ - सांगली',
            'taluka_mr' => $request->taluka_mr ?: 'सांगली शहर व प्रभाग',
            'village_mr' => $request->village_mr ?: 'सांगली',
            'booth_no' => $request->booth_no,
            'voter_category' => $request->voter_category ?: 'general',
            'sentiment' => $sentiment,
            'sentiment_label_mr' => $label,
            'assigned_worker_name' => $request->assigned_worker_name,
            'assigned_worker_phone' => $request->assigned_worker_phone,
            'follow_up_date' => $request->follow_up_date ?: date('Y-m-d', strtotime('+7 days')),
            'status' => 'active',
            'notes_mr' => $request->notes_mr,
            'schemes_benefitted' => json_encode($request->schemes_benefitted ?: []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!empty($request->initial_interaction)) {
            DB::table('neta_crm_interactions')->insert([
                'citizen_id' => $id,
                'interaction_type' => 'home_visit',
                'interaction_date' => date('Y-m-d'),
                'conducted_by' => $request->assigned_worker_name ?: 'क्षेत्र कार्यकर्ता',
                'summary_mr' => $request->initial_interaction,
                'outcome' => 'positive',
                'next_action_mr' => 'पुढील पाठपुरावा ठेवणे',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $newCitizen = DB::table('neta_citizens')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'नागरिक CRM नोंदणी यशस्वीरित्या सेव्ह झाली.',
            'data' => $newCitizen,
        ]);
    }

    /**
     * POST /api/neta/crm/citizens/{id}/interactions
     * Log a contact visit or call for a citizen.
     */
    public function storeCrmInteraction(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'interaction_type' => 'required|string',
            'conducted_by' => 'required|string',
            'summary_mr' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $intId = DB::table('neta_crm_interactions')->insertGetId([
            'citizen_id' => $id,
            'interaction_type' => $request->interaction_type,
            'interaction_date' => $request->interaction_date ?: date('Y-m-d'),
            'conducted_by' => $request->conducted_by,
            'summary_mr' => $request->summary_mr,
            'outcome' => $request->outcome ?: 'positive',
            'next_action_mr' => $request->next_action_mr,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->filled('follow_up_date')) {
            DB::table('neta_citizens')->where('id', $id)->update([
                'follow_up_date' => $request->follow_up_date,
                'status' => 'contacted',
                'updated_at' => now(),
            ]);
        }

        $interaction = DB::table('neta_crm_interactions')->where('id', $intId)->first();

        return response()->json([
            'success' => true,
            'message' => 'संपर्क नोंद यशस्वी झाली.',
            'data' => $interaction,
        ]);
    }

    /**
     * POST /api/neta/crm/citizens/{id}/issues
     * Log an issue or grievance request for a citizen.
     */
    public function storeCrmIssue(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title_mr' => 'required|string|max:200',
            'category' => 'required|string',
            'description_mr' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $trackingNo = 'GRV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $issueId = DB::table('neta_crm_issues')->insertGetId([
            'citizen_id' => $id,
            'tracking_no' => $trackingNo,
            'title_mr' => $request->title_mr,
            'category' => $request->category,
            'priority' => $request->priority ?: 'high',
            'status' => 'pending',
            'description_mr' => $request->description_mr,
            'assigned_worker' => $request->assigned_worker,
            'follow_up_date' => $request->follow_up_date ?: date('Y-m-d', strtotime('+5 days')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Also add to neta_pending_tasks
        DB::table('neta_pending_tasks')->insert([
            'title_mr' => "नागरी तक्रार [{$trackingNo}]: " . $request->title_mr,
            'title_en' => "Civic Grievance [{$trackingNo}]: " . ($request->title_en ?: $request->title_mr),
            'category_mr' => 'नागरी समस्या निवारण',
            'category_en' => 'Civic Grievance',
            'assigned_to' => $request->assigned_worker ?: 'क्षेत्र कार्यकर्ता',
            'priority' => $request->priority ?: 'high',
            'deadline' => $request->follow_up_date ?: date('Y-m-d', strtotime('+5 days')),
            'status' => 'pending',
            'notes_mr' => $request->description_mr,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createdIssue = DB::table('neta_crm_issues')->where('id', $issueId)->first();

        return response()->json([
            'success' => true,
            'message' => "नागरी तक्रार [{$trackingNo}] यशस्वीरित्या नोंदवली गेली.",
            'data' => $createdIssue,
        ]);
    }

    /**
     * PATCH /api/neta/crm/issues/{issueId}/status
     * Update issue status & resolution.
     */
    public function updateCrmIssueStatus(Request $request, $issueId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,in_progress,resolved,rejected',
            'resolution_notes_mr' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::table('neta_crm_issues')->where('id', $issueId)->update([
            'status' => $request->status,
            'resolution_notes_mr' => $request->resolution_notes_mr,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'समस्येची स्थिती अद्ययावत केली गेली.',
        ]);
    }

    /**
     * GET /api/neta/export/{type}
     * Fetch formatted datasets for export.
     */
    public function exportData($type)
    {
        if ($type === 'booths') {
            $data = DB::table('neta_booth_progress')->get();
        } elseif ($type === 'workers') {
            $data = DB::table('neta_karyakartas')->get();
        } elseif ($type === 'tasks') {
            $data = DB::table('neta_pending_tasks')->get();
        } elseif ($type === 'meetings') {
            $data = DB::table('neta_meetings_tours')->get();
        } elseif ($type === 'crm_citizens') {
            $data = DB::table('neta_citizens')->get();
        } elseif ($type === 'crm_issues') {
            $data = DB::table('neta_crm_issues as i')
                ->join('neta_citizens as c', 'i.citizen_id', '=', 'c.id')
                ->select('i.*', 'c.full_name_mr as citizen_name', 'c.phone as citizen_phone', 'c.booth_no')
                ->get();
        } elseif ($type === 'users') {
            $data = DB::table('users as u')
                ->leftJoin('user_roles as ur', 'u.id', '=', 'ur.user_id')
                ->leftJoin('roles as r', 'ur.role_id', '=', 'r.id')
                ->select(
                    'u.id',
                    'u.uuid',
                    'u.full_name',
                    'u.phone',
                    'u.email',
                    'u.status',
                    'u.registration_source',
                    'u.created_at',
                    DB::raw('GROUP_CONCAT(r.name) as roles')
                )
                ->groupBy('u.id', 'u.uuid', 'u.full_name', 'u.phone', 'u.email', 'u.status', 'u.registration_source', 'u.created_at')
                ->get();
        } elseif ($type === 'surveys') {
            $data = DB::table('admin_field_submissions')
                ->select(
                    'id',
                    'submission_code',
                    'form_code',
                    'form_title_mr',
                    'citizen_name_mr',
                    'voter_id_number',
                    'phone',
                    'booth_no',
                    'village_mr',
                    'taluka_mr',
                    'submitted_by_name',
                    'status',
                    'verified_at',
                    'created_at'
                )
                ->get();
        } elseif ($type === 'survey_forms') {
            $data = DB::table('survey_form')->get();
        } elseif ($type === 'full_backup') {
            $backup = [
                'metadata' => [
                    'app' => 'Political Project Management System',
                    'backup_date' => now()->toIso8601String(),
                    'constituency' => '282 - Sangli Assembly',
                ],
                'users' => DB::table('users as u')
                    ->leftJoin('user_roles as ur', 'u.id', '=', 'ur.user_id')
                    ->leftJoin('roles as r', 'ur.role_id', '=', 'r.id')
                    ->select(
                        'u.id',
                        'u.uuid',
                        'u.full_name',
                        'u.phone',
                        'u.email',
                        'u.status',
                        'u.registration_source',
                        'u.created_at',
                        DB::raw('GROUP_CONCAT(r.name) as roles')
                    )
                    ->groupBy('u.id', 'u.uuid', 'u.full_name', 'u.phone', 'u.email', 'u.status', 'u.registration_source', 'u.created_at')
                    ->get(),
                'citizens' => DB::table('neta_citizens')->get(),
                'booths' => DB::table('neta_booth_progress')->get(),
                'workers' => DB::table('neta_karyakartas')->get(),
                'survey_submissions' => DB::table('admin_field_submissions')->get(),
                'survey_forms' => DB::table('survey_form')->get(),
                'crm_issues' => DB::table('neta_crm_issues as i')
                    ->leftJoin('neta_citizens as c', 'i.citizen_id', '=', 'c.id')
                    ->select('i.*', 'c.full_name_mr as citizen_name', 'c.phone as citizen_phone', 'c.booth_no')
                    ->get(),
                'meetings' => DB::table('neta_meetings_tours')->get(),
                'tasks' => DB::table('neta_pending_tasks')->get(),
            ];
            return response()->json([
                'success' => true,
                'data' => $backup,
            ]);
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid export type'], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
