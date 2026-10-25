<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AuditLog — maps to the audit_logs table.
 *
 * Actual columns (from DESCRIBE audit_logs):
 *   id, user_id, action, entity_type, entity_id,
 *   old_data (json), new_data (json), ip_address, user_agent, created_at
 *
 * No updated_at column exists in this table.
 */
class AuditLog extends Model
{
    protected $table = 'audit_logs';

    /**
     * audit_logs has no updated_at column — only created_at.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_data'   => 'array',
            'new_data'   => 'array',
            'created_at' => 'datetime',
        ];
    }
}
