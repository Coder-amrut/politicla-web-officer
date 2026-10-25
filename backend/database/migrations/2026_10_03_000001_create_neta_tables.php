<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates all neta_ prefix tables for Officer/Neta command platform.
     */
    public function up(): void
    {
        // 1. neta_area_profiles — संपूर्ण क्षेत्राचा Dashboard Profile
        Schema::create('neta_area_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name_mr');
            $table->string('name_en');
            $table->string('district_mr')->default('सांगली');
            $table->string('district_en')->default('Sangli');
            $table->string('lok_sabha_mr')->default('४४ - सांगली लोकसभा');
            $table->string('lok_sabha_en')->default('44 - Sangli Lok Sabha');
            $table->string('neta_title_mr')->default('मा. आमदार / जनसेवक संपर्क कार्यालय');
            $table->string('neta_title_en')->default('Hon. MLA / Field Command Office');
            $table->unsignedBigInteger('total_voters')->default(342850);
            $table->unsignedBigInteger('male_voters')->default(174200);
            $table->unsignedBigInteger('female_voters')->default(168620);
            $table->unsignedInteger('third_gender')->default(30);
            $table->unsignedInteger('new_voters')->default(6850);
            $table->unsignedInteger('total_booths')->default(318);
            $table->unsignedInteger('sensitive_booths')->default(28);
            $table->unsignedBigInteger('surveyed_households')->default(68420);
            $table->unsignedBigInteger('target_households')->default(82000);
            $table->unsignedInteger('active_karyakartas_today')->default(184);
            $table->unsignedInteger('total_karyakartas')->default(245);
            $table->unsignedInteger('days_to_election')->default(38);
            $table->decimal('sentiment_support', 5, 2)->default(58.4);
            $table->decimal('sentiment_neutral', 5, 2)->default(23.6);
            $table->decimal('sentiment_opposition', 5, 2)->default(18.0);
            $table->timestamps();
        });

        // 2. neta_booth_progress — जिल्हा/विधानसभा/तालुका/गाव/बूथनिहाय Progress
        Schema::create('neta_booth_progress', function (Blueprint $table) {
            $table->id();
            $table->string('booth_no');
            $table->string('booth_name_mr');
            $table->string('booth_name_en');
            $table->string('district_mr')->default('सांगली');
            $table->string('district_en')->default('Sangli');
            $table->string('assembly_mr')->default('२८२ - सांगली');
            $table->string('assembly_en')->default('282 - Sangli');
            $table->string('taluka_mr');
            $table->string('taluka_en');
            $table->string('village_mr');
            $table->string('village_en');
            $table->unsignedInteger('total_voters')->default(1000);
            $table->unsignedInteger('surveyed_voters')->default(0);
            $table->unsignedInteger('coverage_pct')->default(0);
            $table->string('incharge_name');
            $table->string('incharge_phone');
            $table->string('sentiment')->default('anukul'); // anukul, churas, savadh
            $table->string('sentiment_label_mr')->default('अनुकूल');
            $table->string('sentiment_label_en')->default('Supportive');
            $table->string('status')->default('high'); // high, medium, low
            $table->string('key_issue_mr')->nullable();
            $table->string('key_issue_en')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->unsignedInteger('workers_assigned')->default(2);
            $table->timestamps();
        });

        // 3. neta_karyakartas — कार्यकर्त्यांचे काम व Performance Dashboard
        Schema::create('neta_karyakartas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('role_mr');
            $table->string('role_en');
            $table->string('assigned_area_mr');
            $table->string('assigned_area_en');
            $table->unsignedInteger('total_surveys')->default(0);
            $table->unsignedInteger('today_surveys')->default(0);
            $table->decimal('rating', 3, 2)->default(4.5);
            $table->string('status')->default('active'); // active, offline, on_leave
            $table->unsignedInteger('coverage_pct')->default(0);
            $table->date('active_since')->nullable();
            $table->json('badges')->nullable();
            $table->timestamps();
        });

        // 4. neta_meetings_tours — बैठका व दौरे
        Schema::create('neta_meetings_tours', function (Blueprint $table) {
            $table->id();
            $table->string('title_mr');
            $table->string('title_en')->nullable();
            $table->date('date');
            $table->string('time');
            $table->string('venue_mr');
            $table->string('venue_en')->nullable();
            $table->string('type_mr')->default('नागरिक संवाद दौरा');
            $table->string('type_en')->default('Public Townhall Tour');
            $table->string('expected_attendees')->nullable();
            $table->string('incharge')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed
            $table->string('priority')->default('high'); // urgent, high, medium, low
            $table->text('agenda_mr')->nullable();
            $table->text('agenda_en')->nullable();
            $table->timestamps();
        });

        // 5. neta_pending_tasks — Pending Tasks
        Schema::create('neta_pending_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title_mr');
            $table->string('title_en')->nullable();
            $table->string('category_mr')->default('नागरी समस्या निवारण');
            $table->string('category_en')->default('Civic Grievance');
            $table->string('assigned_to');
            $table->string('priority')->default('high'); // urgent, high, medium, low
            $table->date('deadline');
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->text('notes_mr')->nullable();
            $table->text('notes_en')->nullable();
            $table->timestamps();
        });

        // 6. neta_intelligence_reports — महत्त्वाच्या Reports
        Schema::create('neta_intelligence_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_key')->unique(); // demographics, civic_issues, scheme_saturation, booth_classification
            $table->string('title_mr');
            $table->string('title_en');
            $table->json('data_json');
            $table->timestamps();
        });

        // 7. neta_field_updates — Field Updates
        Schema::create('neta_field_updates', function (Blueprint $table) {
            $table->id();
            $table->string('reporter_name');
            $table->string('reporter_role');
            $table->string('location');
            $table->string('booth_ref')->nullable();
            $table->string('type')->default('positive'); // positive, grievance, opposition, warning
            $table->string('title_mr');
            $table->string('title_en')->nullable();
            $table->text('detail_mr');
            $table->text('detail_en')->nullable();
            $table->timestamps();
        });

        // 8. neta_notifications_alerts — Notifications & Alerts
        Schema::create('neta_notifications_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('severity')->default('info'); // critical, warning, info, success
            $table->string('title_mr');
            $table->string('title_en')->nullable();
            $table->text('detail_mr');
            $table->text('detail_en')->nullable();
            $table->boolean('is_unread')->default(true);
            $table->string('target_group')->default('all_workers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neta_notifications_alerts');
        Schema::dropIfExists('neta_field_updates');
        Schema::dropIfExists('neta_intelligence_reports');
        Schema::dropIfExists('neta_pending_tasks');
        Schema::dropIfExists('neta_meetings_tours');
        Schema::dropIfExists('neta_karyakartas');
        Schema::dropIfExists('neta_booth_progress');
        Schema::dropIfExists('neta_area_profiles');
    }
};
