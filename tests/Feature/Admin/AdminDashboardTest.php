<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\Gamefowl;
use App\Models\HealthAssessment;
use App\Models\User;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * File: tests/Feature/Admin/AdminDashboardTest.php
 *
 * Purpose:
 *   Milestone 8 coverage: dashboard statistics against an exact fixture
 *   (4 users incl. one deactivated, 3 birds, 3 assessments).
 *
 * Pins exact counts for every stat AND the documented definition of
 * "suggested": a disease appearing twice at rank #2 outranks one appearing
 * once at rank #1. Recent assessments are asserted newest-first with bird
 * linkage and no nested result dumps.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(KnowledgeBaseSeeder::class);
    }

    public function test_dashboard_stats_are_numerically_correct(): void
    {
        [, $token] = $this->userWithRole('admin');

        // Fixture: 4 users — 1 admin + 3 owners, one owner deactivated.
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $inactiveOwner = User::factory()->create();
        $inactiveOwner->delete();

        // 3 active birds (2 for A, 1 for B).
        $birdA1 = Gamefowl::factory()->for($ownerA)->create();
        $birdA2 = Gamefowl::factory()->for($ownerA)->create();
        $birdB1 = Gamefowl::factory()->for($ownerB)->create();

        $sym = fn (string $name) => (int) \App\Models\Symptom::where('name', $name)->value('id');
        $diseaseId = fn (string $name) => (int) Disease::where('name', $name)->value('id');

        // Assessment 1: two results — Coccidiosis #1, Fowl Cholera #2 (rank 2 only).
        $a1 = $this->createAssessment($birdA1, ['Bloody droppings', 'Pale comb']);
        $this->addResult($a1, $diseaseId('Coccidiosis'), 'Coccidiosis', 1, 38);
        $this->addResult($a1, $diseaseId('Fowl Cholera'), 'Fowl Cholera', 2, 29);

        // Assessment 2: Coryza #1, plus a second Fowl Cholera appearance at rank 2.
        $a2 = $this->createAssessment($birdA2, ['Sneezing']);
        $this->addResult($a2, $diseaseId('Infectious Coryza'), 'Infectious Coryza', 1, 45);
        $this->addResult($a2, $diseaseId('Fowl Cholera'), 'Fowl Cholera', 2, 20);

        // Assessment 3 (other owner's bird): Coccidiosis #1, Newcastle #2.
        $a3 = $this->createAssessment($birdB1, ['Bloody droppings']);
        $this->addResult($a3, $diseaseId('Coccidiosis'), 'Coccidiosis', 1, 21);
        $this->addResult($a3, $diseaseId('Newcastle Disease'), 'Newcastle Disease', 2, 15);

        $data = $this->withToken($token)
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data');

        $this->assertSame(4, $data['total_users']);
        $this->assertSame(['admin' => 1, 'owner' => 3], array_map('intval', $data['users_by_role']));
        $this->assertSame(['active' => 3, 'inactive' => 1], array_map('intval', $data['users_by_active_status']));

        $this->assertSame(3, $data['total_gamefowls']);
        $this->assertSame(3, $data['total_assessments']);

        // Symptom frequency across submissions: Bloody droppings x2,
        // then single-appearance symptoms alphabetically.
        $topSymptoms = collect($data['most_frequently_reported_symptoms']);
        $this->assertSame([
            ['Bloody droppings', 2],
            ['Pale comb', 1],
            ['Sneezing', 1],
        ], $topSymptoms->map(fn ($row) => [$row['name'], (int) $row['report_count']])->all());

        // "Suggested" = appeared ANYWHERE in results (documented definition).
        // Fowl Cholera (twice at rank #2, never #1) therefore OUTRANKS
        // Infectious Coryza (once at #1): counts 2 vs 1.
        $topDiseases = collect($data['most_frequently_suggested_diseases']);
        $this->assertSame([
            ['Coccidiosis', 2],
            ['Fowl Cholera', 2],
            ['Infectious Coryza', 1],
            ['Newcastle Disease', 1],
        ], $topDiseases->map(fn ($row) => [$row['name'], (int) $row['suggestion_count']])->all());

        // Recent assessments: newest first, summarized with bird linkage.
        $recent = collect($data['recent_assessments']);
        $this->assertCount(3, $recent);
        $this->assertSame(
            [$a3->id, $a2->id, $a1->id],
            $recent->pluck('id')->all()
        );
        $first = $recent->first();
        $this->assertSame($birdB1->name, $first['gamefowl_name']);
        // Top result = rank #1, which for this assessment is Coccidiosis
        // (Newcastle was attached at rank #2).
        $this->assertSame('Coccidiosis', $first['top_possible_disease']['name']);
        $this->assertArrayNotHasKey('results', $first);
    }

    /**
     * @param  array<int, string>  $symptomNames
     */
    private function createAssessment(Gamefowl $bird, array $symptomNames): HealthAssessment
    {
        $assessment = HealthAssessment::create(['gamefowl_id' => $bird->id]);
        $assessment->symptoms()->attach(collect($symptomNames)
            ->mapWithKeys(fn (string $name) => [
                (int) \App\Models\Symptom::where('name', $name)->value('id') => ['symptom_name' => $name],
            ]));

        return $assessment;
    }

    private function addResult(
        HealthAssessment $assessment,
        int $diseaseId,
        string $diseaseName,
        int $rank,
        int $score,
    ): void {
        $assessment->results()->create([
            'disease_id' => $diseaseId,
            'disease_name' => $diseaseName,
            'rank' => $rank,
            'match_score' => $score,
            'matched_symptoms' => [],
            'missing_symptoms' => [],
            'severity_at_assessment' => 'moderate',
        ]);
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
