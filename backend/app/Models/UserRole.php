<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * UserRole — pivot table user_roles.
 * Columns: user_id, role_id, created_at (composite PK, no auto-increment id)
 */
class UserRole extends Model
{
    protected $table = 'user_roles';

    public $incrementing = false;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'role_id',
    ];
}
