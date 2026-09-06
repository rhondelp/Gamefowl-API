<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * File: tests/Feature/Auth/ForgotPasswordTest.php
 *
 * Purpose:
 *   Feature tests for POST /api/v1/auth/forgot-password (AuthController::forgotPassword).
 *
 * Covers: valid registered email returns success + notification sent (faked);
 * unregistered email returns SAME success response (no account enumeration);
 * invalid email format returns 422; rate limiting blocks after 6 requests/min.
 */
class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_forgot_password_with_registered_email_sends_notification(): void
    {
        $user = User::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'juan@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'If an account with that email exists, a password reset link has been sent.',
            ]);

        // Verify the reset notification was sent to the user
        Notification::assertSentTo(
            $user,
            \Illuminate\Auth\Notifications\ResetPassword::class,
            function ($notification) use ($user) {
                // The notification should have a valid token
                return $notification->token !== null;
            }
        );
    }

    public function test_forgot_password_with_unregistered_email_returns_same_success_response(): void
    {
        // No user with this email exists
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'ghost@example.com',
        ]);

        // Same success response as registered email — NO account enumeration
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'If an account with that email exists, a password reset link has been sent.',
            ]);

        // No notification should be sent
        Notification::assertNothingSent();
    }

    public function test_forgot_password_with_invalid_email_format_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_missing_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_rate_limiting_blocks_repeated_requests(): void
    {
        User::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'secret1234',
        ]);

        // Make 6 requests (within the throttle:6,1 limit)
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'juan@example.com',
            ])->assertOk();
        }

        // 7th request should be rate limited
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'juan@example.com',
        ])->assertTooManyRequests();
    }
}