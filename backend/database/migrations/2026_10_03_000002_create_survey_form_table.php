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
        if (! Schema::hasTable('survey_form')) {
            Schema::create('survey_form', function (Blueprint $table) {
                $table->id();
                $table->string('scope', 50)->index(); // 'DISTRICT', 'LOK_SABHA', 'ASSEMBLY', 'POLLING_STATION', 'GRADUATE_CONSTITUENCY', 'TEACHER_CONSTITUENCY'
                $table->string('record_type', 50)->default('TEMPLATE')->index(); // 'TEMPLATE', 'CUSTOM_FORM', 'DRAFT', 'SUBMISSION'
                $table->string('form_code', 100)->nullable()->index(); // Unique ID / Draft ID / Survey ID
                $table->string('title_en', 255)->nullable();
                $table->string('title_mr', 255)->nullable();
                $table->string('campaign_objective', 100)->nullable();
                $table->string('target_audience', 100)->nullable();
                $table->json('specialized_title')->nullable();
                $table->json('modules')->nullable();
                $table->json('specialized_questions')->nullable();
                $table->json('custom_questions')->nullable();
                $table->json('issues')->nullable();
                $table->json('communication_channels')->nullable();
                $table->longText('form_data')->nullable(); // Complete JSON payload for 1:1 roundtrip
                $table->string('status', 50)->default('ACTIVE')->index();
                $table->string('field_worker_name', 150)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_form');
    }
};
