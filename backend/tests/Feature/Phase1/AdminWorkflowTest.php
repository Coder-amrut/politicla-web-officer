<?php

namespace Tests\Feature\Phase1;

use App\Enums\RegistrationSource;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests 10–19:
 *  10. Admin can view pending users.
 *  11. Admin can approve Worker.
 *  12. Approved Worker becomes ACTIVE.
 *  13. Approved Worker can log in.
 *  14. Admin can reject Officer.
 *  15. Rejected Officer cannot log in.
 *  16. Admin can create Worker directly as ACTIVE.
 *  17. Admin can create Officer directly as ACTIVE.
 *  18. Worker cannot access ADMIN-only endpoints.
 *  19. Officer cannot access ADMIN-only endpoints.
 */
class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAdmin(): User
    {
        $adminRole = Role::where('name', 'ADMIN')->firstOrFail();
        $user = User::create([
            'uuid'                => (string) Str::uuid(),
            'full_name'           => 'Admin User',
            'email'               => 'admin@wf.test',
            'phone'               => '8800000001',
            'password_hash'       => Hash::make('AdminPass1'),
            'status'              => UserStatus::ACTIVE->value,
            'registration_source' => RegistrationSource::SYSTEM_BOOTSTRAP->value,
        ]);
        $user->roles()->syncWithoutDetaching([$adminRole->id]);
        return $user;
    }

    private function createPendingUser(string $role = 'WORKER', string $phone = '7700000001'): User
    {
        $roleModel = Role::where('name', $role)->firstOrFail();
        $user = User::create([
            'uuid'                => (string) Str::uuid(),
            'full_name'           => "Pending {$role}",
            'phone'               => $phone,
            'password_hash'       => Hash::make('Password1'),
            'status'              => UserStatus::PENDING->value,
            'registration_source' => RegistrationSource::SELF_REGISTRATION->value,
        ]);
        $user->roles()->syncWithoutDetaching([$roleModel->id]);
        return $user;
    }

    private function adminToken(): array
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('test')->plainTextToken;
        return [$admin, $token];
    }

    // -------------------------------------------------------------------------
    // Test 10 — Admin can view pending users
    // -------------------------------------------------------------------------

    public function test_admin_can_view_pending_users(): void
    {
        [, $token] = $this->adminToken();
        $this->createPendingUser('WORKER', '7700000001');
        $this->createPendingUser('OFFICER', '7700000002');

        $response = $this->withToken($token)
                         ->getJson('/api/admin/users/pending');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'total'])
                 ->assertJsonPath('total', 2);

        // password_hash must not appear
        $this->assertStringNotContainsString('password_hash', $response->getContent());
    }

    // -------------------------------------------------------------------------
    // Test 11 — Admin can approve Worker
    // -------------------------------------------------------------------------

    public function test_admin_can_approve_pending_worker(): void
    {
        [$admin, $token] = $this->adminToken();
        $worker = $this->createPendingUser('WORKER', '7700000003');

        $response = $this->withToken($token)
                         ->postJson("/api/admin/users/{$worker->id}/approve");

        $response->assertStatus(200)
                 ->assertJsonPath('user.status', 'ACTIVE');
    }

    // -------------------------------------------------------------------------
    // Test 12 — Approved Worker becomes ACTIVE in the database
    // -------------------------------------------------------------------------

    public function test_approved_worker_becomes_active_in_database(): void
    {
        [$admin, $token] = $this->adminToken();
        $worker = $this->createPendingUser('WORKER', '7700000004');

        $this->withToken($token)
             ->postJson("/api/admin/users/{$worker->id}/approve");

        $this->assertDatabaseHas('users', [
            'id'         => $worker->id,
            'status'     => 'ACTIVE',
            'approved_by' => $admin->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test 13 — Approved Worker can log in
    // -------------------------------------------------------------------------

    public function test_approved_worker_can_log_in(): void
    {
        [$admin, $token] = $this->adminToken();
        $worker = $this->createPendingUser('WORKER', '7700000005');

        // Approve
        $this->withToken($token)
             ->postJson("/api/admin/users/{$worker->id}/approve");

        // Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'login'    => '7700000005',
            'password' => 'Password1',
        ]);

        $loginResponse->assertStatus(200)
                      ->assertJsonStructure(['token']);
    }

    // -------------------------------------------------------------------------
    // Test 14 — Admin can reject Officer
    // -------------------------------------------------------------------------

    public function test_admin_can_reject_pending_officer(): void
    {
        [, $token] = $this->adminToken();
        $officer = $this->createPendingUser('OFFICER', '7700000006');

        $response = $this->withToken($token)
                         ->postJson("/api/admin/users/{$officer->id}/reject", [
                             'rejection_reason' => 'Incomplete documentation.',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('user.status', 'REJECTED');

        $this->assertDatabaseHas('users', [
            'id'               => $officer->id,
            'status'           => 'REJECTED',
            'rejection_reason' => 'Incomplete documentation.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Test 15 — Rejected Officer cannot log in
    // -------------------------------------------------------------------------

    public function test_rejected_officer_cannot_log_in(): void
    {
        [, $token] = $this->adminToken();
        $officer = $this->createPendingUser('OFFICER', '7700000007');

        $this->withToken($token)
             ->postJson("/api/admin/users/{$officer->id}/reject", [
                 'rejection_reason' => 'Duplicate account.',
             ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'login'    => '7700000007',
            'password' => 'Password1',
        ]);

        $loginResponse->assertStatus(403)
                      ->assertJsonMissing(['token']);
    }

    // -------------------------------------------------------------------------
    // Test 16 — Admin can create Worker directly as ACTIVE
    // -------------------------------------------------------------------------

    public function test_admin_can_create_worker_directly_as_active(): void
    {
        [, $token] = $this->adminToken();

        $response = $this->withToken($token)
                         ->postJson('/api/admin/users', [
                             'full_name' => 'Direct Worker',
                             'phone'     => '7700000008',
                             'email'     => 'dworker@example.com',
                             'password'  => 'Password1',
                             'role'      => 'WORKER',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('user.status', 'ACTIVE')
                 ->assertJsonPath('user.registration_source', 'ADMIN_CREATED');
    }

    // -------------------------------------------------------------------------
    // Test 17 — Admin can create Officer directly as ACTIVE
    // -------------------------------------------------------------------------

    public function test_admin_can_create_officer_directly_as_active(): void
    {
        [, $token] = $this->adminToken();

        $response = $this->withToken($token)
                         ->postJson('/api/admin/users', [
                             'full_name' => 'Direct Officer',
                             'phone'     => '7700000009',
                             'email'     => 'dofficer@example.com',
                             'password'  => 'Password1',
                             'role'      => 'OFFICER',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('user.status', 'ACTIVE')
                 ->assertJsonPath('user.registration_source', 'ADMIN_CREATED');
    }

    // -------------------------------------------------------------------------
    // Test 18 — Worker cannot access ADMIN-only endpoints
    // -------------------------------------------------------------------------

    public function test_worker_cannot_access_admin_endpoints(): void
    {
        $workerRole = Role::where('name', 'WORKER')->firstOrFail();
        $worker = User::create([
            'uuid'                => (string) Str::uuid(),
            'full_name'           => 'Regular Worker',
            'phone'               => '6600000001',
            'password_hash'       => Hash::make('Password1'),
            'status'              => UserStatus::ACTIVE->value,
            'registration_source' => RegistrationSource::ADMIN_CREATED->value,
        ]);
        $worker->roles()->syncWithoutDetaching([$workerRole->id]);
        $token = $worker->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/users/pending')->assertStatus(403);
        $this->withToken($token)->postJson('/api/admin/users', [])->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Test 19 — Officer cannot access ADMIN-only endpoints
    // -------------------------------------------------------------------------

    public function test_officer_cannot_access_admin_endpoints(): void
    {
        $officerRole = Role::where('name', 'OFFICER')->firstOrFail();
        $officer = User::create([
            'uuid'                => (string) Str::uuid(),
            'full_name'           => 'Regular Officer',
            'phone'               => '6600000002',
            'password_hash'       => Hash::make('Password1'),
            'status'              => UserStatus::ACTIVE->value,
            'registration_source' => RegistrationSource::ADMIN_CREATED->value,
        ]);
        $officer->roles()->syncWithoutDetaching([$officerRole->id]);
        $token = $officer->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/users/pending')->assertStatus(403);
        $this->withToken($token)->postJson('/api/admin/users/{id}/approve', [])->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Security: password_hash never appears in admin responses
    // -------------------------------------------------------------------------

    public function test_password_hash_never_returned_by_admin_endpoints(): void
    {
        [, $token] = $this->adminToken();
        $this->createPendingUser('WORKER', '6600000003');

        $response = $this->withToken($token)->getJson('/api/admin/users/pending');
        $this->assertStringNotContainsString('password_hash', $response->getContent());
    }

    // -------------------------------------------------------------------------
    // Security: Unauthenticated access returns 401
    // -------------------------------------------------------------------------

    public function test_unauthenticated_cannot_access_admin_endpoints(): void
    {
        $this->getJson('/api/admin/users/pending')->assertStatus(401);
    }
}
