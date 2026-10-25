<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations for Karkarta EVM voting system.
     * Table names prefixed with karkarta_ as requested by the user.
     */
    public function up(): void
    {
        // 1. karkarta_evm_elections
        if (!Schema::hasTable('karkarta_evm_elections')) {
            Schema::create('karkarta_evm_elections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->string('name')->default('२८२ - सांगली विधानसभा / नगर परिषद निवडणूक २०२६');
                $table->text('message')->nullable();
                $table->string('date_time')->nullable()->default('मतदान दिनांक : २० नोव्हेंबर, २०२६ रोजी सकाळी ७:०० ते सायंकाळी ६:००');
                $table->string('whatsapp_number')->nullable()->default('919822011231');
                $table->string('status')->default('ACTIVE');
                $table->timestamps();
            });
        }

        // 2. karkarta_evm_candidates
        if (!Schema::hasTable('karkarta_evm_candidates')) {
            Schema::create('karkarta_evm_candidates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('election_id')->default(1)->index();
                $table->integer('serial_number')->default(1);
                $table->string('candidate_name');
                $table->string('party_name');
                $table->longText('candidate_photo')->nullable();
                $table->longText('party_symbol')->nullable();
                $table->string('position')->default('विधानसभा / प्रभाग');
                $table->string('section_color')->default('#ea580c');
                $table->boolean('highlighted')->default(false);
                $table->text('voice_message')->nullable();
                $table->longText('audio_url')->nullable();
                $table->string('slug')->nullable();
                $table->integer('votes')->default(0);
                $table->timestamps();
            });
        }

        // 3. karkarta_evm_votes
        if (!Schema::hasTable('karkarta_evm_votes')) {
            Schema::create('karkarta_evm_votes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('election_id')->default(1)->index();
                $table->unsignedBigInteger('candidate_id')->index();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->string('candidate_name');
                $table->string('voter_name')->nullable();
                $table->string('voter_phone')->nullable();
                $table->string('booth_no')->nullable();
                $table->timestamp('voted_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. karkarta_evm_settings
        if (!Schema::hasTable('karkarta_evm_settings')) {
            Schema::create('karkarta_evm_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('worker_id')->nullable()->index();
                $table->boolean('enable_sound')->default(true);
                $table->boolean('enable_vibration')->default(true);
                $table->boolean('allow_multiple_votes')->default(true);
                $table->string('contact_whatsapp')->nullable();
                $table->string('contact_call')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karkarta_evm_settings');
        Schema::dropIfExists('karkarta_evm_votes');
        Schema::dropIfExists('karkarta_evm_candidates');
        Schema::dropIfExists('karkarta_evm_elections');
    }
};
