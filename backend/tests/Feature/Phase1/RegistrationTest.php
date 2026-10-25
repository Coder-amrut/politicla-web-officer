<?php

namespace Tests\Feature\Phase1;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests 1–7 and 20 (partial):
 *   1. Worker self-registration succeeds.
 *   2. Officer self-registration succeeds.
 *   3. ADMIN registration is rejected.
 *   4. Self-registered Worker starts as PENDING.
 *   5. Self-registered Officer starts as PENDING.
 *   6. Password is stored hashed in password_hash.
 *   7. Pending Worker cannot log in as ACTIVE.
 *  20. Password hash is never returned by APIs.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Test 1 — Worker self-registration succeeds
    // -------------------------------------------------------------------------

    public function test_worker_self_registration_succeeds(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name'             => 'John Worker',
            'phone'                 => '9876543210',
            'email'                 => 'worker@example.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'WORKER',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Registration submitted successfully. Your account is pending admin approval.']);
    }

    // -------------------------------------------------------------------------
    // Test 2 — Officer self-registration succeeds
    // -------------------------------------------------------------------------

    public function test_officer_self_registration_succeeds(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name'             => 'Jane Officer',
            'phone'                 => '9876543211',
            'email'                 => 'officer@example.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'OFFICER',
        ]);

        $response->assertStatus(201);
    }

    // -------------------------------------------------------------------------
    // Test 3 — ADMIN registration is rejected (403/422)
    // -------------------------------------------------------------------------

    public function test_admin_self_registration_is_rejected(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name'             => 'Evil Admin',
            'phone'                 => '9876543212',
            'email'                 => 'admin@example.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'ADMIN',
        ]);

        // Must NOT be 201; expect validation failure
        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['phone' => '9876543212', 'status' => 'ACTIVE']);
    }

    // -------------------------------------------------------------------------
    // Test 4 — Self-registered Worker starts as PENDING
    // -------------------------------------------------------------------------

    public function test_self_registered_worker_starts_as_pending(): void
    {
        $this->postJson('/api/auth/register', [
            'full_name'             => 'Bob Worker',
            'phone'                 => '9000000001',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'WORKER',
        ]);

        $this->assertDatabaseHas('users', [
            'phone'               => '9000000001',
            'status'              => 'PENDING',
            'registration_source' => 'SELF_REGISTRATION',
        ]);
    }

    // -------------------------------------------------------------------------
    // Test 5 — Self-registered Officer starts as PENDING
    // -------------------------------------------------------------------------

    public function test_self_registered_officer_starts_as_pending(): void
    {
        $this->postJson('/api/auth/register', [
            'full_name'             => 'Alice Officer',
            'phone'                 => '9000000002',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'OFFICER',
        ]);

        $this->assertDatabaseHas('users', [
            'phone'               => '9000000002',
            'status'              => 'PENDING',
            'registration_source' => 'SELF_REGISTRATION',
        ]);
    }

    // -------------------------------------------------------------------------
    // Test 6 — Password is stored hashed in password_hash (not plaintext)
    // -------------------------------------------------------------------------

    public function test_password_is_stored_hashed_in_password_hash_column(): void
    {
        $this->postJson('/api/auth/register', [
            'full_name'             => 'Hash Test User',
            'phone'                 => '9000000003',
            'password'              => 'MySecretPass1',
            'password_confirmation' => 'MySecretPass1',
            'role'                  => 'WORKER',
        ]);

        $user = User::where('phone', '9000000003')->firstOrFail();

        // Plaintext must NOT be stored
        $this->assertNotEquals('MySecretPass1', $user->password_hash);
        // Must be a valid bcrypt hash
        $this->assertTrue(Hash::check('MySecretPass1', $user->password_hash));
    }

    // -------------------------------------------------------------------------
    // Test 7 — Pending Worker cannot log in
    // -------------------------------------------------------------------------

    public function test_pending_worker_cannot_log_in(): void
    {
        $this->postJson('/api/auth/register', [
            'full_name'             => 'Pending Worker',
            'phone'                 => '9000000004',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'WORKER',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login'    => '9000000004',
            'password' => 'Password1',
        ]);

        $response->assertStatus(403);
        $response->assertJsonMissing(['token']);
    }

    // -------------------------------------------------------------------------
    // Test 20 — Password hash never returned by registration endpoint
    // -------------------------------------------------------------------------

    public function test_password_hash_never_returned_by_register_endpoint(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name'             => 'Security Test',
            'phone'                 => '9000000005',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'WORKER',
        ]);

        $response->assertStatus(201);
        $this->assertStringNotContainsString('password_hash', $response->getContent());
        $this->assertStringNotContainsString('Password1', $response->getContent());
    }
}
