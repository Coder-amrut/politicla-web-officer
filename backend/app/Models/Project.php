<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /**
     * Columns that are mass-assignable.
     * NOTE: `uuid` is set once at creation only; the controller must pass it explicitly.
     */
    protected $fillable = [
        'uuid',
        'name',
        'project_type',
        'description',
        'status',
        'start_date',
        'end_date',
        'created_by',
        'assigned_member_count',
        'assigned_member_name',
        'geographic_scope',
        'geographic_code',
        'geographic_name_en',
        'geographic_name_mr',
        'geographic_context',
    ];

    /**
     * Type casts — status is backed by the ProjectStatus enum.
     * Dates are cast to Carbon instances for consistent formatting.
     */
    protected $casts = [
        'status'     => ProjectStatus::class,
        'start_date' => 'date',
        'end_date'   => 'date',
        'geographic_context' => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The admin/user who created this project.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberAssignments(): HasMany
    {
        return $this->hasMany(UserProjectAssignment::class, 'project_id');
    }
}
