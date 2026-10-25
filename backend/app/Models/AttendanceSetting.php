<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    use HasFactory;

    protected $table = 'attendance_settings';

    protected $fillable = [
        'is_location_required',
        'allow_anywhere',
        'location_name',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'enforce_strict_geofence',
        'updated_by_name',
    ];

    protected $casts = [
        'is_location_required' => 'boolean',
        'allow_anywhere' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
        'enforce_strict_geofence' => 'boolean',
    ];
}
