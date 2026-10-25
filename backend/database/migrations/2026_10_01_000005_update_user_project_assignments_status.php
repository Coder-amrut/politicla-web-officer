<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_project_assignments')) {
            DB::statement("ALTER TABLE user_project_assignments MODIFY status VARCHAR(30) DEFAULT 'ASSIGNED'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_project_assignments')) {
            DB::statement("ALTER TABLE user_project_assignments MODIFY status ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE'");
        }
    }
};
