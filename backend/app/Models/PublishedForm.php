<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PublishedForm extends Model
{
    protected $table = 'published_forms';

    protected $fillable = [
        'uuid',
        'project_id',
        'project_uuid',
        'project_name',
        'form_title',
        'form_title_mr',
        'geographic_scope',
        'default_fields',
        'custom_questions',
        'form_format',
        'status',
        'version',
        'assigned_users_count',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'default_fields'       => 'array',
        'custom_questions'     => 'array',
        'form_format'          => 'array',
        'version'              => 'integer',
        'assigned_users_count' => 'integer',
        'published_at'         => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The assigned workers/users who have access to this published form.
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'published_form_users', 'published_form_id', 'user_id')
            ->withPivot(['id', 'role', 'status', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    /**
     * Direct relationship to pivot records.
     */
    public function userAssignments(): HasMany
    {
        return $this->hasMany(PublishedFormUser::class, 'published_form_id');
    }

    /**
     * Associated Project (optional).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * User who published the form.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
