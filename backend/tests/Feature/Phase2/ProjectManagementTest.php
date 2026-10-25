<?php

namespace Tests\Feature\Phase2;

use App\Enums\ProjectStatus;
use App\Enums\RegistrationSource;
use App\Enums\UserStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 2 — Project Management API Tests
 *
 * Covers:
 *   1.  Unauthenticated requests are rejected (401)
 *   2.  Non-admin (WORKER) cannot access project endpoints (403)
 *   3.  Admin can create a project (201)
 *   4.  Project creation validates required fields (422)
 *   5.  Admin can list projects (200 + paginated)
 *   6.  Admin can retrieve a single project by UUID (200)
 *   7.  Retrieving a non-existent UUID returns 404
 *   8.  Admin can update project metadata (200)
 *   9.  Update endpoint rejects status field (only metadata allowed)
 *  10.  Admin can transition status DRAFT → ACTIVE (200)
 *  11.  Admin can transition status ACTIVE → COMPLETED (200)
 *  12.  Admin can transition status COMPLETED → ARCHIVED (200)
 *  13.  Invalid status transition is rejected (409)
 *  14.  ARCHIVED is terminal — no further transitions (409)
 *  15.  Status endpoint rejects an unknown status value (422)
 *  16.  Project list can be filtered by status
 *  17.  Project is persisted correctly in the database after creation
 *  18.  AuditLog row is written on creation
 *  19.  AuditLog row is written on status change
 *  20.  Created project UUID is unique and well-formed
 */
class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAdmin(): User
    {
        $adminRole = Role::where('name', 'ADMIN')->firstOrFail();

        $admin = User::create([
            'uuid'                => (string) Str::uuid(),
            'full_name'           => 'Test Admin',
            'email'               => 'admin@pm-test.com',
            'phone'               => '9800000099',
            'password_hash'       => Hash::make('AdminPass1'),
            'status'              => UserStatus::ACTIVE->value,
            'registration_source' => RegistrationSource::SYSTEM_BOOTSTRAP->value,
        ]);

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        return $admin;
    }

    private function createWorker(): User
    {
        $workerRole = Role::where('name', 'WORKER')->firstOrFail();

        $worker = User::create([
            'uuid'                => (string) Str::uuid(),
            'full_name'           => 'Test Worker',
            'email'               => null,
            'phone'               => '9800000098',
            'password_hash'       => Hash::make('WorkerPass1'),
            'status'              => UserStatus::ACTIVE->value,
            'registration_source' => RegistrationSource::ADMIN_CREATED->value,
        ]);

        $worker->roles()->syncWithoutDetaching([$workerRole->id]);

        return $worker;
    }

    /** Log the user in and return the bearer token. */
    private function loginToken(string $phone, string $password): string
    {
        $res = $this->postJson('/api/auth/login', [
            'login'    => $phone,
            'password' => $password,
        ]);
        $res->assertStatus(200);
        return $res->json('token');
    }

    /** Minimal valid project payload. */
    private function projectPayload(array $overrides = []): array
    {
        return array_merge([
            'name'         => 'Test Project Alpha',
            'project_type' => 'Survey',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Test 1 — Unauthenticated request is rejected
    // -------------------------------------------------------------------------

    public function test_unauthenticated_cannot_list_projects(): void
    {
        $this->getJson('/api/admin/projects')->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Test 2 — Non-admin (WORKER) cannot access project endpoints
    // -------------------------------------------------------------------------

    public function test_worker_cannot_access_project_endpoints(): void
    {
        $this->createWorker();
        $token = $this->loginToken('9800000098', 'WorkerPass1');

        $this->withToken($token)->getJson('/api/admin/projects')->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Test 3 — Admin can create a project (201)
    // -------------------------------------------------------------------------

    public function test_admin_can_create_project(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $response = $this->withToken($token)->postJson('/api/admin/projects', $this->projectPayload([
            'description' => 'A test project.',
            'start_date'  => '2025-01-01',
            'end_date'    => '2025-06-30',
        ]));

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'project' => [
                         'uuid', 'name', 'project_type', 'description',
                         'status', 'start_date', 'end_date', 'created_by', 'created_at', 'updated_at',
                     ],
                 ])
                 ->assertJsonPath('project.name', 'Test Project Alpha')
                 ->assertJsonPath('project.status', 'DRAFT') // default
                 ->assertJsonPath('project.created_by.id', $admin->id);
    }

    // -------------------------------------------------------------------------
    // Test 4 — Validation rejects missing required fields (422)
    // -------------------------------------------------------------------------

    public function test_project_creation_fails_without_required_fields(): void
    {
        $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $this->withToken($token)->postJson('/api/admin/projects', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'project_type']);
    }

    // -------------------------------------------------------------------------
    // Test 5 — Admin can list all projects (200 + pagination keys)
    // -------------------------------------------------------------------------

    public function test_admin_can_list_projects(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        // Create 3 projects directly
        Project::create([
            'uuid' => (string) Str::uuid(), 'name' => 'Project 1',
            'project_type' => 'Type A', 'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);
        Project::create([
            'uuid' => (string) Str::uuid(), 'name' => 'Project 2',
            'project_type' => 'Type B', 'status' => 'ACTIVE', 'created_by' => $admin->id,
        ]);
        Project::create([
            'uuid' => (string) Str::uuid(), 'name' => 'Project 3',
            'project_type' => 'Type C', 'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);

        $response = $this->withToken($token)->getJson('/api/admin/projects');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'total', 'per_page', 'page', 'last_page'])
                 ->assertJsonPath('total', 3);
    }

    // -------------------------------------------------------------------------
    // Test 6 — Admin can retrieve a single project by UUID (200)
    // -------------------------------------------------------------------------

    public function test_admin_can_get_project_by_uuid(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'Single Project',
            'project_type' => 'Demo', 'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);

        $response = $this->withToken($token)->getJson('/api/admin/projects/' . $uuid);

        $response->assertStatus(200)
                 ->assertJsonPath('project.uuid', $uuid)
                 ->assertJsonPath('project.name', 'Single Project');
    }

    // -------------------------------------------------------------------------
    // Test 7 — Non-existent UUID returns 404
    // -------------------------------------------------------------------------

    public function test_unknown_uuid_returns_404(): void
    {
        $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $this->withToken($token)
             ->getJson('/api/admin/projects/' . Str::uuid())
             ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Test 8 — Admin can update project metadata (200)
    // -------------------------------------------------------------------------

    public function test_admin_can_update_project_metadata(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'Old Name',
            'project_type' => 'Old Type', 'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);

        $response = $this->withToken($token)->patchJson('/api/admin/projects/' . $uuid, [
            'name'         => 'New Name',
            'project_type' => 'New Type',
            'description'  => 'Updated description',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('project.name', 'New Name')
                 ->assertJsonPath('project.project_type', 'New Type')
                 ->assertJsonPath('project.status', 'DRAFT'); // status unchanged
    }

    // -------------------------------------------------------------------------
    // Test 9 — Update endpoint ignores status (status must use /status route)
    // The UpdateProjectRequest deliberately excludes status.
    // Sending status in the metadata update must NOT change it.
    // -------------------------------------------------------------------------

    public function test_update_metadata_does_not_change_status(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'Project',
            'project_type' => 'Type', 'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);

        // Attempt to inject status via the metadata update endpoint
        $this->withToken($token)->patchJson('/api/admin/projects/' . $uuid, [
            'name'   => 'Updated',
            'status' => 'ACTIVE', // should be ignored
        ])->assertStatus(200)
          ->assertJsonPath('project.status', 'DRAFT'); // status must remain DRAFT
    }

    // -------------------------------------------------------------------------
    // Test 10 — Status transition: DRAFT → ACTIVE (200)
    // -------------------------------------------------------------------------

    public function test_can_transition_draft_to_active(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'P', 'project_type' => 'T',
            'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);

        $this->withToken($token)
             ->patchJson('/api/admin/projects/' . $uuid . '/status', ['status' => 'ACTIVE'])
             ->assertStatus(200)
             ->assertJsonPath('project.status', 'ACTIVE');
    }

    // -------------------------------------------------------------------------
    // Test 11 — Status transition: ACTIVE → COMPLETED (200)
    // -------------------------------------------------------------------------

    public function test_can_transition_active_to_completed(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'P', 'project_type' => 'T',
            'status' => 'ACTIVE', 'created_by' => $admin->id,
        ]);

        $this->withToken($token)
             ->patchJson('/api/admin/projects/' . $uuid . '/status', ['status' => 'COMPLETED'])
             ->assertStatus(200)
             ->assertJsonPath('project.status', 'COMPLETED');
    }

    // -------------------------------------------------------------------------
    // Test 12 — Status transition: COMPLETED → ARCHIVED (200)
    // -------------------------------------------------------------------------

    public function test_can_transition_completed_to_archived(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'P', 'project_type' => 'T',
            'status' => 'COMPLETED', 'created_by' => $admin->id,
        ]);

        $this->withToken($token)
             ->patchJson('/api/admin/projects/' . $uuid . '/status', ['status' => 'ARCHIVED'])
             ->assertStatus(200)
             ->assertJsonPath('project.status', 'ARCHIVED');
    }

    // -------------------------------------------------------------------------
    // Test 13 — Invalid transition DRAFT → COMPLETED is rejected (409)
    // -------------------------------------------------------------------------

    public function test_invalid_status_transition_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'P', 'project_type' => 'T',
            'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);

        $this->withToken($token)
             ->patchJson('/api/admin/projects/' . $uuid . '/status', ['status' => 'COMPLETED'])
             ->assertStatus(409);
    }

    // -------------------------------------------------------------------------
    // Test 14 — ARCHIVED is terminal — no transitions (409)
    // -------------------------------------------------------------------------

    public function test_archived_project_cannot_transition(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'P', 'project_type' => 'T',
            'status' => 'ARCHIVED', 'created_by' => $admin->id,
        ]);

        foreach (['DRAFT', 'ACTIVE', 'COMPLETED'] as $target) {
            $this->withToken($token)
                 ->patchJson('/api/admin/projects/' . $uuid . '/status', ['status' => $target])
                 ->assertStatus(409);
        }
    }

    // -------------------------------------------------------------------------
    // Test 15 — Unknown status value is rejected (422)
    // -------------------------------------------------------------------------

    public function test_unknown_status_value_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'P', 'project_type' => 'T',
            'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);

        $this->withToken($token)
             ->patchJson('/api/admin/projects/' . $uuid . '/status', ['status' => 'INVALID_STATUS'])
             ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Test 16 — Project list can be filtered by status
    // -------------------------------------------------------------------------

    public function test_project_list_can_be_filtered_by_status(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        Project::create([
            'uuid' => (string) Str::uuid(), 'name' => 'Draft P',
            'project_type' => 'T', 'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);
        Project::create([
            'uuid' => (string) Str::uuid(), 'name' => 'Active P',
            'project_type' => 'T', 'status' => 'ACTIVE', 'created_by' => $admin->id,
        ]);

        $response = $this->withToken($token)->getJson('/api/admin/projects?status=DRAFT');

        $response->assertStatus(200)
                 ->assertJsonPath('total', 1)
                 ->assertJsonPath('data.0.status', 'DRAFT');
    }

    // -------------------------------------------------------------------------
    // Test 17 — Project is persisted correctly in the database
    // -------------------------------------------------------------------------

    public function test_project_is_persisted_in_database(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $this->withToken($token)->postJson('/api/admin/projects', [
            'name'         => 'DB Persist Test',
            'project_type' => 'Census',
            'description'  => 'Verify DB row',
            'start_date'   => '2025-03-01',
        ])->assertStatus(201);

        $this->assertDatabaseHas('projects', [
            'name'         => 'DB Persist Test',
            'project_type' => 'Census',
            'status'       => 'DRAFT',
            'created_by'   => $admin->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test 18 — AuditLog row is written on project creation
    // -------------------------------------------------------------------------

    public function test_audit_log_created_on_project_creation(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $this->withToken($token)->postJson('/api/admin/projects', $this->projectPayload())
             ->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'PROJECT_CREATED',
            'user_id'     => $admin->id,
            'entity_type' => 'projects',
        ]);
    }

    // -------------------------------------------------------------------------
    // Test 19 — AuditLog row is written on status change
    // -------------------------------------------------------------------------

    public function test_audit_log_written_on_status_change(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $uuid = (string) Str::uuid();
        Project::create([
            'uuid' => $uuid, 'name' => 'P', 'project_type' => 'T',
            'status' => 'DRAFT', 'created_by' => $admin->id,
        ]);

        $this->withToken($token)
             ->patchJson('/api/admin/projects/' . $uuid . '/status', ['status' => 'ACTIVE'])
             ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'PROJECT_STATUS_CHANGED',
            'user_id'     => $admin->id,
            'entity_type' => 'projects',
        ]);
    }

    // -------------------------------------------------------------------------
    // Test 20 — Each project gets a unique, well-formed UUID
    // -------------------------------------------------------------------------

    public function test_created_projects_have_unique_uuids(): void
    {
        $admin = $this->createAdmin();
        $token = $this->loginToken('9800000099', 'AdminPass1');

        $res1 = $this->withToken($token)->postJson('/api/admin/projects', $this->projectPayload(['name' => 'P1']))->assertStatus(201);
        $res2 = $this->withToken($token)->postJson('/api/admin/projects', $this->projectPayload(['name' => 'P2']))->assertStatus(201);

        $uuid1 = $res1->json('project.uuid');
        $uuid2 = $res2->json('project.uuid');

        $this->assertNotEquals($uuid1, $uuid2);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $uuid1
        );
    }
}
