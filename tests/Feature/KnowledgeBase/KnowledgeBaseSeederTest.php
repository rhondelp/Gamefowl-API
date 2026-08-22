<?php

namespace Tests\Feature\KnowledgeBase;

use App\Models\Disease;
use App\Models\DiseaseSymptomRule;
use App\Models\Recommendation;
use App\Models\Symptom;
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
}
