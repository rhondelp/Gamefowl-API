<?php

namespace Tests\Unit\ExpertSystem;

use App\Models\Disease;
use App\Models\Symptom;
use App\Services\ExpertSystem\DiagnosisMatch;
use App\Services\ExpertSystem\DiagnosticEngine;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DiagnosticEngineTest extends TestCase
{
    use RefreshDatabase;

    private DiagnosticEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(KnowledgeBaseSeeder::class);
        $this->engine = new DiagnosticEngine();
    }

    public function test_hand_calculated_example_against_seeded_data(): void
    {
        // Coccidiosis seeded rules and weights:
        //   Bloody droppings 5, Pale comb 4, Ruffled feathers 3,
        //   Huddling together 3, Watery white droppings 3,
        //   Weight loss despite feeding 3, Lethargy or depression 3
        // Total rule weight = 24.
        //
        // Input: Bloody droppings (5) + Pale comb (4) + Lethargy (3)
        // Score = round((5+4+3) / 24 * 100) = round(12 / 24 * 100) = 50.
        $input = [
            $this->symptomId('Bloody droppings'),
            $this->symptomId('Pale comb'),
            $this->symptomId('Lethargy or depression'),
        ];

        $match = $this->matchFor($this->engine->diagnose($input), 'Coccidiosis');

        $this->assertNotNull($match);
        $this->assertSame(50, $match->matchScore);
        $this->assertCount(3, $match->matchedSymptoms);
        $this->assertCount(4, $match->missingSymptoms);
        $this->assertSame('severe', $match->severity);

        $matchedNames = array_column($match->matchedSymptoms, 'name');
        sort($matchedNames);
        $this->assertSame([
            'Bloody droppings',
            'Lethargy or depression',
            'Pale comb',
        ], $matchedNames);

        $missingNames = array_column($match->missingSymptoms, 'name');
        sort($missingNames);
        $this->assertSame([
            'Huddling together',
            'Ruffled feathers',
            'Watery white droppings',
            'Weight loss despite feeding',
        ], $missingNames);
    }

    public function test_second_hand_calculated_example_checks_rounding(): void
    {
        // Newcastle seeded rules and weights:
        //   Greenish watery droppings 5, Twisted neck 5,
        //   Paralysis of legs/wings 4, Gasping/labored breathing 4,
        //   Circling or stargazing 4, Loss of appetite 3,
        //   Sudden death without prior signs 3
        // Total rule weight = 28.
        //
        // Input: Greenish watery droppings (5) + Twisted neck (5)
        //        + Loss of appetite (3)
        // Score = round(13 / 28 * 100) = round(46.428...) = 46.
        $input = [
            $this->symptomId('Greenish watery droppings'),
            $this->symptomId('Twisted neck (torticollis)'),
            $this->symptomId('Loss of appetite'),
        ];

        $match = $this->matchFor($this->engine->diagnose($input), 'Newcastle Disease');

        $this->assertNotNull($match);
        $this->assertSame(46, $match->matchScore);
    }

    public function test_multiple_matches_are_ranked_by_score_descending(): void
    {
        // Overlapping input across three seeded diseases:
        //   Coccidiosis:  (5+4+3)           / 24 * 100 = 50
        //   Fowl Cholera: (4+3+3+2)         / 24 * 100 = 50
        //     (cholera also carries Twisted neck at weight 2 -> ties Coccidiosis,
        //      tie broken alphabetically: "Coccidiosis" < "Fowl Cholera")
        //   Newcastle:    (5+5+3)           / 28 * 100 = 46.43 -> 46
        //   (Coryza scores 9 -> below default threshold of 20; Fowl Pox has no overlap.)
        $input = [
            $this->symptomId('Bloody droppings'),
            $this->symptomId('Pale comb'),
            $this->symptomId('Lethargy or depression'),
            $this->symptomId('Greenish watery droppings'),
            $this->symptomId('Twisted neck (torticollis)'),
            $this->symptomId('Loss of appetite'),
        ];

        $results = $this->engine->diagnose($input);

        $this->assertSame(
            ['Coccidiosis', 'Fowl Cholera', 'Newcastle Disease'],
            $results->map(fn (DiagnosisMatch $m) => $m->diseaseName)->all()
        );
        $this->assertSame([50, 50, 46], $results->map(fn (DiagnosisMatch $m) => $m->matchScore)->all());
    }

    public function test_full_symptom_set_scores_100_with_no_missing(): void
    {
        $input = [
            'Bloody droppings',
            'Pale comb',
            'Ruffled feathers',
            'Huddling together',
            'Watery white droppings',
            'Weight loss despite feeding',
            'Lethargy or depression',
        ];
        $input = array_map(fn (string $name) => $this->symptomId($name), $input);

        $match = $this->matchFor($this->engine->diagnose($input), 'Coccidiosis');

        $this->assertNotNull($match);
        $this->assertSame(100, $match->matchScore);
        $this->assertCount(0, $match->missingSymptoms);
        $this->assertCount(7, $match->matchedSymptoms);
    }

    public function test_inactive_symptom_is_excluded_from_numerator_denominator_and_missing_list(): void
    {
        // Deactivate "Huddling together" (weight 3).
        // Remaining effective rule weight for Coccidiosis = 24 - 3 = 21.
        // Input: Bloody droppings (5) + Pale comb (4) + Ruffled feathers (3)
        //        + Lethargy or depression (3)
        // Score = round(15 / 21 * 100) = round(71.43) = 71.
        Symptom::where('name', 'Huddling together')->update(['is_active' => false]);

        $input = [
            $this->symptomId('Bloody droppings'),
            $this->symptomId('Pale comb'),
            $this->symptomId('Ruffled feathers'),
            $this->symptomId('Lethargy or depression'),
        ];

        $match = $this->matchFor($this->engine->diagnose($input), 'Coccidiosis');

        $this->assertNotNull($match);
        $this->assertSame(71, $match->matchScore);

        $missingNames = array_column($match->missingSymptoms, 'name');
        $this->assertNotContains('Huddling together', $missingNames);

        // Inputting ONLY the inactive symptom yields no candidacy at all.
        $onlyInactive = $this->engine->diagnose([$this->symptomId('Huddling together')]);
        $this->assertNull($this->matchFor($onlyInactive, 'Coccidiosis'));
    }

    public function test_inactive_disease_is_excluded_even_on_perfect_match(): void
    {
        $sign = $this->createSymptom('Test paralysis sign');
        $this->createDisease('Test Sleeping Sickness', 'critical', [
            'Test paralysis sign' => 5,
        ], active: false, vetWarning: 'Highly dangerous.');

        $results = $this->engine->diagnose([$sign->id]);

        $this->assertCount(0, $results);
    }

    public function test_disease_without_rules_is_excluded_without_division_by_zero(): void
    {
        $this->createDisease('Empty Rule Disease', 'moderate', []);

        $results = $this->engine->diagnose([
            $this->symptomId('Bloody droppings'),
        ]);

        $this->assertNull($this->matchFor($results, 'Empty Rule Disease'));
    }

    public function test_duplicate_input_symptom_ids_do_not_inflate_score(): void
    {
        $a = $this->createSymptom('Dup sign A');
        $b = $this->createSymptom('Dup sign B');
        $this->createDisease('Dup Target', 'mild', [
            'Dup sign A' => 2,
            'Dup sign B' => 2,
        ]);

        // Deduplicated: matched weight 2 of 4 total -> round(50) = 50,
        // never (2+2+2)/4 which would exceed 100.
        $inflated = $this->engine->diagnose([$a->id, $a->id, $a->id]);

        $this->assertSame(50, $this->matchFor($inflated, 'Dup Target')->matchScore);
        $this->assertCount(1, $this->matchFor($inflated, 'Dup Target')->matchedSymptoms);
    }

    public function test_unknown_and_non_numeric_symptom_ids_are_ignored_defensively(): void
    {
        $valid = $this->symptomId('Bloody droppings');

        $clean = $this->engine->diagnose([$valid]);
        $messy = $this->engine->diagnose(['abc', null, 999999, $valid, $valid]);

        $cleanScore = $this->matchFor($clean, 'Coccidiosis')->matchScore;
        $messyScore = $this->matchFor($messy, 'Coccidiosis')->matchScore;

        $this->assertSame($cleanScore, $messyScore);
        $this->assertSame(21, $messyScore); // round(5/24*100)
    }

    public function test_threshold_config_excludes_low_scoring_diseases(): void
    {
        $partialInput = [
            $this->symptomId('Bloody droppings'),
            $this->symptomId('Pale comb'),
        ];

        config()->set('expertsystem.min_match_threshold', 60);
        $strict = $this->engine->diagnose($partialInput);
        $this->assertNull($this->matchFor($strict, 'Coccidiosis')); // scores 38 < 60

        $fullInput = array_map(
            fn (string $name) => $this->symptomId($name),
            ['Bloody droppings', 'Pale comb', 'Ruffled feathers', 'Huddling together',
                'Watery white droppings', 'Weight loss despite feeding', 'Lethargy or depression'],
        );
        $stillStrict = $this->engine->diagnose($fullInput);
        $this->assertSame(100, $this->matchFor($stillStrict, 'Coccidiosis')?->matchScore);
    }

    public function test_max_results_config_limits_output(): void
    {
        $input = [
            $this->symptomId('Bloody droppings'),
            $this->symptomId('Pale comb'),
            $this->symptomId('Lethargy or depression'),
            $this->symptomId('Greenish watery droppings'),
            $this->symptomId('Twisted neck (torticollis)'),
            $this->symptomId('Loss of appetite'),
        ];

        config()->set('expertsystem.max_results', 2);
        $results = $this->engine->diagnose($input);

        $this->assertCount(2, $results);
        $this->assertSame(['Coccidiosis', 'Fowl Cholera'],
            $results->map(fn (DiagnosisMatch $m) => $m->diseaseName)->all());
    }

    public function test_equal_scores_break_tie_alphabetically_by_disease_name(): void
    {
        $shared = $this->createSymptom('Shared tie sign');
        $this->createDisease('Zulu Condition', 'critical', ['Shared tie sign' => 3]);
        $this->createDisease('Alpha Condition', 'mild', ['Shared tie sign' => 3]);

        $results = $this->engine->diagnose([$shared->id]);

        $this->assertCount(2, $results);
        $this->assertSame(['Alpha Condition', 'Zulu Condition'],
            $results->map(fn (DiagnosisMatch $m) => $m->diseaseName)->all());
        $this->assertSame([100, 100], $results->map(fn (DiagnosisMatch $m) => $m->matchScore)->all());
    }

    public function test_vet_warning_surfaced_only_from_severe_upward(): void
    {
        $sign = $this->createSymptom('Moderate warning sign');
        $this->createDisease('Moderate Warned Disease', 'moderate', [$sign->name => 5],
            vetWarning: 'Should NOT surface.');

        // Twisted neck alone would score only round(5/28*100) = 18 for
        // Newcastle (below the threshold of 20), so pair it with a
        // hallmark sign to clear candidacy: round(10/28*100) = 36.
        $newcastle = $this->matchFor(
            $this->engine->diagnose([
                $this->symptomId('Twisted neck (torticollis)'),
                $this->symptomId('Greenish watery droppings'),
            ]),
            'Newcastle Disease'
        );

        $this->assertNotNull($newcastle);
        $this->assertSame(36, $newcastle->matchScore);
        $this->assertStringContainsString('notifiable', (string) $newcastle->vetWarning);

        $moderate = $this->engine->diagnose([$sign->id]);
        $this->assertNull($this->matchFor($moderate, 'Moderate Warned Disease')?->vetWarning);
    }

    /**
     * Look up a seeded symptom's ID by its exact name.
     */
    private function symptomId(string $name): int
    {
        return (int) Symptom::where('name', $name)->value('id');
    }

    /**
     * Find one disease's result inside an engine result collection.
     */
    private function matchFor(Collection $results, string $diseaseName): ?DiagnosisMatch
    {
        return $results->first(fn (DiagnosisMatch $m) => $m->diseaseName === $diseaseName);
    }

    private function createSymptom(string $name, int $weight = 0): Symptom
    {
        return Symptom::create([
            'name' => $name,
            'category' => 'test',
            'severity' => 'mild',
        ]);
    }

    /**
     * @param  array<string, int>  $symptomWeights  symptom name => rule weight
     */
    private function createDisease(
        string $name,
        string $severity,
        array $symptomWeights,
        bool $active = true,
        ?string $vetWarning = null,
    ): Disease {
        $disease = Disease::create([
            'name' => $name,
            'description' => "Fixture disease {$name}.",
            'severity' => $severity,
            'recommended_action' => 'Observe and consult a veterinarian.',
            'vet_warning' => $vetWarning,
            'is_active' => $active,
        ]);

        foreach ($symptomWeights as $symptomName => $weight) {
            $symptom = Symptom::where('name', $symptomName)->first()
                ?? Symptom::create([
                    'name' => $symptomName,
                    'category' => 'test',
                    'severity' => 'mild',
                ]);

            $disease->symptoms()->attach($symptom->id, ['weight' => $weight]);
        }

        return $disease;
    }
}
