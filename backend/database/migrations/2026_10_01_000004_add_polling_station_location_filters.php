<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polling_stations', function (Blueprint $table) {
            if (! Schema::hasColumn('polling_stations', 'taluka_code')) {
                $table->string('taluka_code', 30)->nullable()->after('district_code');
            }
            if (! Schema::hasColumn('polling_stations', 'village_code')) {
                $table->string('village_code', 30)->nullable()->after('taluka_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('polling_stations', function (Blueprint $table) {
            foreach (['taluka_code', 'village_code'] as $column) {
                if (Schema::hasColumn('polling_stations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
