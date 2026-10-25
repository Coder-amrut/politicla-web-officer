<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates tables for:
     * 1. Data Approval & Correction (admin_field_submissions)
     * 2. Database Backup & Restore tracking (admin_system_backups)
     * 3. Duplicate Voter & Data Detection (admin_duplicate_flags)
     * 4. Central Document & Media Manager (admin_media_documents)
     * 5. Security Monitoring & Active Sessions (admin_security_sessions)
     */
    public function up(): void
    {
        // 1. admin_field_submissions — फील्ड डेटा मंजुरी व दुरुस्ती
        Schema::create('admin_field_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submission_code')->unique(); // e.g. SUB-2026-001
            $table->string('form_code')->default('SURVEY_GENERAL');
            $table->string('form_title_mr')->default('मतदार व कुटुंब पाहणी फॉर्म');
            $table->string('citizen_name_mr');
            $table->string('voter_id_number')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('booth_no')->default('बूथ क्र. १');
            $table->string('village_mr')->default('सांगली');
            $table->string('taluka_mr')->default('सांगली शहर');
            $table->string('submitted_by_name')->default('क्षेत्र कार्यकर्ता');
            $table->json('submission_data')->nullable(); // Answers & demographics
            $table->string('status')->default('PENDING_APPROVAL')->index(); // PENDING_APPROVAL, APPROVED, REJECTED, CORRECTED
            $table->string('reviewed_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('correction_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // 2. admin_system_backups — डेटाबेस बॅकअप नोंदी
        Schema::create('admin_system_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->unique();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('file_size_formatted')->default('0 KB');
            $table->string('backup_type')->default('FULL_SQL'); // FULL_SQL, SCHEMA_ONLY, DATA_ONLY
            $table->string('status')->default('SUCCESS'); // SUCCESS, FAILED
            $table->string('created_by_name')->default('Admin');
            $table->timestamps();
        });

        // 3. admin_duplicate_flags — ड्युप्लिकेट मतदार व डेटा डिटेक्शन
        Schema::create('admin_duplicate_flags', function (Blueprint $table) {
            $table->id();
            $table->string('detection_type'); // VOTER_ID_DUPLICATE, PHONE_DUPLICATE, NAME_SIMILARITY
            $table->string('identifier_value')->index(); // e.g. MH/44/282/001429
            $table->unsignedBigInteger('primary_record_id');
            $table->string('primary_record_table')->default('neta_citizens');
            $table->unsignedBigInteger('duplicate_record_id')->nullable();
            $table->string('duplicate_record_table')->default('neta_citizens');
            $table->string('record_title_mr')->nullable();
            $table->string('booth_conflict_mr')->nullable();
            $table->unsignedTinyInteger('similarity_score')->default(100); // 0 to 100
            $table->string('status')->default('DETECTED')->index(); // DETECTED, RESOLVED_MERGED, DISMISSED_VALID
            $table->text('notes_mr')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamps();
        });

        // 4. admin_media_documents — कागदपत्रे व फोटो व्यवस्थापक
        Schema::create('admin_media_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title_mr');
            $table->string('category')->default('civic_issue_photo'); // voter_id_proof, civic_issue_photo, event_photo, application_form
            $table->string('file_name');
            $table->string('file_url');
            $table->string('file_type')->default('image/jpeg');
            $table->string('file_size_formatted')->default('250 KB');
            $table->string('booth_ref')->nullable();
            $table->string('citizen_name_ref')->nullable();
            $table->string('uploaded_by_name')->default('क्षेत्र कार्यकर्ता');
            $table->string('tags')->nullable();
            $table->timestamps();
        });

        // 5. admin_security_sessions — सुरक्षा मॉनिटरिंग व अ‍ॅक्टिव्ह सेशन्स
        Schema::create('admin_security_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name');
            $table->string('role_name')->default('WORKER');
            $table->string('ip_address')->default('127.0.0.1');
            $table->string('user_agent')->nullable();
            $table->string('device_type')->default('Mobile App');
            $table->string('location_ip')->default('सांगली, महाराष्ट्र');
            $table->timestamp('last_activity_at')->nullable();
            $table->string('status')->default('ACTIVE')->index(); // ACTIVE, TERMINATED, FAILED_ATTEMPT
            $table->boolean('is_blocked_ip')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_security_sessions');
        Schema::dropIfExists('admin_media_documents');
        Schema::dropIfExists('admin_duplicate_flags');
        Schema::dropIfExists('admin_system_backups');
        Schema::dropIfExists('admin_field_submissions');
    }
};
