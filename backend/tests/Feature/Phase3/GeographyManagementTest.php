<?php

namespace Tests\Feature\Phase3;

use App\Enums\GeographyStatus;
use App\Enums\ProjectStatus;
use App\Enums\RegistrationSource;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Geography;
use App\Models\GeographyType;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3 — Geography Management API Tests
 */
class GeographyManagementTest extends TestCase
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
            'email'               => 'admin@geo-test.com',
            'phone'               => '9800000001',
            'password_hash'       => Hash::make('AdminPass1'),
            'status'              => UserStatus::ACTIVE->value,
            'registration_source' => RegistrationSource::SYSTEM_BOOTSTRAP->value,
        ]);

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        return $admin;
    }

    private function createOfficer(): User
    {
        $officerRole = Role::where('name', 'OFFICER')->firstOrFail();

        $officer = User::create([
            'uuid'                => (string) Str::uuid(),
            'full_name'           => 'Test Officer',
            'email'               => 'officer@geo-test.com',
            'phone'               => '9800000002',
            'password_hash'       => Hash::make('OfficerPass1'),
            'status'              => UserStatus::ACTIVE->value,
            'registration_source' => RegistrationSource::ADMIN_CREATED->value,
        ]);

        $officer->roles()->syncWithoutDetaching([$officerRole->id]);

        return $officer;
    }

    private function createWorker(): User
    {
        $workerRole = Role::where('name', 'WORKER')->firstOrFail();

        $worker = User::create([
            'uuid'                => (string) Str::uuid(),
            'full_name'           => 'Test Worker',
            'email'               => null,
            'phone'               => '9800000003',
            'password_hash'       => Hash::make('WorkerPass1'),
            'status'              => UserStatus::ACTIVE->value,
            'registration_source' => RegistrationSource::ADMIN_CREATED->value,
        ]);

        $worker->roles()->syncWithoutDetaching([$workerRole->id]);

        return $worker;
    }

    private function loginToken(string $phone, string $password): string
    {
        $res = $this->postJson('/api/auth/login', [
            'login'    => $phone,
            'password' => $password,
        ]);
        $res->assertStatus(200);
        return $res->json('token');
    }

    private function createTestProject(User $creator, string $status = 'ACTIVE'): Project
    {
        return Project::create([
            'uuid'         => (string) Str::uuid(),
            'name'         => 'Test Election Campaign ' . Str::random(5),
            'project_type' => 'CAMPAIGN',
            'description'  => 'Test Description',
            'status'       => $status,
            'created_by'   => $creator->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. Authentication & Authorization Tests
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_to_geography_types_are_rejected(): void
    {
        $res = $this->getJson('/api/admin/geography-types');
        $res->assertStatus(401);
    }

    public function test_unauthenticated_requests_to_project_geographies_are_rejected(): void
    {
        $res = $this->getJson('/api/admin/projects/00000000-0000-0000-0000-000000000000/geographies');
        $res->assertStatus(401);
    }

    public function test_worker_cannot_access_geography_endpoints(): void
    {
        $admin = $this->createAdmin();
        $this->createWorker();
        $project = $this->createTestProject($admin);
        $workerToken = $this->loginToken('9800000003', 'WorkerPass1');

        $headers = ['Authorization' => 'Bearer ' . $workerToken];

        $this->getJson('/api/admin/geography-types', $headers)->assertStatus(403);
        $this->getJson("/api/admin/projects/{$project->uuid}/geographies", $headers)->assertStatus(403);
        $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'name'              => 'State 1',
            'geography_type_id' => 1,
        ], $headers)->assertStatus(403);
    }

    public function test_officer_cannot_access_geography_endpoints(): void
    {
        $admin = $this->createAdmin();
        $this->createOfficer();
        $project = $this->createTestProject($admin);
        $officerToken = $this->loginToken('9800000002', 'OfficerPass1');

        $headers = ['Authorization' => 'Bearer ' . $officerToken];

        $this->getJson('/api/admin/geography-types', $headers)->assertStatus(403);
        $this->getJson("/api/admin/projects/{$project->uuid}/geographies", $headers)->assertStatus(403);
        $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'name'              => 'State 1',
            'geography_type_id' => 1,
        ], $headers)->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 2. Geography Types Reference Tests
    // -------------------------------------------------------------------------

    public function test_admin_can_list_all_9_geography_types_ordered_by_level(): void
    {
        $this->createAdmin();
        $token = $this->loginToken('9800000001', 'AdminPass1');

        $res = $this->getJson('/api/admin/geography-types', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $res->assertStatus(200);
        $data = $res->json('data');

        $this->assertCount(9, $data);
        $this->assertEquals('STATE', $data[0]['name']);
        $this->assertEquals(1, $data[0]['level_order']);
        $this->assertEquals('DISTRICT', $data[1]['name']);
        $this->assertEquals(2, $data[1]['level_order']);
        $this->assertEquals('AREA', $data[8]['name']);
        $this->assertEquals(9, $data[8]['level_order']);
    }

    // -------------------------------------------------------------------------
    // 3. Geography Creation Tests
    // -------------------------------------------------------------------------

    public function test_admin_can_create_top_level_geography(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');

        $res = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1, // STATE
            'name'              => 'Maharashtra',
            'code'              => 'MH',
            'latitude'          => 19.7515,
            'longitude'         => 75.7139,
        ], ['Authorization' => 'Bearer ' . $token]);

        $res->assertStatus(201);
        $res->assertJsonPath('message', 'Geography created successfully.');
        $res->assertJsonPath('geography.name', 'Maharashtra');
        $res->assertJsonPath('geography.code', 'MH');
        $res->assertJsonPath('geography.type.name', 'STATE');
        $res->assertJsonPath('geography.status', 'ACTIVE');
        $res->assertJsonPath('geography.parent', null);
        $this->assertNotNull($res->json('geography.uuid'));
    }

    public function test_admin_can_create_child_geography_with_valid_parent(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Create State (Level 1)
        $stateRes = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'Maharashtra',
        ], $headers);
        $stateUuid = $stateRes->json('geography.uuid');

        // Create District under State (Level 2)
        $districtRes = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 2, // DISTRICT
            'parent_uuid'       => $stateUuid,
            'name'              => 'Pune',
            'code'              => 'PUN',
        ], $headers);

        $districtRes->assertStatus(201);
        $districtRes->assertJsonPath('geography.name', 'Pune');
        $districtRes->assertJsonPath('geography.parent.uuid', $stateUuid);
        $districtRes->assertJsonPath('geography.parent.name', 'Maharashtra');
        $districtRes->assertJsonPath('geography.parent.type.name', 'STATE');
    }

    public function test_geography_creation_fails_when_required_fields_are_missing(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');

        $res = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name', 'geography_type_id']);
    }

    public function test_geography_creation_fails_with_invalid_geography_type(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');

        $res = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 999,
            'name'              => 'Test Region',
        ], ['Authorization' => 'Bearer ' . $token]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['geography_type_id']);
    }

    public function test_geography_creation_fails_when_parent_belongs_to_different_project(): void
    {
        $admin = $this->createAdmin();
        $projectA = $this->createTestProject($admin);
        $projectB = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Create State in Project A
        $stateRes = $this->postJson("/api/admin/projects/{$projectA->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'State in A',
        ], $headers);
        $stateUuidInA = $stateRes->json('geography.uuid');

        // Try to create District in Project B referencing State in Project A
        $res = $this->postJson("/api/admin/projects/{$projectB->uuid}/geographies", [
            'geography_type_id' => 2,
            'parent_uuid'       => $stateUuidInA,
            'name'              => 'District in B',
        ], $headers);

        $res->assertStatus(422);
        $res->assertJsonPath('message', 'Parent geography must belong to the same project.');
    }

    public function test_geography_creation_fails_when_hierarchy_level_is_violated(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Create Village (Level 6)
        $villageRes = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 6, // VILLAGE
            'name'              => 'Sample Village',
        ], $headers);
        $villageUuid = $villageRes->json('geography.uuid');

        // Try to create District (Level 2) with Village (Level 6) as parent
        $res = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 2, // DISTRICT
            'parent_uuid'       => $villageUuid,
            'name'              => 'Invalid District',
        ], $headers);

        $res->assertStatus(422);
        $res->assertJsonPath('message', 'Parent geography level must be higher than the child geography level in the hierarchy.');
    }

    public function test_cannot_create_geography_in_archived_project(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin, status: ProjectStatus::ARCHIVED->value);
        $token = $this->loginToken('9800000001', 'AdminPass1');

        $res = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'State on Archived',
        ], ['Authorization' => 'Bearer ' . $token]);

        $res->assertStatus(422);
        $res->assertJsonPath('message', 'Cannot add geographies to an archived project.');
    }

    // -------------------------------------------------------------------------
    // 4. Geography Listing & Filtering Tests
    // -------------------------------------------------------------------------

    public function test_admin_can_list_geographies_with_pagination_and_filters(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Create State
        $stateRes = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'Maharashtra State',
            'code'              => 'MH',
        ], $headers);
        $stateUuid = $stateRes->json('geography.uuid');

        // Create District
        $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 2,
            'parent_uuid'       => $stateUuid,
            'name'              => 'Pune District',
            'code'              => 'PUN',
        ], $headers);

        // List all
        $allRes = $this->getJson("/api/admin/projects/{$project->uuid}/geographies", $headers);
        $allRes->assertStatus(200);
        $allRes->assertJsonPath('total', 2);

        // Filter by geography_type_id = 1 (STATE only)
        $stateOnlyRes = $this->getJson("/api/admin/projects/{$project->uuid}/geographies?geography_type_id=1", $headers);
        $stateOnlyRes->assertStatus(200);
        $stateOnlyRes->assertJsonPath('total', 1);
        $stateOnlyRes->assertJsonPath('data.0.name', 'Maharashtra State');

        // Filter by parent_uuid
        $childOnlyRes = $this->getJson("/api/admin/projects/{$project->uuid}/geographies?parent_uuid={$stateUuid}", $headers);
        $childOnlyRes->assertStatus(200);
        $childOnlyRes->assertJsonPath('total', 1);
        $childOnlyRes->assertJsonPath('data.0.name', 'Pune District');

        // Filter by top_level_only
        $topLevelRes = $this->getJson("/api/admin/projects/{$project->uuid}/geographies?top_level_only=1", $headers);
        $topLevelRes->assertStatus(200);
        $topLevelRes->assertJsonPath('total', 1);
        $topLevelRes->assertJsonPath('data.0.name', 'Maharashtra State');

        // Search filter
        $searchRes = $this->getJson("/api/admin/projects/{$project->uuid}/geographies?search=Pune", $headers);
        $searchRes->assertStatus(200);
        $searchRes->assertJsonPath('total', 1);
        $searchRes->assertJsonPath('data.0.name', 'Pune District');
    }

    // -------------------------------------------------------------------------
    // 5. Geography Detail & Cross-Project Isolation
    // -------------------------------------------------------------------------

    public function test_admin_can_retrieve_single_geography_with_children(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        $stateRes = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'State Alpha',
        ], $headers);
        $stateUuid = $stateRes->json('geography.uuid');

        $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 2,
            'parent_uuid'       => $stateUuid,
            'name'              => 'District Alpha 1',
        ], $headers);

        $res = $this->getJson("/api/admin/projects/{$project->uuid}/geographies/{$stateUuid}", $headers);
        $res->assertStatus(200);
        $res->assertJsonPath('geography.uuid', $stateUuid);
        $res->assertJsonPath('geography.name', 'State Alpha');
        $res->assertJsonCount(1, 'geography.children');
        $res->assertJsonPath('geography.children.0.name', 'District Alpha 1');
    }

    public function test_geography_from_different_project_returns_404(): void
    {
        $admin = $this->createAdmin();
        $projectA = $this->createTestProject($admin);
        $projectB = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        $geoRes = $this->postJson("/api/admin/projects/{$projectA->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'State in Project A',
        ], $headers);
        $geoUuidInA = $geoRes->json('geography.uuid');

        // Access via Project B URL
        $res = $this->getJson("/api/admin/projects/{$projectB->uuid}/geographies/{$geoUuidInA}", $headers);
        $res->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // 6. Geography Update Tests
    // -------------------------------------------------------------------------

    public function test_admin_can_update_geography_metadata_and_parent(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Create State 1 & State 2
        $state1Res = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'State One',
        ], $headers);
        $state1Uuid = $state1Res->json('geography.uuid');

        $state2Res = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'State Two',
        ], $headers);
        $state2Uuid = $state2Res->json('geography.uuid');

        // Create District under State 1
        $distRes = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 2,
            'parent_uuid'       => $state1Uuid,
            'name'              => 'Original District Name',
            'code'              => 'ODN',
        ], $headers);
        $distUuid = $distRes->json('geography.uuid');

        // Update metadata and move parent to State 2
        $updateRes = $this->patchJson("/api/admin/projects/{$project->uuid}/geographies/{$distUuid}", [
            'name'        => 'Updated District Name',
            'code'        => 'UDN',
            'latitude'    => 18.5204,
            'longitude'   => 73.8567,
            'parent_uuid' => $state2Uuid,
        ], $headers);

        $updateRes->assertStatus(200);
        $updateRes->assertJsonPath('geography.name', 'Updated District Name');
        $updateRes->assertJsonPath('geography.code', 'UDN');
        $updateRes->assertJsonPath('geography.parent.uuid', $state2Uuid);

        // Promote to top-level (parent null)
        $promoteRes = $this->patchJson("/api/admin/projects/{$project->uuid}/geographies/{$distUuid}", [
            'parent_uuid' => null,
        ], $headers);

        $promoteRes->assertStatus(200);
        $promoteRes->assertJsonPath('geography.parent', null);
    }

    public function test_self_referencing_parent_on_update_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        $stateRes = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'State Alpha',
        ], $headers);
        $stateUuid = $stateRes->json('geography.uuid');

        $res = $this->patchJson("/api/admin/projects/{$project->uuid}/geographies/{$stateUuid}", [
            'parent_uuid' => $stateUuid,
        ], $headers);

        $res->assertStatus(422);
        $res->assertJsonPath('message', 'A geography cannot be its own parent.');
    }

    // -------------------------------------------------------------------------
    // 7. Status Toggle Tests
    // -------------------------------------------------------------------------

    public function test_admin_can_toggle_geography_status(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        $stateRes = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'State For Status Test',
        ], $headers);
        $stateUuid = $stateRes->json('geography.uuid');

        // Set to INACTIVE
        $inactiveRes = $this->patchJson("/api/admin/projects/{$project->uuid}/geographies/{$stateUuid}/status", [
            'status' => 'INACTIVE',
        ], $headers);

        $inactiveRes->assertStatus(200);
        $inactiveRes->assertJsonPath('geography.status', 'INACTIVE');

        // Set back to ACTIVE
        $activeRes = $this->patchJson("/api/admin/projects/{$project->uuid}/geographies/{$stateUuid}/status", [
            'status' => 'ACTIVE',
        ], $headers);

        $activeRes->assertStatus(200);
        $activeRes->assertJsonPath('geography.status', 'ACTIVE');

        // Invalid status value
        $invalidRes = $this->patchJson("/api/admin/projects/{$project->uuid}/geographies/{$stateUuid}/status", [
            'status' => 'DELETED',
        ], $headers);

        $invalidRes->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // 8. Audit Logging & DB Verification
    // -------------------------------------------------------------------------

    public function test_audit_logs_are_written_for_geography_lifecycle_events(): void
    {
        $admin = $this->createAdmin();
        $project = $this->createTestProject($admin);
        $token = $this->loginToken('9800000001', 'AdminPass1');
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Create
        $createRes = $this->postJson("/api/admin/projects/{$project->uuid}/geographies", [
            'geography_type_id' => 1,
            'name'              => 'Audit Logged Geography',
        ], $headers);
        $geoUuid = $createRes->json('geography.uuid');

        $this->assertDatabaseHas('audit_logs', [
            'user_id'     => $admin->id,
            'action'      => 'GEOGRAPHY_CREATED',
            'entity_type' => 'geographies',
        ]);

        // Update
        $this->patchJson("/api/admin/projects/{$project->uuid}/geographies/{$geoUuid}", [
            'name' => 'Audit Logged Geography Updated',
        ], $headers);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'     => $admin->id,
            'action'      => 'GEOGRAPHY_UPDATED',
            'entity_type' => 'geographies',
        ]);

        // Status Change
        $this->patchJson("/api/admin/projects/{$project->uuid}/geographies/{$geoUuid}/status", [
            'status' => 'INACTIVE',
        ], $headers);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'     => $admin->id,
            'action'      => 'GEOGRAPHY_STATUS_CHANGED',
            'entity_type' => 'geographies',
        ]);
    }
}
