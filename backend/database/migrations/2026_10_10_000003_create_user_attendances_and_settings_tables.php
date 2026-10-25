<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 1. user_attendances: User attendance records with address, geo-coordinates, photos, and compliance.
     * 2. attendance_settings: Admin designated attendance location, geofence radius, and anywhere toggle.
     */
    public function up(): void
    {
        // 1. attendance_settings — अ‍ॅडमिन उपस्थिती स्थान सेटिंग्ज (Admin location restriction settings)
        if (!Schema::hasTable('attendance_settings')) {
            Schema::create('attendance_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_location_required')->default(false); // If true, all users must go to this place
                $table->boolean('allow_anywhere')->default(true); // If true (or no location set), users can attend from anywhere
                $table->string('location_name')->nullable(); // e.g., 'Sangli Election Command HQ'
                $table->text('address')->nullable(); // Address set by admin
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->integer('radius_meters')->default(500); // Geofence radius in meters
                $table->boolean('enforce_strict_geofence')->default(true); // Block punch if outside radius
                $table->string('updated_by_name')->nullable()->default('Admin HQ');
                $table->timestamps();
            });
        }

        // 2. user_attendances — वापरकर्ते उपस्थिती तपशील व पत्ता (User Attendance with Address)
        if (!Schema::hasTable('user_attendances')) {
            Schema::create('user_attendances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('user_name')->nullable();
                $table->string('user_role')->nullable()->default('WORKER'); // WORKER, OFFICER, ADMIN
                $table->date('date')->index(); // YYYY-MM-DD
                $table->string('punch_in_time')->nullable(); // e.g., '09:30 AM'
                $table->string('punch_out_time')->nullable(); // e.g., '06:15 PM'
                $table->string('status')->default('Present'); // Present, In-Progress, Completed, Half-Day, Absent
                $table->longText('punch_in_photo')->nullable(); // base64 webcam / camera photo
                $table->longText('punch_out_photo')->nullable(); // base64 photo
                $table->decimal('latitude', 10, 7)->nullable(); // Punch In latitude
                $table->decimal('longitude', 10, 7)->nullable(); // Punch In longitude
                $table->text('address')->nullable(); // Address of user during attendance
                $table->decimal('punch_out_latitude', 10, 7)->nullable();
                $table->decimal('punch_out_longitude', 10, 7)->nullable();
                $table->text('punch_out_address')->nullable();
                $table->string('location_name')->nullable(); // Name of location/premise
                $table->double('distance_meters', 10, 2)->nullable(); // Distance from admin set location
                $table->string('compliance_status')->default('ANYWHERE_ALLOWED'); // 'AT_REQUIRED_LOCATION', 'ANYWHERE_ALLOWED', 'OUTSIDE_RADIUS'
                $table->string('device_info')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_attendances');
        Schema::dropIfExists('attendance_settings');
    }
};
