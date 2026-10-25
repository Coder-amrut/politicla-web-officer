<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Permission — maps to the permissions table.
 *
 * Actual columns: id, name, description
 * No updated_at column.
 */
class Permission extends Model
{
    protected $table = 'permissions';

    public const UPDATED_AT = null;

    // permissions table has no updated_at; created_at only via DEFAULT
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];
}
