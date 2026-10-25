<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAttendance extends Model
{
    use HasFactory;

    protected $table = 'user_attendances';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'date',
        'punch_in_time',
        'punch_out_time',
        'status',
        'punch_in_photo',
        'punch_out_photo',
        'latitude',
        'longitude',
        'address',
        'punch_out_latitude',
        'punch_out_longitude',
        'punch_out_address',
        'location_name',
        'distance_meters',
        'compliance_status',
        'device_info',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'latitude' => 'float',
        'longitude' => 'float',
        'punch_out_latitude' => 'float',
        'punch_out_longitude' => 'float',
        'distance_meters' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
