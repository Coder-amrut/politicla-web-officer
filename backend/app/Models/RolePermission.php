<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RolePermission — pivot table role_permissions.
 * Columns: role_id, permission_id (composite PK, no timestamps)
 */
class RolePermission extends Model
{
    protected $table = 'role_permissions';

    public $incrementing = false;
    public $timestamps   = false;

    protected $fillable = [
        'role_id',
        'permission_id',
    ];
}
