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
        // 1. Table: published_forms
        if (! Schema::hasTable('published_forms')) {
            Schema::create('published_forms', function (Blueprint $table) {
                $table->id();
                $table->char('uuid', 36)->unique();
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->char('project_uuid', 36)->nullable()->index();
                $table->string('project_name', 255);
                $table->string('form_title', 255);
                $table->string('form_title_mr', 255)->nullable();
                $table->string('geographic_scope', 50)->default('GENERAL')->index();
                $table->json('default_fields')->nullable();
                $table->json('custom_questions')->nullable();
                $table->longText('form_format')->nullable(); // Complete compiled schema
                $table->string('status', 30)->default('PUBLISHED')->index();
                $table->unsignedInteger('version')->default(1);
                $table->unsignedInteger('assigned_users_count')->default(0);
                $table->unsignedBigInteger('published_by')->nullable()->index();
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamps();

                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
                $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 2. Table: published_form_users (Assigned workers/users who have access to fill this form)
        if (! Schema::hasTable('published_form_users')) {
            Schema::create('published_form_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('published_form_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('role', 50)->default('WORKER');
                $table->string('status', 30)->default('ACTIVE'); // ACTIVE, REVOKED
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();

                $table->unique(['published_form_id', 'user_id']);
                $table->foreign('published_form_id')->references('id')->on('published_forms')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('published_form_users');
        Schema::dropIfExists('published_forms');
    }
};
