<?php

namespace Tests\Feature;

use App\Models\Gamefowl;
use App\Models\Symptom;
use App\Models\User;
use App\Services\ExpertSystem\DiagnosisMatch;
use App\Services\ExpertSystem\DiagnosticEngine;
use App\Support\CodeExcerpt;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * File: tests/Feature/TechnicalDocumentationPageTest.php
 *
 * Purpose:
 *   Coverage for the code-level documentation page
 *   (GET /documentation/technical).
 *
 * Covers: the shared password gate (including landing back on this page
 * after unlocking from a link to it), the links between the two
 * documentation pages, every live code excerpt resolving against the real
 * files, and the worked examples: each documented input/output is read back
 * out of the rendered page and recomputed with the real engine and the real
 * endpoint, and each documented score is checked against the assertion in
 * the unit test it reproduces.
 */
class TechnicalDocumentationPageTest extends TestCase
{
    use RefreshDatabase;

    private const ENGINE_TEST = 'tests/Unit/ExpertSystem/DiagnosticEngineTest.php';

    protected function setUp(): void
    {
        parent::setUp();

        config(['documentation.password' => 'panel-preview']);
    }

    public function test_technical_page_sits_behind_the_same_password_gate(): void
    {
        $this->get('/documentation/technical')->assertRedirect('/documentation');

        // Unlocking after following a link to this page lands back here.
        $this->post('/documentation', ['password' => 'panel-preview'])
            ->assertRedirect('/documentation/technical');

        $this->get('/documentation/technical')->assertOk();
    }

    public function test_every_code_excerpt_is_read_from_the_real_files(): void
    {
        $this->unlocked()
            ->get('/documentation/technical')
            ->assertOk()
            ->assertDontSee(CodeExcerpt::MISSING)
            ->assertSee('app/Services/ExpertSystem/DiagnosticEngine.php')
            ->assertSee('$score = (int) round(($matchedWeight / $totalWeight) * 100);')
            ->assertSee('if ($totalWeight <= 0) {')
            ->assertSee('if ($matched->isEmpty()) {')
            ->assertSee("Rule::exists('symptoms', 'id')->where('is_active', true),")
            ->assertSee('<pre class="code-gutter" aria-hidden="true">8'."\n".'9', false)
            ->assertSee('<b>1</b>', false)
            ->assertSee('<b>6</b>', false);
    }

    public function test_the_two_documentation_pages_link_to_each_other(): void
    {
        $this->unlocked()
            ->get('/documentation/view')
            ->assertSee('View Technical Documentation')
            ->assertSee('href="'.route('docs.technical').'"', false);

        $this->unlocked()
            ->get('/documentation/technical')
            ->assertSee('← Back to Overview Documentation')
            ->assertSee('href="'.route('docs.show').'"', false)
            ->assertSee('href="'.route('docs.show').'#snapshots"', false);

        $this->unlocked()
            ->get('/documentation/view')
            ->assertSee('id="snapshots"', false);
    }

    public function test_example_a_matches_the_engine_the_endpoint_and_the_unit_test(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);
        $html = $this->unlocked()->get('/documentation/technical')->getContent();

        // The IDs the page uses are the fresh-seed IDs.
        $this->assertSame([15, 10, 21], $this->symptomIds(['Bloody droppings', 'Pale comb', 'Lethargy or depression']));

        $matches = app(DiagnosticEngine::class)->diagnose([15, 10, 21]);
        $this->assertCount(1, $matches);
        $this->assertSame($this->documented($html, 'a-match'), $this->dump($matches->first()));

        // The trace table's hidden rows.
        config(['expertsystem.min_match_threshold' => 0]);
        $this->assertSame(['Coccidiosis' => 50, 'Fowl Cholera' => 13, 'Fowl Pox' => 13], $this->scores([15, 10, 21]));
        config(['expertsystem.min_match_threshold' => 20]);

        // The documented request, sent to the real endpoint, returns the documented response.
        $owner = User::factory()->create();
        $bird = Gamefowl::factory()->for($owner)->create(['sex' => 'male', 'date_of_birth' => null]);

        $actual = $this->withToken($owner->createToken('mobile')->plainTextToken)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", json_decode($this->documented($html, 'a-request'), true))
            ->assertCreated()
            ->json();
        $expected = json_decode($this->documented($html, 'a-response'), true);
        unset($actual['data']['created_at'], $expected['data']['created_at']);
        $this->assertSame($expected, $actual);

        // Same number the unit test asserts.
        $this->assertStringContainsString(
            '$this->assertSame(50, $match->matchScore);',
            CodeExcerpt::method(self::ENGINE_TEST, 'test_hand_calculated_example_against_seeded_data')->code,
        );
    }

    public function test_example_b_matches_the_endpoint_the_engine_and_the_unit_tests(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);
        $html = $this->unlocked()->get('/documentation/technical')->getContent();

        // Over HTTP: a deactivated symptom is rejected with the documented 422.
        $this->assertSame([2], $this->symptomIds(['Sneezing']));
        Symptom::where('name', 'Sneezing')->update(['is_active' => false]);

        $owner = User::factory()->create();
        $bird = Gamefowl::factory()->for($owner)->create();

        $actual = $this->withToken($owner->createToken('mobile')->plainTextToken)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", json_decode($this->documented($html, 'b-request'), true))
            ->assertUnprocessable()
            ->json();
        $this->assertSame(json_decode($this->documented($html, 'b-rejection'), true), $actual);

        Symptom::where('name', 'Sneezing')->update(['is_active' => true]);

        // Calling the engine directly: the deactivated rule leaves both sums.
        $this->assertSame([23, 11], $this->symptomIds(['Huddling together', 'Ruffled feathers']));
        Symptom::where('name', 'Huddling together')->update(['is_active' => false]);

        $matches = app(DiagnosticEngine::class)->diagnose([15, 10, 11, 21]);
        $this->assertCount(1, $matches);
        $this->assertSame($this->documented($html, 'b-match'), $this->dump($matches->first()));

        $this->assertCount(0, app(DiagnosticEngine::class)->diagnose([23]));
        $this->assertSame('collect([])', $this->documented($html, 'b-empty'));

        config(['expertsystem.min_match_threshold' => 0]);
        $this->assertSame(['Coccidiosis' => 71, 'Fowl Cholera' => 13, 'Fowl Pox' => 13], $this->scores([15, 10, 11, 21]));

        // Same outcomes the two tests assert.
        $this->assertStringContainsString(
            '$this->assertSame(71, $match->matchScore);',
            CodeExcerpt::method(self::ENGINE_TEST, 'test_inactive_symptom_is_excluded_from_numerator_denominator_and_missing_list')->code,
        );
        $this->assertStringContainsString(
            "->assertJsonValidationErrors(['symptom_ids.0']);",
            CodeExcerpt::method('tests/Feature/HealthAssessmentTest.php', 'test_inactive_or_nonexistent_symptom_ids_are_rejected_here_not_by_the_engine')->code,
        );
    }

    public function test_example_c_matches_the_engine_and_the_unit_test(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);
        $html = $this->unlocked()->get('/documentation/technical')->getContent();

        $messy = app(DiagnosticEngine::class)->diagnose(['abc', null, 999999, 15, 15]);
        $clean = app(DiagnosticEngine::class)->diagnose([15]);

        $this->assertCount(1, $messy);
        $this->assertSame($this->documented($html, 'c-match'), $this->dump($messy->first()));
        $this->assertSame($this->dump($clean->first()), $this->dump($messy->first()));

        $this->assertStringContainsString(
            '$this->assertSame(21, $messyScore);',
            CodeExcerpt::method(self::ENGINE_TEST, 'test_unknown_and_non_numeric_symptom_ids_are_ignored_defensively')->code,
        );
    }

    public function test_the_test_table_lists_every_engine_test_in_order(): void
    {
        preg_match_all('/public function (test_\w+)\(/', file_get_contents(base_path(self::ENGINE_TEST)), $inFile);

        $html = $this->unlocked()->get('/documentation/technical')->getContent();
        preg_match_all('~<td><code>(test_\w+)</code></td>~', $html, $onPage);

        $this->assertSame($inFile[1], $onPage[1]);
    }

    private function unlocked(): static
    {
        return $this->withSession(['docs_unlocked' => true]);
    }

    /**
     * Text of the code block the page marks with data-example="$name".
     */
    private function documented(string $html, string $name): string
    {
        $found = preg_match('~data-example="'.preg_quote($name, '~').'">(.*?)</code>~s', $html, $match);
        $this->assertSame(1, $found, "The page has no example block named {$name}.");

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
    }

    /**
     * A DiagnosisMatch written the way the page shows it: as the named-argument
     * constructor call the engine makes in Step 3d.
     */
    private function dump(DiagnosisMatch $match): string
    {
        $symptoms = fn (array $items) => $items === [] ? '[]' : "[\n".implode("\n", array_map(
            fn (array $symptom) => "        ['id' => {$symptom['id']}, 'name' => ".var_export($symptom['name'], true).'],',
            $items,
        ))."\n    ]";

        return implode("\n", [
            'new DiagnosisMatch(',
            "    diseaseId: {$match->diseaseId},",
            '    diseaseName: '.var_export($match->diseaseName, true).',',
            "    matchScore: {$match->matchScore},",
            '    matchedSymptoms: '.$symptoms($match->matchedSymptoms).',',
            '    missingSymptoms: '.$symptoms($match->missingSymptoms).',',
            '    severity: '.var_export($match->severity, true).',',
            '    vetWarning: '.($match->vetWarning === null ? 'null' : var_export($match->vetWarning, true)).',',
            ')',
        ]);
    }

    /**
     * @param  array<int, int>  $symptomIds
     * @return array<string, int>  disease => score, in ranked order
     */
    private function scores(array $symptomIds): array
    {
        return app(DiagnosticEngine::class)->diagnose($symptomIds)
            ->mapWithKeys(fn (DiagnosisMatch $match) => [$match->diseaseName => $match->matchScore])
            ->all();
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    private function symptomIds(array $names): array
    {
        return array_map(fn (string $name) => (int) Symptom::where('name', $name)->value('id'), $names);
    }
}
