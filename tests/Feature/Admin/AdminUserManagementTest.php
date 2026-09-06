<?php

namespace Tests\Feature;

use App\Models\Gamefowl;
use App\Models\HealthAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_is_forbidden_from_user_endpoints(): void
    {
        [, $ownerToken] = $this->userWithRole('owner');

        $attempts = [
            ['GET', '/api/v1/admin/users'],
            ['GET', '/api/v1/admin/users/1'],
            ['PATCH', '/api/v1/admin/users/1', ['role' => 'admin']],
            ['DELETE', '/api/v1/admin/users/1'],
        ];

        foreach ($attempts as $attempt) {
            [$method, $url] = $attempt;
            $payload = $attempt[2] ?? [];

            $this->withToken($ownerToken)
                ->json($method, $url, $payload)
                ->assertForbidden()
                ->assertJson([
                    'success' => false,
                    'message' => 'Forbidden.',
                ]);
        }
    }

    public function test_admin_can_list_users_and_filter_by_role(): void
    {
        [, $token] = $this->userWithRole('admin');
        User::factory()->count(3)->create(); // owners (factory default role is owner via DB default)

        $list = $this->withToken($token)->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('success', true);

        // 3 owners + 1 admin = 4 total.
        $list->assertJsonPath('data.pagination.total', 4);

        $ownersOnly = $this->withToken($token)->getJson('/api/v1/admin/users?role=owner')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 3);

        $this->assertSame('owner', $ownersOnly->json('data.items.0.role'));
    }

    public function test_deactivated_users_hidden_by_default_but_visible_with_filter(): void
    {
        [$admin, $token] = $this->userWithRole('admin');
        $victim = User::factory()->create();
        $victim->delete();

        $this->withToken($token)->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonMissing(['email' => $victim->email]);

        $inactive = $this->withToken($token)->getJson('/api/v1/admin/users?status=inactive')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1);

        $this->assertSame($victim->email, $inactive->json('data.items.0.email'));
        $this->assertFalse($inactive->json('data.items.0.is_active'));
    }

    public function test_admin_can_view_user_detail_with_aggregate_counts(): void
    {
        [$admin, $token] = $this->userWithRole('admin');
        $owner = User::factory()->create();
        Gamefowl::factory()->count(2)->for($owner)->create();

        $detail = $this->withToken($token)
            ->getJson("/api/v1/admin/users/{$owner->id}")
            ->assertOk()
            ->assertJsonPath('data.user.email', $owner->email);

        $this->assertSame(2, $detail->json('data.user.gamefowl_count'));
        $this->assertSame(0, $detail->json('data.user.health_assessment_count'));
        $this->assertArrayNotHasKey('password', $detail->json('data.user'));
    }

    public function test_admin_can_promote_demote_and_change_active_status(): void
    {
        [$admin, $token] = $this->userWithRole('admin');
        $target = User::factory()->create();

        $this->withToken($token)
            ->patchJson("/api/v1/admin/users/{$target->id}", ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'admin');

        $this->withToken($token)
            ->patchJson("/api/v1/admin/users/{$target->id}", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.user.is_active', false);

        $this->assertSoftDeleted('users', ['id' => $target->id]);

        $this->withToken($token)
            ->patchJson("/api/v1/admin/users/{$target->id}", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.user.is_active', true);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'deleted_at' => null,
            'role' => 'admin',
        ]);

        $this->withToken($token)
            ->patchJson("/api/v1/admin/users/{$target->id}", ['role' => 'owner'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'owner');
    }

    public function test_admin_cannot_modify_or_deactivate_their_own_account(): void
    {
        [$admin, $token] = $this->userWithRole('admin');

        $demote = $this->withToken($token)
            ->patchJson("/api/v1/admin/users/{$admin->id}", ['role' => 'owner'])
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        $deactivate = $this->withToken($token)
            ->deleteJson("/api/v1/admin/users/{$admin->id}")
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertStringContainsStringIgnoringCase(
            'own account',
            $demote->json('message').' '.$deactivate->json('message')
        );

        // Self-lockout guard actually held.
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_deactivate_another_user_via_delete(): void
    {
        [$admin, $token] = $this->userWithRole('admin');
        $target = User::factory()->create();

        $this->withToken($token)
            ->deleteJson("/api/v1/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User deactivated successfully.');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function userWithRole(string $role): array
    {
        $user = User::factory()->create(['role' => $role]);
        Auth::forgetGuards();

        return [$user, $user->createToken('mobile')->plainTextToken];
    }
}
