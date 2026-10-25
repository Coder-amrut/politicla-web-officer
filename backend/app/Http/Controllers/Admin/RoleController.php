<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * GET /api/admin/roles
     * List all roles with their assigned permissions and user counts.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with([
            'permissions:id,name,description',
            'users:id,full_name,email,phone',
        ])
            ->withCount('users')
            ->orderBy('id')
            ->get(['id', 'name', 'display_name', 'description']);

        return response()->json([
            'data' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'users_count' => $role->users_count,
                'users' => $role->users->map(fn ($u) => [
                    'id' => $u->id,
                    'full_name' => $u->full_name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                ]),
                'permissions' => $role->permissions->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description,
                ]),
            ]),
        ]);
    }

    /**
     * GET /api/admin/permissions
     * List all available permissions in the system.
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::orderBy('id')->get(['id', 'name', 'description']);

        return response()->json([
            'data' => $permissions->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'module' => explode('_', $p->name)[0] ?? 'general',
            ]),
        ]);
    }

    /**
     * GET /api/admin/permissions/{permissionId}/users
     * Returns all persons who have this permission (either via role or directly).
     */
    public function permissionUsers(int|string $permissionId): JsonResponse
    {
        $permission = Permission::findOrFail($permissionId);

        // Roles that have this permission
        $roleIds = \Illuminate\Support\Facades\DB::table('role_permissions')
            ->where('permission_id', $permissionId)
            ->pluck('role_id');

        // Users via role
        $roleUsers = User::whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds))
            ->with('roles:id,name,display_name')
            ->get(['id', 'full_name', 'email', 'phone']);

        // Users via direct permission
        $directUsers = User::whereHas('directPermissions', fn ($q) => $q->where('permissions.id', $permissionId))
            ->with('roles:id,name,display_name')
            ->get(['id', 'full_name', 'email', 'phone']);

        return response()->json([
            'data' => [
                'permission' => $permission,
                'role_users' => $roleUsers,
                'direct_users' => $directUsers,
                'all_users' => $roleUsers->merge($directUsers)->unique('id')->values(),
            ],
        ]);
    }

    /**
     * PUT /api/admin/roles/{roleId}/permissions
     * Replace / synchronize all permissions for a specific role.
     */
    public function syncPermissions(Request $request, int|string $roleId): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        $validated = $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $role->permissions()->sync($validated['permission_ids']);
        $role->load('permissions:id,name,description');

        $newPermissions = $role->permissions->pluck('name')->toArray();

        AuditService::log(
            action: 'ROLE_PERMISSIONS_UPDATED',
            userId: $request->user()?->id,
            entityType: 'roles',
            entityId: (string) $role->id,
            oldData: ['permissions' => $oldPermissions],
            newData: ['permissions' => $newPermissions],
            request: $request,
        );

        return response()->json([
            'message' => "Permissions for role '{$role->name}' updated successfully.",
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'permissions' => $role->permissions->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                ]),
            ],
        ]);
    }

    /**
     * POST /api/admin/roles/{roleId}/permissions/give
     * Give (grant) one or more permissions to a role.
     */
    public function givePermission(Request $request, int|string $roleId): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        $validated = $request->validate([
            'permission_id' => ['required_without:permission_ids', 'integer', 'exists:permissions,id'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $idsToGive = $validated['permission_ids'] ?? [$validated['permission_id']];

        $role->permissions()->syncWithoutDetaching($idsToGive);
        $role->load('permissions:id,name,description');

        $given = Permission::whereIn('id', $idsToGive)->pluck('name')->toArray();

        AuditService::log(
            action: 'ROLE_PERMISSION_GRANTED',
            userId: $request->user()?->id,
            entityType: 'roles',
            entityId: (string) $role->id,
            newData: ['granted_permissions' => $given],
            request: $request,
        );

        return response()->json([
            'message' => 'Permission(s) granted successfully.',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                ]),
            ],
        ]);
    }

    /**
     * DELETE /api/admin/roles/{roleId}/permissions/{permissionId}
     * Remove (revoke) a specific permission from a role.
     */
    public function removePermission(Request $request, int|string $roleId, int|string $permissionId): JsonResponse
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        $role->permissions()->detach($permissionId);
        $role->load('permissions:id,name,description');

        AuditService::log(
            action: 'ROLE_PERMISSION_REVOKED',
            userId: $request->user()?->id,
            entityType: 'roles',
            entityId: (string) $role->id,
            oldData: ['revoked_permission' => $permission->name],
            request: $request,
        );

        return response()->json([
            'message' => "Permission '{$permission->name}' removed from role '{$role->name}'.",
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                ]),
            ],
        ]);
    }

    /**
     * GET /api/admin/users-list
     * List all users with their roles and direct permission counts for individual management.
     */
    public function usersList(): JsonResponse
    {
        $users = User::with([
            'roles:id,name,display_name',
            'directPermissions:id,name',
        ])
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email', 'phone', 'status']);

        return response()->json([
            'data' => $users->map(fn ($u) => [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'email' => $u->email,
                'phone' => $u->phone,
                'status' => $u->status,
                'roles' => $u->roles->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'display_name' => $r->display_name,
                ]),
                'direct_permissions_count' => $u->directPermissions->count(),
            ]),
        ]);
    }

    /**
     * GET /api/admin/users/{userId}/permissions
     * Get a specific person's inherited role permissions, direct permissions, and combined effective permissions.
     */
    public function getUserPermissions(int|string $userId): JsonResponse
    {
        $user = User::with([
            'roles.permissions:id,name,description',
            'directPermissions:id,name,description',
        ])->findOrFail($userId);

        $rolePermissions = $user->roles->flatMap->permissions->unique('id')->values();
        $directPermissions = $user->directPermissions;
        $effectivePermissions = $rolePermissions->merge($directPermissions)->unique('id')->values();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->roles->map(fn ($r) => [
                        'id' => $r->id,
                        'name' => $r->name,
                        'display_name' => $r->display_name,
                    ]),
                ],
                'role_permissions' => $rolePermissions,
                'direct_permissions' => $directPermissions,
                'effective_permissions' => $effectivePermissions,
            ],
        ]);
    }

    /**
     * PUT /api/admin/users/{userId}/permissions
     * Sync / replace all direct permissions for a specific person.
     */
    public function syncUserPermissions(Request $request, int|string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $oldPermissions = $user->directPermissions->pluck('name')->toArray();
        $user->directPermissions()->sync($validated['permission_ids']);
        $user->load('directPermissions:id,name,description');

        $newPermissions = $user->directPermissions->pluck('name')->toArray();

        AuditService::log(
            action: 'USER_PERMISSIONS_UPDATED',
            userId: $request->user()?->id,
            entityType: 'users',
            entityId: (string) $user->id,
            oldData: ['direct_permissions' => $oldPermissions],
            newData: ['direct_permissions' => $newPermissions],
            request: $request,
        );

        return response()->json([
            'message' => "Permissions for {$user->full_name} updated successfully.",
            'data' => [
                'user_id' => $user->id,
                'direct_permissions' => $user->directPermissions,
            ],
        ]);
    }

    /**
     * POST /api/admin/users/{userId}/permissions/give
     * Give (grant) a specific permission directly to a particular person.
     */
    public function giveUserPermission(Request $request, int|string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'permission_id' => ['required_without:permission_ids', 'integer', 'exists:permissions,id'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $idsToGive = $validated['permission_ids'] ?? [$validated['permission_id']];

        $user->directPermissions()->syncWithoutDetaching($idsToGive);
        $user->load('directPermissions:id,name,description');

        $given = Permission::whereIn('id', $idsToGive)->pluck('name')->toArray();

        AuditService::log(
            action: 'USER_PERMISSION_GRANTED',
            userId: $request->user()?->id,
            entityType: 'users',
            entityId: (string) $user->id,
            newData: ['granted_permissions' => $given],
            request: $request,
        );

        return response()->json([
            'message' => "Permission(s) granted to {$user->full_name} successfully.",
            'data' => [
                'user_id' => $user->id,
                'direct_permissions' => $user->directPermissions,
            ],
        ]);
    }

    /**
     * DELETE /api/admin/users/{userId}/permissions/{permissionId}
     * Remove (revoke) a direct permission from a particular person.
     */
    public function removeUserPermission(Request $request, int|string $userId, int|string $permissionId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $permission = Permission::findOrFail($permissionId);

        $user->directPermissions()->detach($permissionId);
        $user->load('directPermissions:id,name,description');

        AuditService::log(
            action: 'USER_PERMISSION_REVOKED',
            userId: $request->user()?->id,
            entityType: 'users',
            entityId: (string) $user->id,
            oldData: ['revoked_permission' => $permission->name],
            request: $request,
        );

        return response()->json([
            'message' => "Permission '{$permission->name}' removed from {$user->full_name}.",
            'data' => [
                'user_id' => $user->id,
                'direct_permissions' => $user->directPermissions,
            ],
        ]);
    }
}
