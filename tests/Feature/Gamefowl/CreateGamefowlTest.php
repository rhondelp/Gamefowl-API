<?php

namespace Tests\Feature\Gamefowl;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * File: tests/Feature/Gamefowl/CreateGamefowlTest.php
 *
 * Purpose:
 *   Feature tests for POST /api/v1/gamefowls (GamefowlController::store).
 *
 * Covers: successful creation (201 + persisted row), authentication required,
 * missing name rejected, invalid sex enum rejected, future date_of_birth
 * rejected, and payload-spoofed user_id ignored (bird always belongs to the
 * authenticated owner).
 */
class CreateGamefowlTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_gamefowl(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/gamefowls', [
            'name' => 'Manny',
            'breed' => 'Kelso',
            'date_of_birth' => '2025-01-15',
            'sex' => 'male',
            'color' => 'Black',
            'weight' => 3.2,
            'notes' => 'Broodcock candidate.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.gamefowl.name', 'Manny')
            ->assertJsonPath('data.gamefowl.breed', 'Kelso')
            ->assertJsonPath('data.gamefowl.sex', 'male')
            ->assertJsonPath('data.gamefowl.weight', 3.2)
            ->assertJsonPath('data.gamefowl.is_active', true);

        $this->assertDatabaseHas('gamefowls', [
            'user_id' => $user->id,
            'name' => 'Manny',
            'breed' => 'Kelso',
            'sex' => 'male',
        ]);
    }

    public function test_create_requires_authentication(): void
    {
        $this->postJson('/api/v1/gamefowls', ['name' => 'Manny'])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_create_requires_name(): void
    {
        $user = User::factory()->create();

        $this->actingAsToken($user)
            ->postJson('/api/v1/gamefowls', ['sex' => 'male'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_rejects_invalid_sex_value(): void
    {
        $user = User::factory()->create();

        $this->actingAsToken($user)
            ->postJson('/api/v1/gamefowls', [
                'name' => 'Manny',
                'sex' => 'rooster',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sex']);
    }

    public function test_create_rejects_future_date_of_birth(): void
    {
        $user = User::factory()->create();

        $this->actingAsToken($user)
            ->postJson('/api/v1/gamefowls', [
                'name' => 'Time Traveler',
                'sex' => 'female',
                'date_of_birth' => now()->addYear()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    public function test_user_id_cannot_be_spoofed_via_payload(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAsToken($owner)
            ->postJson('/api/v1/gamefowls', [
                'name' => 'Stolen Bird',
                'sex' => 'male',
                'user_id' => $other->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('gamefowls', [
            'name' => 'Stolen Bird',
            'user_id' => $owner->id,
        ]);

        $this->assertDatabaseMissing('gamefowls', [
            'name' => 'Stolen Bird',
            'user_id' => $other->id,
        ]);
    }

    private function actingAsToken(User $user): self
    {
        return $this->withToken($user->createToken('mobile')->plainTextToken);
    }
}
