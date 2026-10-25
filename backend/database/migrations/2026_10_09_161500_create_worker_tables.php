<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Worker / Karyakarta portal.
     * All tables prefixed with worker_ as requested.
     */
    public function up(): void
    {
        // 1. worker_profiles update (add missing columns if table exists)
        if (Schema::hasTable('worker_profiles')) {
            Schema::table('worker_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('worker_profiles', 'dob')) {
                    $table->date('dob')->nullable();
                }
                if (!Schema::hasColumn('worker_profiles', 'address')) {
                    $table->text('address')->nullable();
                }
                if (!Schema::hasColumn('worker_profiles', 'assigned_ward')) {
                    $table->string('assigned_ward')->nullable();
                }
                if (!Schema::hasColumn('worker_profiles', 'primary_booths')) {
                    $table->string('primary_booths')->nullable();
                }
                if (!Schema::hasColumn('worker_profiles', 'epic')) {
                    $table->string('epic')->nullable();
                }
                if (!Schema::hasColumn('worker_profiles', 'phone_override')) {
                    $table->string('phone_override')->nullable();
                }
            });
        }

        // 2. worker_tasks — दैनंदिन कामे
        if (!Schema::hasTable('worker_tasks')) {
            Schema::create('worker_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->string('title');
                $table->string('deadline')->nullable();
                $table->string('priority')->default('Medium'); // High, Urgent, Medium, Normal
                $table->boolean('completed')->default(false);
                $table->string('category')->nullable();
                $table->timestamps();
            });
        }

        // 3. worker_follow_ups — मतदार पाठपुरावा
        if (!Schema::hasTable('worker_follow_ups')) {
            Schema::create('worker_follow_ups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->string('citizen_name');
                $table->string('phone')->nullable();
                $table->string('topic');
                $table->string('next_date')->nullable();
                $table->string('status')->default('Pending'); // Pending, Scheduled, Completed
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 4. worker_surveys — मतदार पाहणी मोहीम
        if (!Schema::hasTable('worker_surveys')) {
            Schema::create('worker_surveys', function (Blueprint $table) {
                $table->id();
                $table->string('survey_code')->unique();
                $table->string('title_mr');
                $table->string('title_en')->nullable();
                $table->string('ward_name')->nullable();
                $table->integer('total_voters')->default(0);
                $table->integer('completed_voters')->default(0);
                $table->string('target_date')->nullable();
                $table->string('status')->default('Active'); // Active, Completed, Draft
                $table->text('description')->nullable();
                $table->json('schema_data')->nullable();
                $table->timestamps();
            });
        }

        // 5. worker_survey_responses — भरलेले सर्व्हे फॉर्म्स
        if (!Schema::hasTable('worker_survey_responses')) {
            Schema::create('worker_survey_responses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('survey_id')->nullable()->index();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->string('citizen_name');
                $table->string('voter_id')->nullable();
                $table->string('phone')->nullable();
                $table->text('feedback')->nullable();
                $table->tinyInteger('rating')->default(5);
                $table->string('sentiment')->default('Positive');
                $table->string('booth_no')->nullable();
                $table->json('response_answers')->nullable();
                $table->timestamps();
            });
        }

        // 6. worker_attendances — हजेरी व फेस स्कॅन
        if (!Schema::hasTable('worker_attendances')) {
            Schema::create('worker_attendances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('worker_id')->index();
                $table->string('date'); // YYYY-MM-DD or formatted
                $table->string('punch_in_time')->nullable();
                $table->string('punch_out_time')->nullable();
                $table->string('status')->default('Present'); // Present, Absent, Half-Day
                $table->longText('punch_in_photo')->nullable(); // base64 face screenshot
                $table->longText('punch_out_photo')->nullable();
                $table->string('location_note')->nullable();
                $table->timestamps();
            });
        }

        // 7. worker_complaints — नागरिक तक्रारी
        if (!Schema::hasTable('worker_complaints')) {
            Schema::create('worker_complaints', function (Blueprint $table) {
                $table->id();
                $table->string('complaint_code')->unique();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('category');
                $table->text('desc');
                $table->string('date_reported')->nullable();
                $table->string('status')->default('Pending'); // Pending, Action Taken, In Progress, Resolved
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 8. worker_contacts — संपर्क यादी
        if (!Schema::hasTable('worker_contacts')) {
            Schema::create('worker_contacts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('ward')->nullable();
                $table->string('status')->default('Supportive'); // Supportive, Needs Info, Undecided, Opposed
                $table->boolean('contacted')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 9. worker_meetings — बैठका
        if (!Schema::hasTable('worker_meetings')) {
            Schema::create('worker_meetings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->string('title');
                $table->string('time');
                $table->string('location')->nullable();
                $table->string('attendees')->nullable();
                $table->string('type')->default('In-person'); // In-person, Virtual
                $table->string('status')->default('Scheduled');
                $table->timestamps();
            });
        }

        // 10. worker_candidates — उमेदवार यादी
        if (!Schema::hasTable('worker_candidates')) {
            Schema::create('worker_candidates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('party');
                $table->string('symbol')->default('🚩');
                $table->text('description')->nullable();
                $table->string('photo')->nullable();
                $table->text('about')->nullable();
                $table->text('vision')->nullable();
                $table->text('experience')->nullable();
                $table->text('supporters')->nullable();
                $table->integer('votes_count')->default(0);
                $table->timestamps();
            });
        }

        // 11. worker_votes — नोंदवलेले मतदान
        if (!Schema::hasTable('worker_votes')) {
            Schema::create('worker_votes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('worker_id')->index();
                $table->unsignedBigInteger('candidate_id')->index();
                $table->string('candidate_name');
                $table->timestamp('voted_at');
                $table->timestamps();
            });
        }

        // 12. worker_activities — अलीकडील हालचाली
        if (!Schema::hasTable('worker_activities')) {
            Schema::create('worker_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->string('type')->default('users'); // users, clock, clipboard
                $table->string('title');
                $table->string('time');
                $table->string('bg_color')->nullable();
                $table->string('color')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_activities');
        Schema::dropIfExists('worker_votes');
        Schema::dropIfExists('worker_candidates');
        Schema::dropIfExists('worker_meetings');
        Schema::dropIfExists('worker_contacts');
        Schema::dropIfExists('worker_complaints');
        Schema::dropIfExists('worker_attendances');
        Schema::dropIfExists('worker_survey_responses');
        Schema::dropIfExists('worker_surveys');
        Schema::dropIfExists('worker_follow_ups');
        Schema::dropIfExists('worker_tasks');
    }
};
