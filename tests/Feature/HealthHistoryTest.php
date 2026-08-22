<?php

namespace Tests\Feature;

use App\Models\Gamefowl;
use App\Models\HealthAssessment;
use App\Models\Symptom;
use App\Models\User;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(KnowledgeBaseSeeder::class);
    }

    public function test_owner_can_create_a_health_record_with_defaults(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        $response = $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-records", [
                'type' => 'vet_visit',
                'title' => 'Routine check-up',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.record.type', 'vet_visit')
            ->assertJsonPath('data.record.recorded_at', now()->toDateString());

        $this->assertDatabaseHas('health_records', [
            'gamefowl_id' => $bird->id,
            'type' => 'vet_visit',
            'title' => 'Routine check-up',
        ]);
    }

    public function test_health_record_validation_rejects_bad_input(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-records", [
                'type' => 'bogus_type',
                'title' => 'Nope',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);

        $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-records", [
                'type' => 'general_note',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-records", [
                'type' => 'weight_check',
                'title' => 'Future log',
                'recorded_at' => now()->addWeek()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recorded_at']);
    }

    public function test_cannot_create_or_list_records_for_another_owners_bird(): void
    {
        [$intruder, $intruderToken] = $this->ownerWithBird();
        [,, $victimsBird] = $this->ownerWithBird();

        $this->withToken($intruderToken)
            ->postJson("/api/v1/gamefowls/{$victimsBird->id}/health-records", [
                'type' => 'general_note',
                'title' => 'Sneaky note',
            ])
            ->assertNotFound()
            ->assertJson(['success' => false]);

        $this->withToken($intruderToken)
            ->getJson("/api/v1/gamefowls/{$victimsBird->id}/health-records")
            ->assertNotFound();
    }

    public function test_health_records_list_is_paginated_and_scoped(): void
    {
        [$owner, $token, $bird] = $this->ownerWithBird();

        for ($i = 1; $i <= 3; $i++) {
            $this->withToken($token)
                ->postJson("/api/v1/gamefowls/{$bird->id}/health-records", [
                    'type' => 'general_note',
                    'title' => "Note {$i}",
                    'recorded_at' => now()->subDays($i)->toDateString(),
                ])->assertCreated();
        }

        $list = $this->withToken($token)
            ->getJson("/api/v1/gamefowls/{$bird->id}/health-records?per_page=2")
            ->assertOk();

        $list->assertJsonPath('data.pagination.total', 3)
            ->assertJsonPath('data.pagination.per_page', 2);

        // Sorted newest recorded_at first.
        $this->assertSame('Note 1', $list->json('data.items.0.title'));
        $this->assertSame('Note 2', $list->json('data.items.1.title'));
    }

    public function test_timeline_interleaves_both_entry_types_chronologically(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        // Submitted out of chronological order on purpose.
        $oldRecord = $this->createRecord($token, $bird, 'Weight log', now()->subDays(10));
        $recentAssessment = $this->submitAssessment($token, $bird, ['Bloody droppings', 'Pale comb']);
        $midRecord = $this->createRecord($token, $bird, 'Vet visit last week', now()->subDays(5));

        // Force a second assessment to sit between the two records (6 days ago).
        $forcedAssessment = $this->submitAssessment($token, $bird, ['Sneezing']);
        DB::table('health_assessments')
            ->where('id', $forcedAssessment['id'])
            ->update(['created_at' => Carbon::now()->subDays(6)]);

        Auth::forgetGuards();

        $timeline = $this->withToken($token)
            ->getJson("/api/v1/gamefowls/{$bird->id}/health-history")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 4);

        $items = $timeline->json('data.items');

        $this->assertSame(
            [
                ['assessment', $recentAssessment['id']],
                ['health_record', $midRecord],
                ['assessment', $forcedAssessment['id']],
                ['health_record', $oldRecord],
            ],
            array_map(
                fn (array $item) => [$item['type'], $item['type'] === 'assessment' ? $item['assessment_id'] : $item['record_id']],
                $items
            )
        );

        // Assessment entries are summarized: top disease + score + severity,
        // never nested results.
        $top = collect($items)->firstWhere('type', 'assessment');
        $this->assertSame('Coccidiosis', $top['top_possible_disease']['name']);
        $this->assertSame(38, $top['match_score']);
        $this->assertArrayNotHasKey('results', $top);
        $this->assertArrayNotHasKey('matched_symptoms', $top);
    }

    public function test_timeline_pagination_works_over_mixed_entries(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        // Newest-first expected order: b-assessment, a-assessment, R1 (1d ago), R2 (2d ago).
        $r1 = $this->createRecord($token, $bird, 'R1', now()->subDay());
        $r2 = $this->createRecord($token, $bird, 'R2', now()->subDays(2));
        $a = $this->submitAssessment($token, $bird, ['Bloody droppings']);
        $b = $this->submitAssessment($token, $bird, ['Pale comb']);

        Auth::forgetGuards();

        $page1 = $this->withToken($token)
            ->getJson("/api/v1/gamefowls/{$bird->id}/health-history?per_page=2&page=1")
            ->assertOk();

        $page2 = $this->withToken($token)
            ->getJson("/api/v1/gamefowls/{$bird->id}/health-history?per_page=2&page=2")
            ->assertOk();

        $page1->assertJsonPath('data.pagination.total', 4)
            ->assertJsonPath('data.pagination.current_page', 1);

        $page1Items = $page1->json('data.items');
        $page2Items = $page2->json('data.items');

        $this->assertCount(2, $page1Items);
        $this->assertCount(2, $page2Items);

        $asEntry = fn (array $item) => [$item['type'], $item['type'] === 'assessment' ? $item['assessment_id'] : $item['record_id']];

        $this->assertSame([['assessment', $b['id']], ['assessment', $a['id']]], array_map($asEntry, $page1Items));
        $this->assertSame([['health_record', $r1], ['health_record', $r2]], array_map($asEntry, $page2Items));
    }

    public function test_timeline_on_bird_without_history_is_empty_not_an_error(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        $this->withToken($token)
            ->getJson("/api/v1/gamefowls/{$bird->id}/health-history")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['items' => [], 'pagination' => ['total' => 0]],
            ]);
    }

    /**
     * Status-label derivation rules (evaluated in order):
     *   1. no assessments at all                     -> no_data
     *   2. latest assessment older than recent window -> stale
     *   3. top result match_score >= 50              -> needs_attention
     *   4. otherwise                                 -> healthy
     */
    public function test_status_label_derivation_table(): void
    {
        $cases = [
            'fresh bird with nothing at all' => [
                'setup' => null,
                'expected' => 'no_data',
            ],
            'records exist but screening has not happened yet' => [
                'setup' => 'records_only',
                'expected' => 'no_data',
            ],
            'recent assessment with low score' => [
                'setup' => 'low_score_recent',
                'expected' => 'healthy',
            ],
            'recent assessment where zero symptoms matched strongly' => [
                'setup' => 'no_qualifying_results',
                'expected' => 'healthy',
            ],
            'recent assessment at or above 50' => [
                'setup' => 'high_score_recent',
                'expected' => 'needs_attention',
            ],
            'latest assessment is old' => [
                'setup' => 'stale_assessment',
                'expected' => 'stale',
            ],
        ];

        foreach ($cases as $name => $case) {
            [, $token, $bird] = $this->ownerWithBird();
            Auth::forgetGuards();

            switch ($case['setup']) {
                case 'records_only':
                    $this->withToken($token)->postJson("/api/v1/gamefowls/{$bird->id}/health-records", [
                        'type' => 'general_note', 'title' => 'Just a note',
                    ])->assertCreated();
                    break;

                case 'low_score_recent':
                    // Coccidiosis: round((5+3)/24*100) = 33 < 50.
                    $this->submitAssessment($token, $bird,
                        ['Bloody droppings', 'Lethargy or depression']);
                    break;

                case 'no_qualifying_results':
                    // Loss of appetite alone scores below every threshold:
                    // max round(3/24*100)=13 for coccidiosis < 20 default threshold
                    // -> assessment persists with zero results.
                    $this->submitAssessment($token, $bird, ['Loss of appetite']);
                    break;

                case 'high_score_recent':
                    // Coccidiosis fully matched -> 100 >= 50.
                    $this->submitAssessment($token, $bird, [
                        'Bloody droppings', 'Pale comb', 'Lethargy or depression',
                    ]);
                    break;

                case 'stale_assessment':
                    $this->submitAssessment($token, $bird, ['Bloody droppings']);
                    DB::table('health_assessments')->update([
                        'created_at' => Carbon::now()->subDays(15),
                    ]);
                    break;
            }

            $status = $this->withToken($token)
                ->getJson("/api/v1/gamefowls/{$bird->id}/health-status")
                ->assertOk()
                ->json('data.status');

            $this->assertSame($case['expected'], $status, "Case failed: {$name}");
        }
    }

    public function test_status_includes_context_payload_for_recent_concerning_case(): void
    {
        [, $token, $bird] = $this->ownerWithBird();

        $this->submitAssessment($token, $bird, [
            'Bloody droppings', 'Pale comb', 'Lethargy or depression',
        ]);

        $data = $this->withToken($token)
            ->getJson("/api/v1/gamefowls/{$bird->id}/health-status")
            ->assertOk()
            ->json('data');

        $this->assertSame('needs_attention', $data['status']);
        $this->assertSame(50, $data['based_on']['match_score']);
        $this->assertSame('Coccidiosis', $data['based_on']['top_possible_disease']['name']);
        $this->assertNotNull($data['disclaimer']);
    }

    public function test_cross_owner_history_and_status_are_not_found(): void
    {
        [, $intruderToken] = $this->ownerWithBird();
        [,, $victimsBird] = $this->ownerWithBird();

        $this->withToken($intruderToken)
            ->getJson("/api/v1/gamefowls/{$victimsBird->id}/health-history")
            ->assertNotFound();

        $this->withToken($intruderToken)
            ->getJson("/api/v1/gamefowls/{$victimsBird->id}/health-status")
            ->assertNotFound();
    }

    private function createRecord(string $token, Gamefowl $bird, string $title, Carbon $date): int
    {
        return (int) $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-records", [
                'type' => str_contains($title, 'Vet') ? 'vet_visit' : 'weight_check',
                'title' => $title,
                'recorded_at' => $date->toDateString(),
            ])
            ->assertCreated()
            ->json('data.record.id');
    }

    /**
     * @param  array<int, string>  $symptomNames
     * @return array<string, mixed>
     */
    private function submitAssessment(string $token, Gamefowl $bird, array $symptomNames): array
    {
        $symptomIds = array_map(
            fn (string $name) => (int) Symptom::where('name', $name)->value('id'),
            $symptomNames
        );

        $response = $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => $symptomIds,
            ])
            ->assertCreated();

        return ['id' => $response->json('data.id')];
    }

    /**
     * @return array{0: User, 1: string, 2: Gamefowl}
     */
    private function ownerWithBird(): array
    {
        $user = User::factory()->create();
        $bird = Gamefowl::factory()->for($user)->create();

        return [$user, $user->createToken('mobile')->plainTextToken, $bird];
    }
}
