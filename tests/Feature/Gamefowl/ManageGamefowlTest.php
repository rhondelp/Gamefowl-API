<?php

namespace Tests\Feature\Gamefowl;

use App\Models\Gamefowl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManageGamefowlTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_show_their_own_bird(): void
    {
        [$owner, $token] = $this->owner();
        $bird = Gamefowl::factory()->for($owner)->create();

        $this->withToken($token)
            ->getJson("/api/v1/gamefowls/{$bird->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.gamefowl.id', $bird->id)
            ->assertJsonPath('data.gamefowl.name', $bird->name);
    }

    public function test_owner_can_update_their_own_bird(): void
    {
        [$owner, $token] = $this->owner();
        $bird = Gamefowl::factory()->for($owner)->create(['weight' => 3.0]);

        $this->withToken($token)
            ->putJson("/api/v1/gamefowls/{$bird->id}", [
                'name' => 'Renamed',
                'weight' => 4.5,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.gamefowl.name', 'Renamed')
            ->assertJsonPath('data.gamefowl.weight', 4.5)
            ->assertJsonPath('data.gamefowl.is_active', false);

        $this->assertDatabaseHas('gamefowls', [
            'id' => $bird->id,
            'name' => 'Renamed',
            'weight' => 4.5,
            'is_active' => false,
        ]);
    }

    public function test_other_owner_gets_404_on_show_update_and_delete(): void
    {
        [$intruder, $intruderToken] = $this->owner();
        [$victim] = $this->owner();
        $bird = Gamefowl::factory()->for($victim)->create();

        $this->withToken($intruderToken)
            ->getJson("/api/v1/gamefowls/{$bird->id}")
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found.',
            ]);

        $this->withToken($intruderToken)
            ->putJson("/api/v1/gamefowls/{$bird->id}", ['name' => 'Hacked'])
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found.',
            ]);

        $this->withToken($intruderToken)
            ->deleteJson("/api/v1/gamefowls/{$bird->id}")
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found.',
            ]);

        $this->assertDatabaseHas('gamefowls', [
            'id' => $bird->id,
            'deleted_at' => null,
        ]);
    }

    public function test_delete_soft_deletes_so_record_survives_for_history(): void
    {
        [$owner, $token] = $this->owner();
        $bird = Gamefowl::factory()->for($owner)->create();

        $this->withToken($token)
            ->deleteJson("/api/v1/gamefowls/{$bird->id}")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Gamefowl deleted successfully.',
            ]);

        $this->assertSoftDeleted($bird);

        $this->assertSame(1, DB::table('gamefowls')->count());

        $this->withToken($token)->getJson('/api/v1/gamefowls')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0);

        $this->withToken($token)->getJson('/api/v1/gamefowls?include_inactive=1')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0);

        $this->withToken($token)->getJson("/api/v1/gamefowls/{$bird->id}")
            ->assertNotFound();
    }

    public function test_show_requires_authentication(): void
    {
        [$owner] = $this->owner();
        $bird = Gamefowl::factory()->for($owner)->create();

        $this->getJson("/api/v1/gamefowls/{$bird->id}")
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function owner(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        return [$user, $token];
    }
}
