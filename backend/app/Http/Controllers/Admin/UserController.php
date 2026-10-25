<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationSource;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\RejectUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /api/admin/users/pending
    // -------------------------------------------------------------------------

    public function pending(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->where('status', UserStatus::PENDING->value)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn (User $u) => $this->safeUser($u));

        return response()->json([
            'data'  => $users,
            'total' => $users->count(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/admin/users/{id}/approve
    // -------------------------------------------------------------------------

    public function approve(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->status !== UserStatus::PENDING->value) {
            return response()->json([
                'message' => 'Only PENDING accounts can be approved.',
            ], 409);
        }

        $admin = $request->user();

        \DB::beginTransaction();
        try {
            $user->update([
                'status'           => UserStatus::ACTIVE->value,
                'approved_by'      => $admin->id,
                'approved_at'      => now(),
                'rejection_reason' => null,
            ]);

            AuditService::log(
                action:     'USER_APPROVED',
                userId:     $admin->id,
                entityType: 'users',
                entityId:   (string) $user->id,
                oldData:    ['status' => UserStatus::PENDING->value],
                newData:    ['status' => UserStatus::ACTIVE->value, 'approved_by' => $admin->id],
                request:    $request,
            );

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        $user->load('roles');

        return response()->json([
            'message' => 'User approved successfully.',
            'user'    => $this->safeUser($user),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/admin/users/{id}/reject
    // -------------------------------------------------------------------------

    public function reject(RejectUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->status !== UserStatus::PENDING->value) {
            return response()->json([
                'message' => 'Only PENDING accounts can be rejected.',
            ], 409);
        }

        $admin = $request->user();

        \DB::beginTransaction();
        try {
            $user->update([
                'status'           => UserStatus::REJECTED->value,
                'approved_by'      => null,
                'approved_at'      => null,
                'rejection_reason' => $request->validated()['rejection_reason'],
            ]);

            AuditService::log(
                action:     'USER_REJECTED',
                userId:     $admin->id,
                entityType: 'users',
                entityId:   (string) $user->id,
                oldData:    ['status' => UserStatus::PENDING->value],
                newData:    [
                    'status'           => UserStatus::REJECTED->value,
                    'rejection_reason' => $request->validated()['rejection_reason'],
                ],
                request:    $request,
            );

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        $user->load('roles');

        return response()->json([
            'message' => 'User rejected.',
            'user'    => $this->safeUser($user),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/admin/users
    // -------------------------------------------------------------------------

    public function store(CreateUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $role = Role::where('name', $validated['role'])->first();

        if (! $role) {
            return response()->json([
                'message' => 'The requested role does not exist.',
            ], 422);
        }

        $admin = $request->user();

        \DB::beginTransaction();
        try {
            $user = User::create([
                'uuid'                => (string) Str::uuid(),
                'full_name'           => $validated['full_name'],
                'email'               => $validated['email'] ?? null,
                'phone'               => $validated['phone'],
                'password_hash'       => Hash::make($validated['password']),
                'status'              => UserStatus::ACTIVE->value,
                'registration_source' => RegistrationSource::ADMIN_CREATED->value,
                'approved_by'         => $admin->id,
                'approved_at'         => now(),
            ]);

            $user->roles()->syncWithoutDetaching([$role->id]);

            AuditService::log(
                action:     'ADMIN_CREATED_USER',
                userId:     $admin->id,
                entityType: 'users',
                entityId:   (string) $user->id,
                newData:    [
                    'full_name' => $user->full_name,
                    'role'      => $validated['role'],
                    'status'    => UserStatus::ACTIVE->value,
                ],
                request:    $request,
            );

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        $user->load('roles');

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => $this->safeUser($user),
        ], 201);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return safe user data — never includes password_hash.
     */
    private function safeUser(User $user): array
    {
        return [
            'id'                  => $user->id,
            'uuid'                => $user->uuid,
            'full_name'           => $user->full_name,
            'email'               => $user->email,
            'phone'               => $user->phone,
            'status'              => $user->status,
            'registration_source' => $user->registration_source,
            'approved_by'         => $user->approved_by,
            'approved_at'         => $user->approved_at?->toISOString(),
            'rejection_reason'    => $user->rejection_reason,
            'created_at'          => $user->created_at?->toISOString(),
            'roles'               => $user->roles->pluck('name'),
        ];
    }
}
