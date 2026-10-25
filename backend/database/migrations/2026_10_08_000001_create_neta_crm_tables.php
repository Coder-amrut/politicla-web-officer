<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates CRM tables for Citizen 360 contact management, interaction logs, grievances, and meeting attendance.
     */
    public function up(): void
    {
        // 1. neta_citizens — नागरिक व मतदार ३६०° प्रोफाइल
        Schema::create('neta_citizens', function (Blueprint $table) {
            $table->id();
            $table->string('voter_id_number')->nullable()->index(); // EPIC Voter ID
            $table->string('full_name_mr');
            $table->string('full_name_en')->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('alternate_phone')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('gender')->default('male'); // male, female, other
            $table->string('occupation_mr')->nullable();
            $table->string('family_head_name')->nullable();
            $table->unsignedSmallInteger('family_members_count')->default(1);
            $table->string('address_mr');
            $table->string('address_en')->nullable();
            $table->string('district_mr')->default('सांगली');
            $table->string('assembly_mr')->default('२८२ - सांगली');
            $table->string('taluka_mr')->default('सांगली शहर');
            $table->string('village_mr')->default('सांगली');
            $table->string('booth_no')->default('बूथ क्र. १')->index();
            $table->string('voter_category')->default('general'); // influencer, loyal_supporter, fence_sitter, opposition, youth, senior
            $table->string('sentiment')->default('anukul'); // anukul, tatstha, virodhak
            $table->string('sentiment_label_mr')->default('अनुकूल');
            $table->string('assigned_worker_name')->nullable();
            $table->string('assigned_worker_phone')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status')->default('active'); // active, contacted, follow_up_needed, closed
            $table->text('notes_mr')->nullable();
            $table->json('schemes_benefitted')->nullable(); // JSON array of schemes e.g. ["लाडकी बहीण", "पीएम किसान"]
            $table->timestamps();
        });

        // 2. neta_crm_interactions — संपर्क व भेटींचा इतिहास (Calls & Visits Log)
        Schema::create('neta_crm_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained('neta_citizens')->onDelete('cascade');
            $table->string('interaction_type')->default('home_visit'); // home_visit, phone_call, office_visit, rally, tea_discussion
            $table->date('interaction_date');
            $table->string('conducted_by'); // Karyakarta / Leader Name
            $table->text('summary_mr');
            $table->string('outcome')->default('positive'); // positive, neutral, negative, follow_up_required
            $table->string('next_action_mr')->nullable();
            $table->timestamps();
        });

        // 3. neta_crm_issues — नागरी समस्या व मागण्यांची नोंद (Grievance & Issue Tracker)
        Schema::create('neta_crm_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained('neta_citizens')->onDelete('cascade');
            $table->string('tracking_no')->unique(); // e.g. GRV-2026-0101
            $table->string('title_mr');
            $table->string('category')->default('water_drainage'); // water_drainage, roads, street_lights, sanitation, pension_ration, health, other
            $table->string('priority')->default('high'); // urgent, high, normal
            $table->string('status')->default('pending'); // pending, in_progress, resolved, rejected
            $table->text('description_mr');
            $table->string('assigned_worker')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('resolution_notes_mr')->nullable();
            $table->timestamps();
        });

        // 4. neta_crm_meetings_attended — उपस्थित राहिलेल्या बैठका/दौरे
        Schema::create('neta_crm_meetings_attended', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained('neta_citizens')->onDelete('cascade');
            $table->string('meeting_title_mr');
            $table->date('meeting_date');
            $table->string('venue_mr')->nullable();
            $table->text('feedback_mr')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neta_crm_meetings_attended');
        Schema::dropIfExists('neta_crm_issues');
        Schema::dropIfExists('neta_crm_interactions');
        Schema::dropIfExists('neta_citizens');
    }
};
