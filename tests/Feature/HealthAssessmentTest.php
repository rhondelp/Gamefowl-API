<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\Gamefowl;
use App\Models\HealthAssessment;
use App\Models\HealthAssessmentResult;
use App\Models\Recommendation;
use App\Models\Symptom;
use App\Models\User;
use App\Services\ExpertSystem\DiagnosticEngine;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * File: tests/Feature/HealthAssessmentTest.php
 *
 * Purpose:
 *   Milestone 6 coverage: the full assessment lifecycle.
 *
 * Covers: faithful pass-through (endpoint output asserted field-by-field
 * against a direct DiagnosticEngine call on identical input), auto-filled
 * age/sex snapshots, cross-owner 404, input validation (empty list,
 * inactive/nonexistent symptoms rejected HERE, not silently by the engine),
 * view ownership, snapshot immutability after knowledge-base renames/
 * deactivations, care-recommendation snapshots (per-disease, active-only,
 * link order, immune to later edits/unlinks; [] vs null for rows saved
 * before the snapshot existed), and transaction rollback leaving zero
 * partial rows.
 */
class HealthAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(KnowledgeBaseSeeder::class);
    }

    public function test_owner_can_submit_assessment_for_own_bird_and_results_match_engine(): void
    {
        [$owner, $token, $bird] = $this->ownerWithBird();

        $symptomIds = [
            $this->symptomId('Bloody droppings'),
            $this->symptomId('Pale comb'),
            $this->symptomId('Lethargy or depression'),
        ];

        // The endpoint must be a faithful pass-through of the engine:
        // compute the expected output independently, then compare.
        $expected = app(DiagnosticEngine::class)->diagnose($symptomIds);

        $response = $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => $symptomIds,
                'duration_of_symptoms' => '1_to_3_days',
                'appetite' => 'reduced',
                'activity_level' => 'lethargic',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $data = $response->json('data');

        $this->assertCount($expected->count(), $data['results']);

        foreach ($expected as $index => $match) {
            $result = $data['results'][$index];

            $this->assertSame($index + 1, $result['rank']);
            $this->assertSame($match->diseaseId, $result['possible_disease']['id']);
            $this->assertSame($match->diseaseName, $result['possible_disease']['name']);
            $this->assertSame($match->matchScore, $result['match_score']);
            $this->assertSame(
                collect($match->matchedSymptoms)->pluck('name')->all(),
                $result['matched_symptoms']
            );
            $this->assertSame(
                collect($match->missingSymptoms)->pluck('name')->all(),
                $result['missing_symptoms']
            );
        }

        $this->assertDatabaseHas('health_assessments', [
            'gamefowl_id' => $bird->id,
            'duration_of_symptoms' => '1_to_3_days',
            'appetite' => 'reduced',
            'sex_at_assessment' => $bird->sex,
        ]);

        $assessment = HealthAssessment::first();
        $this->assertCount(3, $assessment->symptoms);
        $this->assertCount($expected->count(), $assessment->results);
    }

    public function test_age_and_sex_snapshots_auto_fill_from_the_live_bird(): void
    {
        [$owner, $token, $bird] = $this->ownerWithBird([
            'date_of_birth' => now()->subYears(2)->subMonths(3),
            'sex' => 'female',
        ]);

        $response = $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => [$this->symptomId('Ruffled feathers')],
            ]);

        $response->assertCreated();

        $assessment = HealthAssessment::first();
        $this->assertSame('2y 3m', $assessment->age_at_assessment);
        $this->assertSame('female', $assessment->sex_at_assessment);
    }

    public function test_cannot_assess_another_owners_bird(): void
    {
        [$intruder, $intruderToken] = $this->ownerWithBird();
        [,, $victimsBird] = $this->ownerWithBird();

        $this->withToken($intruderToken)
            ->postJson("/api/v1/gamefowls/{$victimsBird->id}/health-assessments", [
                'symptom_ids' => [$this->symptomId('Bloody droppings')],
            ])
            ->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_empty_symptom_list_is_rejected(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['symptom_ids']);
    }

    public function test_inactive_or_nonexistent_symptom_ids_are_rejected_here_not_by_the_engine(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        Symptom::where('name', 'Sneezing')->update(['is_active' => false]);

        $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => [$this->symptomId('Sneezing')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['symptom_ids.0']);

        $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => [99999],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['symptom_ids.0']);
    }

    public function test_owner_can_view_own_assessment_but_not_another_owners(): void
    {
        [$ownerA, $tokenA, $birdA] = $this->ownerWithBird();
        [$ownerB, $tokenB] = $this->ownerWithBird();

        $assessmentId = $this->withToken($tokenA)
            ->postJson("/api/v1/gamefowls/{$birdA->id}/health-assessments", [
                'symptom_ids' => [$this->symptomId('Bloody droppings')],
            ])
            ->json('data.id');

        $this->withToken($tokenA)
            ->getJson("/api/v1/health-assessments/{$assessmentId}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $assessmentId)
            ->assertJsonPath('data.results.0.possible_disease.name', 'Coccidiosis')
            ->assertJsonPath('data.results.0.match_score', 21);

        Auth::forgetGuards();

        $this->withToken($tokenB)
            ->getJson("/api/v1/health-assessments/{$assessmentId}")
            ->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_historical_snapshots_survive_later_renames_and_deactivations(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        $bloodyId = $this->symptomId('Bloody droppings');
        $paleId = $this->symptomId('Pale comb');

        $original = $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => [$bloodyId, $paleId],
            ])
            ->json('data');

        // Admin mutates the knowledge base after the assessment exists.
        Disease::where('name', 'Coccidiosis')->update(['name' => 'Renamed Condition', 'severity' => 'mild']);
        Symptom::where('id', $bloodyId)->update(['name' => 'Renamed Sign', 'is_active' => false]);
        Symptom::where('id', $paleId)->update(['name' => 'Also Renamed']);

        $refetched = $this->withToken($token)
            ->getJson('/api/v1/health-assessments/'.$original['id'])
            ->assertOk()
            ->json('data');

        $coccidiosisResult = collect($refetched['results'])
            ->first(fn (array $r) => $r['possible_disease']['name'] === 'Coccidiosis');

        $this->assertNotNull($coccidiosisResult);
        $this->assertSame(38, $coccidiosisResult['match_score']); // round((5+4)/24*100)
        $matchedNames = array_values($coccidiosisResult['matched_symptoms']);
        sort($matchedNames);
        $this->assertSame(
            ['Bloody droppings', 'Pale comb'],
            $matchedNames,
            'Matched symptom names must stay exactly as submitted.'
        );
        $this->assertSame('severe', $coccidiosisResult['severity_at_assessment']);

        $submittedNames = array_column($refetched['submitted_symptoms'], 'name');
        sort($submittedNames);
        $this->assertSame(['Bloody droppings', 'Pale comb'], $submittedNames);
    }

    public function test_new_assessment_snapshots_each_diseases_active_linked_recommendations(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        // Deactivated advice must never be copied into a new assessment.
        Recommendation::where('title', 'Reduce mosquito breeding sites around pens')
            ->update(['is_active' => false]);

        $data = $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => [
                    $this->symptomId('Bloody droppings'),
                    $this->symptomId('Pale comb'),
                    $this->symptomId('Wart-like scabs on comb or wattles'),
                ],
            ])
            ->assertCreated()
            ->json('data');

        // Coccidiosis 38 (rank 1) and Fowl Pox 31 (rank 2): each result
        // carries ITS OWN disease's advice, in the order it was linked.
        $this->assertSame(
            ['Coccidiosis', 'Fowl Pox'],
            array_column(array_column($data['results'], 'possible_disease'), 'name')
        );
        $this->assertSame([
            'Keep litter dry and replace soiled bedding',
            'Provide clean water with electrolytes',
            'Consult a licensed veterinarian before medicating',
            'Monitor the flock twice daily and record new cases',
        ], array_column($data['results'][0]['recommendations'], 'title'));
        $this->assertSame([
            'Apply antiseptic to visible lesions',
            'Isolate affected birds immediately',
            'Monitor the flock twice daily and record new cases',
        ], array_column($data['results'][1]['recommendations'], 'title'));

        // Full snapshot shape per entry.
        $dryLitter = Recommendation::where('title', 'Keep litter dry and replace soiled bedding')->firstOrFail();
        $this->assertSame([
            'id' => $dryLitter->id,
            'title' => $dryLitter->title,
            'content' => $dryLitter->content,
            'category' => 'hygiene',
        ], $data['results'][0]['recommendations'][0]);

        // Stored on the result row itself, and the detail endpoint returns
        // the same snapshot.
        $this->assertSame(
            $data['results'][0]['recommendations'],
            HealthAssessmentResult::where('disease_name', 'Coccidiosis')->firstOrFail()->recommendations
        );

        $this->withToken($token)
            ->getJson("/api/v1/health-assessments/{$data['id']}")
            ->assertOk()
            ->assertJsonPath('data.results.0.recommendations', $data['results'][0]['recommendations'])
            ->assertJsonPath('data.results.1.recommendations', $data['results'][1]['recommendations']);
    }

    public function test_recommendation_snapshots_survive_later_edits_deactivations_and_unlinks(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        $symptomIds = [$this->symptomId('Bloody droppings'), $this->symptomId('Pale comb')];

        $original = $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => $symptomIds,
            ])
            ->json('data');

        $snapshot = collect($original['results'])
            ->firstWhere('possible_disease.name', 'Coccidiosis')['recommendations'];
        $this->assertCount(4, $snapshot);

        // Admin reshapes Coccidiosis's advice after the assessment exists:
        // edit one entry, deactivate one, unlink one, link a new one.
        $coccidiosis = Disease::where('name', 'Coccidiosis')->firstOrFail();
        Recommendation::where('title', 'Keep litter dry and replace soiled bedding')
            ->update(['title' => 'Edited title', 'content' => 'Edited content', 'category' => 'environment']);
        Recommendation::where('title', 'Provide clean water with electrolytes')
            ->update(['is_active' => false]);
        $coccidiosis->recommendations()->detach(
            Recommendation::where('title', 'Consult a licensed veterinarian before medicating')->value('id')
        );
        $coccidiosis->recommendations()->attach(
            Recommendation::where('title', 'Isolate affected birds immediately')->value('id')
        );

        $refetched = $this->withToken($token)
            ->getJson('/api/v1/health-assessments/'.$original['id'])
            ->assertOk()
            ->json('data');

        $this->assertSame(
            $snapshot,
            collect($refetched['results'])->firstWhere('possible_disease.name', 'Coccidiosis')['recommendations'],
            'A past assessment must keep showing exactly the advice recorded that day.'
        );
        $this->assertSame('Keep litter dry and replace soiled bedding', $snapshot[0]['title']);
        $this->assertSame('hygiene', $snapshot[0]['category']);

        // The knowledge base really did change: a NEW assessment gets the
        // current advice, so the old one is frozen by its snapshot alone.
        $fresh = $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => $symptomIds,
            ])
            ->assertCreated()
            ->json('data');

        $this->assertSame([
            'Edited title',
            'Monitor the flock twice daily and record new cases',
            'Isolate affected birds immediately',
        ], array_column(
            collect($fresh['results'])->firstWhere('possible_disease.name', 'Coccidiosis')['recommendations'],
            'title'
        ));
    }

    public function test_disease_without_active_recommendations_snapshots_an_empty_list(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        Disease::where('name', 'Coccidiosis')->firstOrFail()->recommendations()->detach();

        $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => [$this->symptomId('Bloody droppings')],
            ])
            ->assertCreated()
            ->assertJsonPath('data.results.0.possible_disease.name', 'Coccidiosis')
            ->assertJsonPath('data.results.0.recommendations', []);
    }

    public function test_results_saved_before_the_recommendation_snapshot_report_null(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        // A row written before the column existed: no recommendations key.
        $assessment = HealthAssessment::create(['gamefowl_id' => $bird->id]);
        $assessment->results()->create([
            'disease_id' => Disease::where('name', 'Coccidiosis')->value('id'),
            'disease_name' => 'Coccidiosis',
            'rank' => 1,
            'match_score' => 21,
            'matched_symptoms' => [],
            'missing_symptoms' => [],
            'severity_at_assessment' => 'severe',
        ]);

        $result = $this->withToken($token)
            ->getJson("/api/v1/health-assessments/{$assessment->id}")
            ->assertOk()
            ->json('data.results.0');

        // null ("not recorded") is deliberately distinct from [] (recorded,
        // nothing linked) so clients can explain the difference.
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertNull($result['recommendations']);
    }

    public function test_failure_mid_persist_leaves_no_partial_assessment(): void
    {
        [$owner, $token, $bird] = $this->ownerWithBird();

        // Simulate a crash after the assessment row and pivots are written
        // but before results finish saving.
        HealthAssessmentResult::creating(function () {
            throw new \RuntimeException('Simulated mid-persist failure.');
        });

        $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => [$this->symptomId('Bloody droppings')],
            ])
            ->assertServerError();

        $this->assertDatabaseCount('health_assessments', 0);
        $this->assertDatabaseCount('health_assessment_symptoms', 0);
        $this->assertDatabaseCount('health_assessment_results', 0);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->postJson('/api/v1/gamefowls/1/health-assessments')->assertUnauthorized();
        $this->getJson('/api/v1/health-assessments/1')->assertUnauthorized();
    }

    private function symptomId(string $name): int
    {
        return (int) Symptom::where('name', $name)->value('id');
    }

    /**
     * @return array{0: User, 1: string, 2: Gamefowl}
     */
    private function ownerWithBird(array $birdAttributes = []): array
    {
        $user = User::factory()->create();
        $bird = Gamefowl::factory(array_merge($birdAttributes, []))->for($user)->create();

        return [$user, $user->createToken('mobile')->plainTextToken, $bird];
    }
}
