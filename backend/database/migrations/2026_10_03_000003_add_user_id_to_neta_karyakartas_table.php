<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('neta_karyakartas') && !Schema::hasColumn('neta_karyakartas', 'user_id')) {
            Schema::table('neta_karyakartas', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('neta_karyakartas') && Schema::hasColumn('neta_karyakartas', 'user_id')) {
            Schema::table('neta_karyakartas', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
