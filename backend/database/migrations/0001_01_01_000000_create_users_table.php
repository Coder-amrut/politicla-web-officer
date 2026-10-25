<?php

/**
 * TEST-ONLY MIGRATION — Field Management Platform Phase 1
 *
 * ⚠️  IMPORTANT SAFETY GUARD:
 *     This migration runs ONLY when APP_ENV=testing.
 *     It targets the field_management_TEST database — never the production
 *     field_management database.
 *     The phpunit.xml sets DB_DATABASE=field_management_test for all test runs.
 *
 * This migration exactly mirrors the production field_management schema
 * so that tests run against an identical structure.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hard safety abort — refuse to run if not in the testing environment
        if (! App::environment('testing')) {
            throw new \RuntimeException(
                'SAFETY ABORT: This test-only migration must not run outside the testing environment.'
            );
        }

        // -----------------------------------------------------------------------
        // roles
        // -----------------------------------------------------------------------
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            // roles has no updated_at in production
        });

        // -----------------------------------------------------------------------
        // users  — mirrors production field_management.users exactly
        // -----------------------------------------------------------------------
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('full_name', 150);
            $table->string('email', 150)->nullable()->unique();
            $table->string('phone', 20)->unique();
            $table->string('password_hash', 255);
            $table->string('profile_photo', 500)->nullable();
            $table->enum('status', ['PENDING', 'ACTIVE', 'INACTIVE', 'SUSPENDED', 'REJECTED'])
                  ->default('PENDING')->index();
            $table->enum('registration_source', ['SELF_REGISTRATION', 'ADMIN_CREATED', 'SYSTEM_BOOTSTRAP'])
                  ->default('SELF_REGISTRATION')->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->dateTime('approved_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // -----------------------------------------------------------------------
        // user_roles  — composite PK, no auto-increment
        // -----------------------------------------------------------------------
        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });

        // -----------------------------------------------------------------------
        // permissions
        // -----------------------------------------------------------------------
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            // no timestamps in production
        });

        // -----------------------------------------------------------------------
        // role_permissions  — composite PK, no timestamps
        // -----------------------------------------------------------------------
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        // -----------------------------------------------------------------------
        // audit_logs  — no updated_at
        // -----------------------------------------------------------------------
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 100);
            $table->string('entity_type', 100)->nullable();
            $table->string('entity_id', 100)->nullable();
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            // No updated_at — matches production
        });

        // -----------------------------------------------------------------------
        // personal_access_tokens  — Sanctum
        // -----------------------------------------------------------------------
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // -----------------------------------------------------------------------
        // Stub tables — prevent relationship errors during tests
        // -----------------------------------------------------------------------
        Schema::create('worker_profiles', function (Blueprint $table) { $table->id(); });
        Schema::create('officer_profiles', function (Blueprint $table) { $table->id(); });
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('name', 200);
            $table->string('project_type', 100);
            $table->text('description')->nullable();
            $table->enum('status', ['DRAFT', 'ACTIVE', 'COMPLETED', 'ARCHIVED'])->default('DRAFT');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('created_by')->references('id')->on('users');
        });
        Schema::create('geography_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->integer('level_order');
        });

        Schema::create('geographies', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('geography_type_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 200);
            $table->string('code', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('geography_type_id')->references('id')->on('geography_types');
            $table->foreign('parent_id')->references('id')->on('geographies')->nullOnDelete();

            $table->index(['parent_id', 'geography_type_id'], 'idx_geographies_parent_type');
        });

        Schema::create('user_project_assignments', function (Blueprint $table) { $table->id(); });
        Schema::create('user_geography_assignments', function (Blueprint $table) { $table->id(); });
        Schema::create('attendance_events', function (Blueprint $table) { $table->id(); });
        Schema::create('sync_operations', function (Blueprint $table) { $table->id(); });
        Schema::create('sync_conflicts', function (Blueprint $table) { $table->id(); });

        // -----------------------------------------------------------------------
        // Seed the three roles (exact production values)
        // -----------------------------------------------------------------------
        \DB::table('roles')->insert([
            ['id' => 1, 'name' => 'WORKER',  'display_name' => 'Field Worker',     'description' => null, 'created_at' => now()],
            ['id' => 2, 'name' => 'OFFICER', 'display_name' => 'Leader / Officer', 'description' => null, 'created_at' => now()],
            ['id' => 3, 'name' => 'ADMIN',   'display_name' => 'Administrator',    'description' => null, 'created_at' => now()],
        ]);

        // -----------------------------------------------------------------------
        // Seed the 9 geography types (exact production values)
        // -----------------------------------------------------------------------
        \DB::table('geography_types')->insert([
            ['id' => 1, 'name' => 'STATE',        'display_name' => 'State',        'level_order' => 1],
            ['id' => 2, 'name' => 'DISTRICT',     'display_name' => 'District',     'level_order' => 2],
            ['id' => 3, 'name' => 'CONSTITUENCY', 'display_name' => 'Constituency', 'level_order' => 3],
            ['id' => 4, 'name' => 'ASSEMBLY',     'display_name' => 'Assembly',     'level_order' => 4],
            ['id' => 5, 'name' => 'TALUKA',       'display_name' => 'Taluka',       'level_order' => 5],
            ['id' => 6, 'name' => 'VILLAGE',      'display_name' => 'Village',      'level_order' => 6],
            ['id' => 7, 'name' => 'WARD',         'display_name' => 'Ward',         'level_order' => 7],
            ['id' => 8, 'name' => 'BOOTH',        'display_name' => 'Booth',        'level_order' => 8],
            ['id' => 9, 'name' => 'AREA',         'display_name' => 'Area',         'level_order' => 9],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
        Schema::dropIfExists('sync_operations');
        Schema::dropIfExists('attendance_events');
        Schema::dropIfExists('user_geography_assignments');
        Schema::dropIfExists('user_project_assignments');
        Schema::dropIfExists('geographies');
        Schema::dropIfExists('geography_types');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('officer_profiles');
        Schema::dropIfExists('worker_profiles');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
