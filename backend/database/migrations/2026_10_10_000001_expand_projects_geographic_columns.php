<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('geographic_code')->nullable()->change();
            $table->text('geographic_name_en')->nullable()->change();
            $table->text('geographic_name_mr')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('geographic_code', 100)->nullable()->change();
            $table->string('geographic_name_en', 255)->nullable()->change();
            $table->string('geographic_name_mr', 255)->nullable()->change();
        });
    }
};
