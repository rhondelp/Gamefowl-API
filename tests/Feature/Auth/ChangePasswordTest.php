<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * File: tests/Feature/Auth/ChangePasswordTest.php
 *
 * Purpose:
 *   Feature tests for PUT /api/v1/auth/me/password, exercising
 *   AuthController::changePassword through real HTTP calls.
 *
 * Covers: successful change (old password stops working, new one logs in),
 * wrong current password rejected with the stored hash untouched, weak or
 * mismatched new password rejected, missing current password rejected,
 * revocation of OTHER tokens while the requesting token keeps working,
 * and 401 without a token.
 *
 * Note: multiple requests inside one method share a container; the
 * framework's RequestGuard caches its resolved user, so follow-up requests
 * call Auth::forgetGuards() first to force fresh token resolution
 * (see Milestone 2 notes).
 */
class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_succeeds_revokes_other_tokens_and_keeps_current(): void
    {
        $user = User::factory()->create(['password' => 'old-secret-123']);
        $requesterToken = $user->createToken('mobile')->plainTextToken;
        $otherDeviceToken = $user->createToken('other-device')->plainTextToken;

        $this->withToken($requesterToken)
            ->putJson('/api/v1/auth/me/password', [
                'current_password' => 'old-secret-123',
                'new_password' => 'new-secret-123',
                'new_password_confirmation' => 'new-secret-123',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully. You have been logged out on other devices.',
            ]);

        $fresh = $user->refresh();
        $this->assertTrue(Hash::check('new-secret-123', $fresh->password));
        $this->assertFalse(Hash::check('old-secret-123', $fresh->password));

        // The OTHER device's token must now be dead...
        Auth::forgetGuards();
        $this->withToken($otherDeviceToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        // ...while the token that performed the change keeps working.
        Auth::forgetGuards();
        $this->withToken($requesterToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);

        // Login follows the same story: old credentials fail, new ones pass.
        Auth::forgetGuards();
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'old-secret-123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        Auth::forgetGuards();
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'new-secret-123',
        ])
            ->assertOk();
    }

    public function test_wrong_current_password_is_rejected_and_password_unchanged(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password; // hashed cast → hash string
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/auth/me/password', [
                'current_password' => 'totally-wrong',
                'new_password' => 'new-secret-123',
                'new_password_confirmation' => 'new-secret-123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);

        // Nothing changed: the stored hash is byte-for-byte identical.
        $this->assertSame($originalHash, $user->fresh()->password);
        $this->assertFalse(Hash::check('new-secret-123', $user->fresh()->password));
    }

    public function test_new_password_must_be_confirmed_and_meet_strength(): void
    {
        $cases = [
            'too short' => [
                'current_password' => 'password',
                'new_password' => 'short7',
                'new_password_confirmation' => 'short7',
            ],
            'mismatched confirmation' => [
                'current_password' => 'password',
                'new_password' => 'good-enough-123',
                'new_password_confirmation' => 'different-456',
            ],
        ];

        foreach ($cases as $label => $payload) {
            $user = User::factory()->create(); // default factory password: 'password'
            $token = $user->createToken('mobile')->plainTextToken;

            $this->withToken($token)
                ->putJson('/api/v1/auth/me/password', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['new_password']);

            $this->assertFalse(
                Hash::check('good-enough-123', $user->fresh()->password),
                "Password changed despite case: {$label}"
            );
        }
    }

    public function test_missing_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'old-secret-123']);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/auth/me/password', [
                'new_password' => 'new-secret-123',
                'new_password_confirmation' => 'new-secret-123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_password_change_requires_authentication(): void
    {
        $this->putJson('/api/v1/auth/me/password', [
            'current_password' => 'whatever',
            'new_password' => 'new-secret-123',
            'new_password_confirmation' => 'new-secret-123',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }
}
