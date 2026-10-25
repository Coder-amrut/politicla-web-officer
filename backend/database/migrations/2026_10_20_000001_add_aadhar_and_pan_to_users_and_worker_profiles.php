<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('worker_profiles')) {
            Schema::table('worker_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('worker_profiles', 'aadhar_number')) {
                    $table->string('aadhar_number', 25)->nullable()->after('phone_override');
                }
                if (!Schema::hasColumn('worker_profiles', 'pan_number')) {
                    $table->string('pan_number', 20)->nullable()->after('aadhar_number');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'aadhar_number')) {
                    $table->string('aadhar_number', 25)->nullable()->after('phone');
                }
                if (!Schema::hasColumn('users', 'pan_number')) {
                    $table->string('pan_number', 20)->nullable()->after('aadhar_number');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('worker_profiles')) {
            Schema::table('worker_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('worker_profiles', 'pan_number')) {
                    $table->dropColumn('pan_number');
                }
                if (Schema::hasColumn('worker_profiles', 'aadhar_number')) {
                    $table->dropColumn('aadhar_number');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'pan_number')) {
                    $table->dropColumn('pan_number');
                }
                if (Schema::hasColumn('users', 'aadhar_number')) {
                    $table->dropColumn('aadhar_number');
                }
            });
        }
    }
};
