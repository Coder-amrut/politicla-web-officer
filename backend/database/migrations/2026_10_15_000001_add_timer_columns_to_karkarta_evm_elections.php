<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('karkarta_evm_elections')) {
            Schema::table('karkarta_evm_elections', function (Blueprint $table) {
                if (!Schema::hasColumn('karkarta_evm_elections', 'start_time')) {
                    $table->timestamp('start_time')->nullable();
                }
                if (!Schema::hasColumn('karkarta_evm_elections', 'end_time')) {
                    $table->timestamp('end_time')->nullable();
                }
                if (!Schema::hasColumn('karkarta_evm_elections', 'timer_duration_seconds')) {
                    $table->unsignedInteger('timer_duration_seconds')->nullable()->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('karkarta_evm_elections')) {
            Schema::table('karkarta_evm_elections', function (Blueprint $table) {
                $table->dropColumn(['start_time', 'end_time', 'timer_duration_seconds']);
            });
        }
    }
};
