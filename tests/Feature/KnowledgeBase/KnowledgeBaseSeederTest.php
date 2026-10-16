<?php

namespace Tests\Feature\KnowledgeBase;

use App\Models\Disease;
use App\Models\DiseaseSymptomRule;
use App\Models\Gamefowl;
use App\Models\HealthAssessment;
use App\Models\Recommendation;
use App\Models\Symptom;
use App\Models\User;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * File: tests/Feature/KnowledgeBase/KnowledgeBaseSeederTest.php
 *
 * Purpose:
 *   Validates KnowledgeBaseSeeder output: exact counts (5 diseases, 23
 *   symptoms, 10 recommendations, 25+ rules), minimum content per disease,
 *   weights within 1-5, no duplicate pairs, and the five expected disease
 *   names present. This seeder is what the engine's hand-calculated tests
 *   and Milestone 6+ flows run against.
 *
 *   Also covers re-seeding a live database: no FK errors from assessment
 *   history, no duplicates, assessments untouched, and admin-edited weights
 *   kept unless SEED_RESET_WEIGHTS=true.
 */
class KnowledgeBaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_produces_a_realistic_and_consistent_knowledge_base(): void
    {
        $this->seed(\Database\Seeders\KnowledgeBaseSeeder::class);

        $this->assertSame(5, Disease::count());
        $this->assertSame(23, Symptom::count());
        $this->assertSame(10, Recommendation::count());
        $this->assertGreaterThanOrEqual(25, DiseaseSymptomRule::count());

        $diseases = Disease::with(['symptoms', 'recommendations'])->get();

        foreach ($diseases as $disease) {
            $this->assertGreaterThanOrEqual(
                5,
                $disease->symptoms->count(),
                "{$disease->name} should have at least 5 symptoms."
            );
            $this->assertGreaterThanOrEqual(
                4,
                $disease->recommendations->count(),
                "{$disease->name} should have at least 4 recommendations."
            );

            foreach ($disease->symptoms as $symptom) {
                $this->assertGreaterThanOrEqual(1, (int) $symptom->pivot->weight);
                $this->assertLessThanOrEqual(5, (int) $symptom->pivot->weight);
            }
        }

        $duplicatePairs = DB::table('disease_symptom_rules')
            ->select('disease_id', 'symptom_id', DB::raw('COUNT(*) as total'))
            ->groupBy('disease_id', 'symptom_id')
            ->having('total', '>', 1)
            ->count();

        $this->assertSame(0, $duplicatePairs);

        $names = Disease::pluck('name')->all();
        $this->assertContains('Infectious Coryza', $names);
        $this->assertContains('Fowl Pox', $names);
        $this->assertContains('Newcastle Disease', $names);
        $this->assertContains('Coccidiosis', $names);
        $this->assertContains('Fowl Cholera', $names);
    }

    public function test_reseeding_a_database_with_assessment_history_is_safe_and_idempotent(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);

        $assessmentId = $this->submitAssessment(['Bloody droppings', 'Pale comb', 'Lethargy or depression']);
        $before = $this->assessmentSnapshot($assessmentId);
        $this->assertCount(3, $before['symptoms']);
        $this->assertNotEmpty($before['results']);

        $editedRule = $this->rule('Coccidiosis', 'Bloody droppings');
        $editedRule->update(['weight' => 1]);

        $this->seed(KnowledgeBaseSeeder::class);

        $this->assertKnowledgeBaseCounts();
        $this->assertSame($before, $this->assessmentSnapshot($assessmentId));
        $this->assertSame(1, $editedRule->fresh()->weight, 'Admin-edited weight must survive a default re-seed.');
    }

    public function test_reset_weights_flag_restores_seeded_weights_on_existing_rules(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);
        $assessmentId = $this->submitAssessment(['Bloody droppings']);
        $before = $this->assessmentSnapshot($assessmentId);

        $editedRule = $this->rule('Coccidiosis', 'Bloody droppings');
        $editedRule->update(['weight' => 1]);

        $_SERVER['SEED_RESET_WEIGHTS'] = $_ENV['SEED_RESET_WEIGHTS'] = 'true';

        try {
            $this->seed(KnowledgeBaseSeeder::class);
        } finally {
            unset($_SERVER['SEED_RESET_WEIGHTS'], $_ENV['SEED_RESET_WEIGHTS']);
        }

        $this->assertKnowledgeBaseCounts();
        $this->assertSame($before, $this->assessmentSnapshot($assessmentId));
        $this->assertSame(5, $editedRule->fresh()->weight);
    }

    private function assertKnowledgeBaseCounts(): void
    {
        $this->assertSame(5, Disease::count());
        $this->assertSame(23, Symptom::count());
        $this->assertSame(10, Recommendation::count());
        $this->assertSame(32, DiseaseSymptomRule::count());
        $this->assertSame(21, DB::table('disease_recommendations')->count());
    }

    /**
     * Submit through the real endpoint so symptom and result rows reference
     * the knowledge base through their RESTRICT FKs.
     *
     * @param  list<string>  $symptomNames
     */
    private function submitAssessment(array $symptomNames): int
    {
        $user = User::factory()->create();
        $bird = Gamefowl::factory()->for($user)->create();

        $this->withToken($user->createToken('mobile')->plainTextToken)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => Symptom::whereIn('name', $symptomNames)->pluck('id')->all(),
                'duration_of_symptoms' => '1_to_3_days',
            ])
            ->assertCreated();

        return (int) HealthAssessment::where('gamefowl_id', $bird->id)->value('id');
    }

    /**
     * @return array{assessment: array<string, mixed>, symptoms: list<array<string, mixed>>, results: list<array<string, mixed>>}
     */
    private function assessmentSnapshot(int $assessmentId): array
    {
        $rows = fn (string $table) => DB::table($table)
            ->where('health_assessment_id', $assessmentId)->orderBy('id')->get()
            ->map(fn ($row) => (array) $row)->all();

        return [
            'assessment' => (array) DB::table('health_assessments')->find($assessmentId),
            'symptoms' => $rows('health_assessment_symptoms'),
            'results' => $rows('health_assessment_results'),
        ];
    }

    private function rule(string $diseaseName, string $symptomName): DiseaseSymptomRule
    {
        return DiseaseSymptomRule::where('disease_id', Disease::where('name', $diseaseName)->value('id'))
            ->where('symptom_id', Symptom::where('name', $symptomName)->value('id'))
            ->firstOrFail();
    }
}
