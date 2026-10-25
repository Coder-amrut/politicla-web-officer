<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'uuid',
        'full_name',
        'email',
        'phone',
        'aadhar_number',
        'pan_number',
        'password_hash',
        'profile_photo',
        'status',
        'registration_source',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'approved_at'   => 'datetime',
            'last_login_at' => 'datetime',
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
        ];
    }

    /**
     * Laravel authentication uses password_hash instead of the default password column.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Check whether this user has a given role (by name).
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Convenience helper.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::ADMIN->value);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Roles assigned to this user (via user_roles pivot).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        );
    }

    /**
     * Direct individual permissions assigned to this user (via user_permissions pivot).
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions',
            'user_id',
            'permission_id'
        )->withPivot('created_at');
    }

    /**
     * All effective permissions (role-inherited + direct user permissions).
     */
    public function allPermissions()
    {
        $rolePermissions = $this->roles()->with('permissions')->get()->flatMap->permissions;
        $direct = $this->directPermissions;
        return $rolePermissions->merge($direct)->unique('id');
    }

    /**
     * Check if user has a specific permission (either directly or via role).
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->allPermissions()->contains('name', $permissionName);
    }

    /**
     * User who approved this account.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Users approved by this user.
     */
    public function approvedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'approved_by');
    }

    /**
     * Worker profile.
     */
    public function workerProfile(): HasOne
    {
        return $this->hasOne(WorkerProfile::class, 'user_id');
    }

    /**
     * Officer profile.
     */
    public function officerProfile(): HasOne
    {
        return $this->hasOne(OfficerProfile::class, 'user_id');
    }

    /**
     * Project assignments.
     */
    public function projectAssignments(): HasMany
    {
        return $this->hasMany(UserProjectAssignment::class, 'user_id');
    }

    /**
     * Geography assignments.
     */
    public function geographyAssignments(): HasMany
    {
        return $this->hasMany(UserGeographyAssignment::class, 'user_id');
    }

    /**
     * Attendance events.
     */
    public function attendanceEvents(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class, 'user_id');
    }

    /**
     * User attendances records with address and geolocation.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(UserAttendance::class, 'user_id');
    }

    /**
     * Audit logs created by or about this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    /**
     * Sync operations.
     */
    public function syncOperations(): HasMany
    {
        return $this->hasMany(SyncOperation::class, 'user_id');
    }

    /**
     * Published survey forms assigned to this user.
     */
    public function publishedForms(): BelongsToMany
    {
        return $this->belongsToMany(PublishedForm::class, 'published_form_users', 'user_id', 'published_form_id')
            ->withPivot(['id', 'role', 'status', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }
}