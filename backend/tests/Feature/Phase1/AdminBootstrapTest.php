<?php

namespace Tests\Feature\Phase1;

use App\Enums\RegistrationSource;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests 8–9:
 *   8. Bootstrap admin can be created via admin:create command.
 *   9. Admin can log in.
 */
class AdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an ADMIN user programmatically (simulating what admin:create does)
     * so we can test the resulting state without interactive TTY.
     */
    private function createAdminUser(): User
    {
        $adminRole = Role::where('name', 'ADMIN')->firstOrFail();

        $user = User::create([
            'uuid'                => (string) Str::uuid(),
            'full_name'           => 'System Admin',
            'email'               => 'admin@test.com',
            'phone'               => '9800000001',
            'password_hash'       => Hash::make('AdminPass1'),
            'status'              => UserStatus::ACTIVE->value,
            'registration_source' => RegistrationSource::SYSTEM_BOOTSTRAP->value,
        ]);

        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        return $user;
    }

    // -------------------------------------------------------------------------
    // Test 8 — Bootstrap admin has correct state
    // -------------------------------------------------------------------------

    public function test_bootstrap_admin_has_correct_status_and_source(): void
    {
        $admin = $this->createAdminUser();

        $this->assertDatabaseHas('users', [
            'phone'               => '9800000001',
            'status'              => 'ACTIVE',
            'registration_source' => 'SYSTEM_BOOTSTRAP',
        ]);

        $this->assertTrue($admin->hasRole('ADMIN'));
    }

    // -------------------------------------------------------------------------
    // Test 9 — Admin can log in and receives a token
    // -------------------------------------------------------------------------

    public function test_admin_can_log_in_and_receives_token(): void
    {
        $this->createAdminUser();

        $response = $this->postJson('/api/auth/login', [
            'login'    => '9800000001',
            'password' => 'AdminPass1',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user'])
                 ->assertJsonPath('user.roles.0', 'ADMIN');

        // Never return password_hash
        $this->assertStringNotContainsString('password_hash', $response->getContent());
    }

    // -------------------------------------------------------------------------
    // Test 9b — Admin can log in via email too
    // -------------------------------------------------------------------------

    public function test_admin_can_log_in_via_email(): void
    {
        $this->createAdminUser();

        $response = $this->postJson('/api/auth/login', [
            'login'    => 'admin@test.com',
            'password' => 'AdminPass1',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['token']);
    }

    // -------------------------------------------------------------------------
    // Artisan command structural test
    // -------------------------------------------------------------------------

    public function test_admin_create_command_exists_and_is_registered(): void
    {
        $commands = array_keys(Artisan::all());
        $this->assertContains('admin:create', $commands);
    }

    // -------------------------------------------------------------------------
    // Test — Logout invalidates the token immediately
    //
    // Steps:
    //   1. Login  → 200 + token
    //   2. GET /api/auth/me with token → 200  (token is valid)
    //   3. POST /api/auth/logout with token → 200
    //   4. GET /api/auth/me with SAME token → 401 (token row was deleted)
    // -------------------------------------------------------------------------

    public function test_logout_invalidates_token_immediately(): void
    {
        // Setup: create an active admin user
        $this->createAdminUser();

        // Step 1: Login and capture the token
        $loginResponse = $this->postJson('/api/auth/login', [
            'login'    => '9800000001',
            'password' => 'AdminPass1',
        ]);

        $loginResponse->assertStatus(200)->assertJsonStructure(['token']);

        $token = $loginResponse->json('token');

        // Step 2: Token is valid — /me returns 200
        $this->withToken($token)
             ->getJson('/api/auth/me')
             ->assertStatus(200);

        // Step 3: Logout using the same token — must succeed
        $this->withToken($token)
             ->postJson('/api/auth/logout')
             ->assertStatus(200)
             ->assertJson(['message' => 'Logged out successfully.']);

        // Confirm the token row was physically removed from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            // The token stored in DB is a SHA-256 hash of the plain-text token.
            // We verify by checking that no row exists for this user's token name.
            'tokenable_type' => \App\Models\User::class,
            'name'           => 'api-token',
        ]);

        // Step 4: Reset the application's in-process auth guard cache so the
        // next request performs a fresh database lookup (simulating a new HTTP
        // request from the client after logout).
        $this->refreshApplication();

        // The SAME token must now be rejected — row no longer exists in DB
        $this->withToken($token)
             ->getJson('/api/auth/me')
             ->assertStatus(401);
    }
}
