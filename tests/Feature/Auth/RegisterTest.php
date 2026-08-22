<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * File: tests/Feature/Auth/RegisterTest.php
 *
 * Purpose:
 *   Feature tests for POST /api/v1/auth/register, exercising
 *   AuthController::register through real HTTP calls.
 *
 * Covers: successful registration (201 + user + token), duplicate email,
 * short password, mismatched password confirmation, and the security rule
 * that a `role` field in the payload can never create an admin.
 *
 * Note: multiple requests inside one method share a container; when tests
 * switch users they call Auth::forgetGuards() first (see Milestone 2 notes).
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_succeeds_and_returns_user_and_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful.',
                'data' => [
                    'user' => [
                        'name' => 'Juan Dela Cruz',
                        'email' => 'juan@example.com',
                        'role' => 'owner',
                    ],
                ],
            ])
            ->assertJsonPath('data.token', fn (string $token) => $token !== '');

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'role' => 'owner',
        ]);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'Taken@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_rejects_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }

    public function test_registration_rejects_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'different1234',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_role_cannot_be_injected_via_payload(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sneaky Saboteur',
            'email' => 'sneaky@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role' => 'admin',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'sneaky@example.com',
            'role' => 'owner',
        ]);
    }
}
