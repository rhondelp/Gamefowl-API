<?php

namespace Tests\Feature\Gamefowl;

use App\Models\Gamefowl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

/**
 * File: tests/Feature/Gamefowl/ListGamefowlTest.php
 *
 * Purpose:
 *   Feature tests for GET /api/v1/gamefowls.
 *
 * Covers: strict per-owner scoping (another owner's birds never appear),
 * inactive birds excluded by default but included with ?include_inactive=1,
 * and authentication required.
 */
class ListGamefowlTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_returns_only_the_authenticated_users_birds(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $mine = Gamefowl::factory()->count(2)->for($owner)->create();
        $theirs = Gamefowl::factory()->for($other)->create();

        $theirId = $theirs->id;

        $this->withToken($owner->createToken('mobile')->plainTextToken)
            ->getJson('/api/v1/gamefowls')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
                ->has('data.items', 2)
                ->where('data.pagination.total', 2)
                ->etc()
            )
            ->assertJsonMissing(['id' => $theirId]);
    }

    public function test_list_excludes_inactive_birds_by_default(): void
    {
        $owner = User::factory()->create();

        $active = Gamefowl::factory()->for($owner)->create();
        $retired = Gamefowl::factory()->inactive()->for($owner)->create();

        $activeId = $active->id;
        $retiredId = $retired->id;

        $this->withToken($owner->createToken('mobile')->plainTextToken)
            ->getJson('/api/v1/gamefowls')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.pagination.total', 1)
                ->where('data.items.0.id', $activeId)
                ->etc()
            );

        $this->withToken($owner->createToken('mobile')->plainTextToken)
            ->getJson('/api/v1/gamefowls?include_inactive=1')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.pagination.total', 2)
                ->etc()
            );
    }

    public function test_list_requires_authentication(): void
    {
        $this->getJson('/api/v1/gamefowls')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }
}
