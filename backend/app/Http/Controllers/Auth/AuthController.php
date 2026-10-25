<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RegistrationSource;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\Admin;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // -------------------------------------------------------------------------
    // POST /api/auth/register
    // -------------------------------------------------------------------------

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $roleName = strtoupper($validated['role'] ?? 'WORKER');

        // Find the requested role — look up by name, never trust an ID from the client.
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            return response()->json([
                'message' => 'The requested role does not exist.',
            ], 422);
        }

        $isAutoApproved = in_array($roleName, ['WORKER', 'OFFICER']);

        \DB::beginTransaction();
        try {
            $user = User::create([
                'uuid'                => (string) Str::uuid(),
                'full_name'           => $validated['full_name'],
                'email'               => $validated['email'] ?? null,
                'phone'               => $validated['phone'],
                'aadhar_number'       => $validated['aadhar_number'] ?? null,
                'pan_number'          => $validated['pan_number'] ?? null,
                'password_hash'       => Hash::make($validated['password']),
                'status'              => $isAutoApproved ? UserStatus::ACTIVE->value : UserStatus::PENDING->value,
                'registration_source' => RegistrationSource::SELF_REGISTRATION->value,
                'approved_at'         => $isAutoApproved ? now() : null,
            ]);

            // Assign role — prevent duplicate via syncWithoutDetaching
            $user->roles()->syncWithoutDetaching([$role->id]);

            // If Worker, ensure worker_profiles row exists with aadhar and pan
            if ($roleName === 'WORKER') {
                \App\Models\WorkerProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'aadhar_number' => $validated['aadhar_number'] ?? null,
                        'pan_number'    => $validated['pan_number'] ?? null,
                    ]
                );
            }

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        // Auto login for workers/officers upon registration
        if ($isAutoApproved) {
            $token = $user->createToken('api-token')->plainTextToken;
            return response()->json([
                'message' => "{$roleName} registration successful.",
                'token'   => $token,
                'user'    => $this->safeUser($user),
            ], 201);
        }

        return response()->json([
            'message' => 'Registration submitted successfully. Your account is pending admin approval.',
        ], 201);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/login
    // -------------------------------------------------------------------------

    public function login(LoginRequest $request): JsonResponse
    {
        $loginValue = trim((string)$request->input('login'));
        if (str_contains($loginValue, '#') && !str_contains($loginValue, '@')) {
            $loginValue = str_replace('#', '@', $loginValue);
        }

        // Admins are stored in their own table and sign in by email.
        if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
            $admin = Admin::where('email', $loginValue)->first();

            // Only accept the dedicated admin record when its stored hash is
            // valid. A malformed legacy row must not break login; continue to
            // the legacy role-based users table below.
            if ($admin && $this->passwordMatches($request->input('password'), $admin->password)) {
                $token = $admin->createToken('api-token')->plainTextToken;

                AuditService::log(
                    action: 'ADMIN_LOGIN',
                    entityType: 'admins',
                    entityId: (string) $admin->id,
                    request: $request,
                );

                return response()->json([
                    'message' => 'Login successful.',
                    'token' => $token,
                    'user' => $this->safeAdmin($admin),
                ]);
            }
        }

        // Determine whether the value looks like an email or a phone number.
        $field = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $loginValue)->first();

        if (! $user || ! $this->passwordMatches($request->input('password'), $user->password_hash)) {
            return response()->json([
                'message' => 'These credentials do not match our records.',
            ], 401);
        }

        // Status gate — only ACTIVE users may receive a token.
        match ($user->status) {
            UserStatus::PENDING->value   => throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                403,
                'Your account is pending admin approval. Please wait for approval before logging in.'
            ),
            UserStatus::REJECTED->value  => throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                403,
                'Your account registration has been rejected. Please contact an administrator.'
            ),
            UserStatus::INACTIVE->value  => throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                403,
                'Your account is inactive. Please contact an administrator.'
            ),
            UserStatus::SUSPENDED->value => throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                403,
                'Your account has been suspended. Please contact an administrator.'
            ),
            default => null, // ACTIVE — continue
        };

        // Update last login timestamp.
        $user->update(['last_login_at' => now()]);

        // Issue Sanctum token.
        $token = $user->createToken('api-token')->plainTextToken;

        AuditService::log(
            action:      'USER_LOGIN',
            userId:      $user->id,
            entityType:  'users',
            entityId:    (string) $user->id,
            request:     $request,
        );

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => $this->safeUser($user),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/dev-login
    // Developer bypass login without password for rapid testing
    // -------------------------------------------------------------------------

    public function devLogin(Request $request): JsonResponse
    {
        $role = strtoupper($request->input('role', 'ADMIN'));

        if ($role === 'ADMIN') {
            $admin = Admin::first();
            if (! $admin) {
                $admin = Admin::create([
                    'name' => 'Super Administrator',
                    'email' => 'admin@gmail.com',
                    'password' => Hash::make('admin123'),
                ]);
            }
            $token = $admin->createToken('api-token')->plainTextToken;

            return response()->json([
                'message' => 'Developer login successful as Admin.',
                'token' => $token,
                'user' => $this->safeAdmin($admin),
            ]);
        }

        // For OFFICER or WORKER
        $roleModel = Role::where('name', $role)->first();
        if (! $roleModel) {
            return response()->json(['message' => "Role {$role} not found in database."], 404);
        }

        $user = User::whereHas('roles', fn ($q) => $q->where('name', $role))
            ->where('status', UserStatus::ACTIVE->value)
            ->first();

        if (! $user) {
            $user = User::whereHas('roles', fn ($q) => $q->where('name', $role))->first();
            if ($user) {
                $user->update(['status' => UserStatus::ACTIVE->value]);
            } else {
                $user = User::create([
                    'uuid'                => (string) Str::uuid(),
                    'full_name'           => $role === 'OFFICER' ? 'Dev Field Officer' : 'Dev Field Worker',
                    'email'               => strtolower($role) . '.dev@field.in',
                    'phone'               => $role === 'OFFICER' ? '9822011231' : '9822011221',
                    'password_hash'       => Hash::make('Password@123'),
                    'status'              => UserStatus::ACTIVE->value,
                    'registration_source' => RegistrationSource::ADMIN_CREATED->value,
                    'approved_at'         => now(),
                ]);
                $user->roles()->syncWithoutDetaching([$roleModel->id]);
            }
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => "Developer login successful as {$role}.",
            'token'   => $token,
            'user'    => $this->safeUser($user),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/send-otp
    // Dove SMS Gateway Integration (Expertskill Technology)
    // -------------------------------------------------------------------------

    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $rawPhone = $request->input('phone');
        $cleanDigits = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($cleanDigits) < 10) {
            return response()->json(['message' => 'Valid 10-digit phone number is required.'], 422);
        }
        $phone = substr($cleanDigits, -10);

        $bypassPhone = '9898989898';

        // 1. Bypass number logic (matching your Android app)
        if ($phone === $bypassPhone) {
            $otp = '123456';
            Cache::put('otp_' . $phone, $otp, now()->addMinutes(10));
            return response()->json([
                'message'    => 'Bypass phone number detected. OTP: 123456',
                'otp'        => $otp,
                'is_bypass'  => true,
                'expires_in' => 600,
            ]);
        }

        // 2. Generate 6-digit random verification code
        $otp = (string) random_int(100000, 999999);
        Cache::put('otp_' . $phone, $otp, now()->addMinutes(10));

        // 3. Dove SMS Gateway API Credentials
        $username = env('DOVE_SMS_USER', 'Experts');
        $authkey  = env('DOVE_SMS_KEY', 'ba9dcdcdfcXX');
        $senderId = env('DOVE_SMS_SENDER_ID', 'EXTSKL');
        $accusage = env('DOVE_SMS_ACCUSAGE', '1');
        $message  = "Your Verification Code for login is {$otp}. - Expertskill Technology.";

        try {
            $url = 'https://mobicomm.dove-sms.com//submitsms.jsp';
            $response = Http::withoutVerifying()->timeout(10)->get($url, [
                'user'     => $username,
                'key'      => $authkey,
                'mobile'   => '+91' . $phone,
                'message'  => $message,
                'accusage' => $accusage,
                'senderid' => $senderId,
            ]);

            $body = $response->body();
            Log::info("Dove SMS API response for +91{$phone}: {$body}");

            $isSent = str_contains(strtolower($body), 'success');

            if ($isSent) {
                return response()->json([
                    'message'        => "OTP sent successfully to +91 {$phone} via SMS.",
                    'sms_delivered'  => true,
                    'gateway_status' => $body,
                    'expires_in'     => 600,
                ]);
            }

            return response()->json([
                'message'        => "Failed to send SMS to +91 {$phone}. Gateway error: {$body}",
                'sms_delivered'  => false,
                'gateway_status' => $body,
            ], 502);
        } catch (\Throwable $e) {
            Log::error("Dove SMS API network error for +91{$phone}: " . $e->getMessage());

            return response()->json([
                'message'       => "SMS Gateway connection error. Please try again.",
                'sms_delivered' => false,
                'error'         => $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/verify-otp
    // -------------------------------------------------------------------------

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp'   => ['required', 'string'],
        ]);

        $rawPhone = $request->input('phone');
        $phone = substr(preg_replace('/[^0-9]/', '', $rawPhone), -10);
        $otp = trim($request->input('otp'));
        $cachedOtp = Cache::get('otp_' . $phone);

        // Verification logic:
        // - Matches real generated OTP in Cache
        // - OR bypass number 9898989898 (allows 123456 or cached)
        // - OR dev environment 123456
        $isBypass = ($phone === '9898989898');
        $isValid = ($cachedOtp && $otp === $cachedOtp) || ($isBypass && ($otp === '123456' || !empty($otp)));

        if (! $isValid && ! app()->environment('production') && $otp === '123456') {
            $isValid = true;
        }

        if (! $isValid) {
            return response()->json([
                'message' => 'Invalid or expired OTP. Please try again.',
            ], 422);
        }

        // Invalidate OTP once verified
        Cache::forget('otp_' . $phone);

        // Check if user exists with this phone
        $user = User::where('phone', $phone)->first();
        if ($user) {
            if ($user->status !== UserStatus::ACTIVE->value) {
                $user->update(['status' => UserStatus::ACTIVE->value]);
            }
            $user->update(['last_login_at' => now()]);
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'message'          => 'OTP verified and login successful.',
                'verified'         => true,
                'is_existing_user' => true,
                'token'            => $token,
                'user'             => $this->safeUser($user),
            ]);
        }

        return response()->json([
            'message'          => 'OTP verified successfully.',
            'verified'         => true,
            'is_existing_user' => false,
            'phone'            => $phone,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/logout
    // -------------------------------------------------------------------------

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    // -------------------------------------------------------------------------
    // GET /api/auth/me
    // -------------------------------------------------------------------------

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user instanceof Admin
                ? $this->safeAdmin($user)
                : $this->safeUser($user),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return safe user data — never includes password_hash.
     */
    private function safeUser(User $user): array
    {
        $user->load('roles');

        return [
            'id'                  => $user->id,
            'uuid'                => $user->uuid,
            'full_name'           => $user->full_name,
            'email'               => $user->email,
            'phone'               => $user->phone,
            'aadhar_number'       => $user->aadhar_number,
            'pan_number'          => $user->pan_number,
            'status'              => $user->status,
            'registration_source' => $user->registration_source,
            'last_login_at'       => $user->last_login_at?->toISOString(),
            'created_at'          => $user->created_at?->toISOString(),
            'roles'               => $user->roles->pluck('name'),
        ];
    }

    /** Return the admin using the profile shape expected by the frontend. */
    private function safeAdmin(Admin $admin): array
    {
        return [
            'id' => $admin->id,
            'uuid' => null,
            'full_name' => $admin->name,
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => null,
            'status' => 'ACTIVE',
            'registration_source' => 'ADMIN_CREATED',
            'last_login_at' => null,
            'created_at' => $admin->created_at?->toISOString(),
            'roles' => ['ADMIN'],
        ];
    }

    /** Invalid or incompatible stored hashes should fail authentication, not 500. */
    private function passwordMatches(string $plainPassword, ?string $storedHash): bool
    {
        if (! $storedHash) {
            return false;
        }

        try {
            return Hash::check($plainPassword, $storedHash);
        } catch (\RuntimeException) {
            return false;
        }
    }
}
