<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * File: tests/Feature/Auth/UpdateProfileTest.php
 *
 * Purpose:
 *   Feature tests for PATCH /api/v1/auth/me, exercising
 *   AuthController::updateProfile through real HTTP calls.
 *
 * Covers: successful name+email change, email uniqueness against OTHER
 * users, own-current-email accepted (uniqueness ignores self), role /
 * active-status payload keys silently ignored (admin-only via /admin/users),
 * 401 without a token, and immediate visibility through GET /auth/me.
 *
 * Note: multiple requests inside one method share a container; the
 * framework's RequestGuard caches its resolved user, so follow-up requests
 * call Auth::forgetGuards() first to force fresh token resolution
 * (see Milestone 2 notes).
 */
class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_changes_name_and_email(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/v1/auth/me', [
                'name' => 'Renamed Owner',
                'email' => 'renamed@example.com',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Renamed Owner',
                        'email' => 'renamed@example.com',
                        'role' => 'owner',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Renamed Owner',
            'email' => 'renamed@example.com',
        ]);
    }

    public function test_email_already_used_by_another_user_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/v1/auth/me', [
                'name' => $user->name,
                'email' => 'Taken@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        // Rejection must leave the caller's row untouched.
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_own_current_email_is_not_rejected_as_taken(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        // Same email as the caller's own row + a name change must pass.
        $this->withToken($token)
            ->patchJson('/api/v1/auth/me', [
                'name' => 'Same Email Owner',
                'email' => $user->email,
            ])
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Same Email Owner')
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_role_and_active_status_in_payload_are_silently_ignored(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/v1/auth/me', [
                'name' => 'Privilege Escalation Attempt',
                'email' => 'escalation@example.com',
                'role' => 'admin',
                'is_active' => false,
                'status' => 'inactive',
                'deleted_at' => now()->toISOString(),
            ])
            ->assertOk();

        $fresh = $user->refresh();

        // Role unchanged AND account not deactivated (active status on
        // users IS the soft-delete state — see Milestone 8).
        $this->assertSame('owner', $fresh->role);
        $this->assertFalse($fresh->trashed());
    }

    public function test_me_endpoint_reflects_the_update_immediately(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/v1/auth/me', [
                'name' => 'Fresh Look',
                'email' => 'fresh@example.com',
            ])
            ->assertOk();

        Auth::forgetGuards(); // drop cached guard user so /me re-resolves

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'user' => [
                        'name' => 'Fresh Look',
                        'email' => 'fresh@example.com',
                    ],
                ],
            ]);
    }

    public function test_profile_update_requires_authentication(): void
    {
        $this->patchJson('/api/v1/auth/me', [
            'name' => 'Ghost',
            'email' => 'ghost@example.com',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'ghost@example.com']);
    }
}
