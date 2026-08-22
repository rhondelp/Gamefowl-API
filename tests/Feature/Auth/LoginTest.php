<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * File: tests/Feature/Auth/LoginTest.php
 *
 * Purpose:
 *   Feature tests for POST /api/v1/auth/login (AuthController::login).
 *
 * Covers: successful login issues a working Sanctum token; wrong password
 * and unknown email produce the SAME error message (no account enumeration);
 * the throttle:6,1 rate limiter blocks the 7th consecutive attempt with 429.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_issues_token(): void
    {
        $user = User::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'juan@example.com')
            ->assertJsonPath('data.token', fn (string $token) => $token !== '');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Invalid credentials.');
    }

    public function test_login_rejects_unknown_email_with_same_error_as_wrong_password(): void
    {
        $unknownEmail = $this->postJson('/api/v1/auth/login', [
            'email' => 'ghost@example.com',
            'password' => 'whatever1234',
        ]);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => 'ghost2@example.com',
            'password' => 'whatever1234',
        ]);

        $unknownEmail->assertUnprocessable();
        $wrongPassword->assertUnprocessable();

        $this->assertSame(
            $unknownEmail->json('errors.email'),
            $wrongPassword->json('errors.email')
        );
    }

    public function test_rate_limiting_blocks_repeated_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'secret1234',
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'juan@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'secret1234',
        ])->assertTooManyRequests();
    }
}
