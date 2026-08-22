<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\User;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Consolidated authorization checkpoint (pre-mobile-completion pass):
 * 1. every /api/v1/admin/* route rejects non-admins with the Forbidden envelope;
 * 2. no owner can read another owner's private data by ID guessing.
 */
class AuthorizationSweepTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_route_rejects_non_admin_users(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);

        [, $ownerToken] = $this->userWithRole('owner');
        $diseaseId = (int) Disease::value('id');

        $routes = [
            // Milestone 4 knowledge-base admin surface.
            ['GET', '/api/v1/admin/diseases'],
            ['POST', '/api/v1/admin/diseases', ['name' => 'X', 'description' => 'D', 'severity' => 'mild', 'recommended_action' => 'A']],
            ['GET', "/api/v1/admin/diseases/{$diseaseId}"],
            ['PATCH', "/api/v1/admin/diseases/{$diseaseId}", ['name' => 'Y']],
            ['DELETE', "/api/v1/admin/diseases/{$diseaseId}"],
            ['POST', "/api/v1/admin/diseases/{$diseaseId}/recommendations", ['recommendation_id' => 1]],
            ['DELETE', "/api/v1/admin/diseases/{$diseaseId}/recommendations/1"],
            ['GET', '/api/v1/admin/symptoms'],
            ['POST', '/api/v1/admin/symptoms', ['name' => 'X', 'category' => 'respiratory', 'severity' => 'mild']],
            ['PUT', '/api/v1/admin/symptoms/1', ['name' => 'Y']],
            ['DELETE', '/api/v1/admin/symptoms/1'],
            ['GET', '/api/v1/admin/recommendations'],
            ['POST', '/api/v1/admin/recommendations', ['title' => 'T', 'content' => 'C', 'category' => 'hygiene']],
            ['PUT', '/api/v1/admin/recommendations/1', ['title' => 'Y']],
            ['DELETE', '/api/v1/admin/recommendations/1'],
            ['POST', '/api/v1/admin/rules', ['disease_id' => $diseaseId, 'symptom_id' => 1, 'weight' => 3]],
            ['PUT', '/api/v1/admin/rules/1', ['weight' => 2]],
            ['DELETE', '/api/v1/admin/rules/1'],
            // Milestone 8 user management + dashboard.
            ['GET', '/api/v1/admin/users'],
            ['GET', '/api/v1/admin/users/1'],
            ['PATCH', '/api/v1/admin/users/1', ['role' => 'admin']],
            ['DELETE', '/api/v1/admin/users/1'],
            ['GET', '/api/v1/admin/dashboard'],
        ];

        foreach ($routes as $attempt) {
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

    public function test_owners_cannot_reach_other_owners_private_data_by_guessing_ids(): void
    {
        [, $intruderToken] = $this->userWithRole('owner');

        [$victimOwner, , $victimsBird] = $this->ownerWithBirdAndAssessment();

        $guessAttempts = [
            "/api/v1/gamefowls/{$victimsBird->id}",
            "/api/v1/gamefowls/{$victimsBird->id}/health-records",
            "/api/v1/gamefowls/{$victimsBird->id}/health-history",
            "/api/v1/gamefowls/{$victimsBird->id}/health-status",
        ];

        foreach ($guessAttempts as $url) {
            $this->withToken($intruderToken)
                ->getJson($url)
                ->assertNotFound();
        }

        // Assessment detail for another owner's bird.
        $assessment = \App\Models\HealthAssessment::where('gamefowl_id', $victimsBird->id)->firstOrFail();
        $this->assertSame($victimOwner->id, $victimsBird->user_id);

        $this->withToken($intruderToken)
            ->getJson("/api/v1/health-assessments/{$assessment->id}")
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function userWithRole(string $role): array
    {
        $user = User::factory()->create(['role' => $role]);

        return [$user, $user->createToken('mobile')->plainTextToken];
    }

    /**
     * @return array{0: User, 1: string, 2: \App\Models\Gamefowl}
     */
    private function ownerWithBirdAndAssessment(): array
    {
        $this->seed(KnowledgeBaseSeeder::class);

        $owner = User::factory()->create();
        $bird = \App\Models\Gamefowl::factory()->for($owner)->create();

        $bloodyId = (int) \App\Models\Symptom::where('name', 'Bloody droppings')->value('id');
        $coccidiosis = Disease::where('name', 'Coccidiosis')->first();

        $assessment = \App\Models\HealthAssessment::create(['gamefowl_id' => $bird->id]);
        $assessment->symptoms()->attach([
            $bloodyId => ['symptom_name' => 'Bloody droppings'],
        ]);
        $assessment->results()->create([
            'disease_id' => $coccidiosis->id,
            'disease_name' => 'Coccidiosis',
            'rank' => 1,
            'match_score' => 21,
            'matched_symptoms' => [],
            'missing_symptoms' => [],
            'severity_at_assessment' => 'severe',
        ]);

        return [$owner, $owner->createToken('mobile')->plainTextToken, $bird];
    }
}
