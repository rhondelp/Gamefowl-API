<?php

namespace Tests\Feature;

use App\Http\Resources\HealthAssessmentResource;
use App\Models\Disease;
use App\Models\Gamefowl;
use App\Models\Symptom;
use App\Models\User;
use App\Services\ExpertSystem\DiagnosisMatch;
use App\Services\ExpertSystem\DiagnosticEngine;
use Database\Seeders\KnowledgeBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * File: tests/Feature/DocumentationPageTest.php
 *
 * Purpose:
 *   Coverage for the documentation page (GET/POST /documentation and
 *   GET /documentation/view).
 *
 * Covers two things:
 *   1. The shared-password gate: locked by default, wrong and empty
 *      passwords rejected, the right one unlocks for the rest of the
 *      session, an unset DOCS_PASSWORD keeps the page locked, attempts are
 *      throttled, and the landing page links to it.
 *   2. Accuracy: the page quotes real numbers, so they are recomputed here
 *      with the real engine and the real assessment endpoint against the
 *      seeded knowledge base, and the page's knowledge-base snapshot in
 *      config/documentation.php is compared with KnowledgeBaseSeeder. A
 *      seeder or engine change that makes the page wrong fails here until
 *      the page is updated to match.
 */
class DocumentationPageTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'panel-preview';

    protected function setUp(): void
    {
        parent::setUp();

        // Independent of whatever DOCS_PASSWORD the local .env holds.
        config(['documentation.password' => self::PASSWORD]);
    }

    public function test_gate_shows_the_password_form(): void
    {
        $this->get('/documentation')
            ->assertOk()
            ->assertSee('How the expert system works')
            ->assertSee('name="password"', false)
            ->assertDontSee("hasn't been set up", false);
    }

    public function test_documentation_redirects_to_the_gate_while_locked(): void
    {
        $this->get('/documentation/view')->assertRedirect('/documentation');
    }

    public function test_wrong_password_is_rejected_and_the_page_stays_locked(): void
    {
        $response = $this->from('/documentation')
            ->post('/documentation', ['password' => 'not-the-password']);

        $response->assertRedirect('/documentation')
            ->assertSessionHasErrors(['password' => 'That password is incorrect. Please try again.'])
            ->assertSessionMissing('docs_unlocked');

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('That password is incorrect.');

        $this->get('/documentation/view')->assertRedirect('/documentation');
    }

    public function test_empty_password_is_rejected(): void
    {
        $this->from('/documentation')
            ->post('/documentation', ['password' => ''])
            ->assertRedirect('/documentation')
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('docs_unlocked');
    }

    public function test_correct_password_unlocks_the_page_for_the_rest_of_the_session(): void
    {
        $this->from('/documentation')
            ->post('/documentation', ['password' => self::PASSWORD])
            ->assertRedirect('/documentation/view')
            ->assertSessionHas('docs_unlocked', true);

        // Stays unlocked: repeat visits go straight in, and the form itself
        // forwards to the page instead of asking again.
        $this->get('/documentation/view')
            ->assertOk()
            ->assertSee('How the GAMEFOWL expert system works')
            ->assertSee(HealthAssessmentResource::DISCLAIMER);
        $this->get('/documentation/view')->assertOk();
        $this->get('/documentation')->assertRedirect('/documentation/view');
    }

    public function test_page_cannot_be_unlocked_while_no_password_is_configured(): void
    {
        config(['documentation.password' => null]);

        $this->get('/documentation')
            ->assertOk()
            ->assertSee("hasn't been set up", false);

        // Neither an empty nor an arbitrary submission gets through.
        $this->from('/documentation')
            ->post('/documentation', ['password' => ''])
            ->assertSessionHasErrors('password');
        $this->from('/documentation')
            ->post('/documentation', ['password' => 'anything'])
            ->assertSessionHasErrors('password');

        $this->get('/documentation/view')->assertRedirect('/documentation');
    }

    public function test_unlock_attempts_are_rate_limited(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->post('/documentation', ['password' => "guess-{$attempt}"])->assertRedirect();
        }

        // Once the limit is hit, even the right password has to wait.
        $this->post('/documentation', ['password' => self::PASSWORD])->assertTooManyRequests();
    }

    public function test_landing_page_links_to_the_documentation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('View Full Documentation')
            ->assertSee('href="'.route('docs.gate').'"', false);
    }

    public function test_knowledge_base_snapshot_matches_the_seeder(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);
        $snapshot = config('documentation.knowledge_base');

        $seededSymptoms = Symptom::pluck('category', 'name')->sortKeys()->all();
        $documentedSymptoms = collect($snapshot['symptoms'])->sortKeys()->all();
        $this->assertSame($seededSymptoms, $documentedSymptoms);

        $seededDiseases = Disease::with('symptoms')->orderBy('name')->get()
            ->map(fn (Disease $disease) => [
                'name' => $disease->name,
                'severity' => $disease->severity,
                'rules' => $disease->symptoms
                    ->mapWithKeys(fn (Symptom $symptom) => [$symptom->name => $symptom->pivot->weight])
                    ->sortKeys()
                    ->all(),
            ])
            ->all();

        $documentedDiseases = collect($snapshot['diseases'])->sortBy('name')->values()
            ->map(fn (array $disease) => [
                'name' => $disease['name'],
                'severity' => $disease['severity'],
                'rules' => collect($disease['rules'])->sortKeys()->all(),
            ])
            ->all();

        $this->assertSame($seededDiseases, $documentedDiseases);
    }

    public function test_section_3_numbers_match_the_engine(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);
        $report = ['Bloody droppings', 'Pale comb', 'Lethargy or depression'];

        // What the owner sees: Coccidiosis alone.
        $this->assertSame(['Coccidiosis' => 50], $this->scores($report));

        // The hidden scores the page quotes, with the 20% cut-off switched off.
        config(['expertsystem.min_match_threshold' => 0]);
        $this->assertSame(
            ['Coccidiosis' => 50, 'Fowl Cholera' => 13, 'Fowl Pox' => 13],
            $this->scores($report),
        );
    }

    public function test_weights_versus_counting_examples_match_the_engine(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);

        $this->assertSame(['Coccidiosis' => 38], $this->scores(['Bloody droppings', 'Pale comb']));
        $this->assertSame(['Coccidiosis' => 25], $this->scores(['Ruffled feathers', 'Huddling together']));
    }

    public function test_section_6_walkthrough_matches_the_real_api(): void
    {
        $this->seed(KnowledgeBaseSeeder::class);
        $report = ['Bloody droppings', 'Pale comb', 'Lethargy or depression', 'Loss of appetite'];

        $owner = User::factory()->create();
        $bird = Gamefowl::factory()->for($owner)->create();
        $token = $owner->createToken('mobile')->plainTextToken;

        $results = $this->withToken($token)
            ->postJson("/api/v1/gamefowls/{$bird->id}/health-assessments", [
                'symptom_ids' => $this->symptomIds($report),
            ])
            ->assertCreated()
            ->assertJsonPath('data.disclaimer', HealthAssessmentResource::DISCLAIMER)
            ->json('data.results');

        $this->assertCount(2, $results);
        [$coccidiosis, $cholera] = $results;

        // Result card 1, as shown on the results mockup.
        $this->assertSame(1, $coccidiosis['rank']);
        $this->assertSame('Coccidiosis', $coccidiosis['possible_disease']['name']);
        $this->assertSame(50, $coccidiosis['match_score']);
        $this->assertSame('severe', $coccidiosis['severity_at_assessment']);
        $this->assertNull($coccidiosis['vet_warning_at_assessment']);
        $this->assertEqualsCanonicalizing(
            ['Bloody droppings', 'Pale comb', 'Lethargy or depression'],
            $coccidiosis['matched_symptoms'],
        );
        $this->assertEqualsCanonicalizing(
            ['Ruffled feathers', 'Weight loss despite feeding', 'Watery white droppings', 'Huddling together'],
            $coccidiosis['missing_symptoms'],
        );
        $this->assertCount(4, $coccidiosis['recommendations']);
        $this->assertSame('Keep litter dry and replace soiled bedding', $coccidiosis['recommendations'][0]['title']);

        // Result card 2.
        $this->assertSame(2, $cholera['rank']);
        $this->assertSame('Fowl Cholera', $cholera['possible_disease']['name']);
        $this->assertSame(25, $cholera['match_score']);
        $this->assertSame('severe', $cholera['severity_at_assessment']);
        $this->assertSame(
            'Fowl cholera can recur in the same facility through carrier birds. Seek veterinary guidance before restocking after an outbreak.',
            $cholera['vet_warning_at_assessment'],
        );

        // "Bruno's health status now reads Needs attention."
        $this->withToken($token)
            ->getJson("/api/v1/gamefowls/{$bird->id}/health-status")
            ->assertOk()
            ->assertJsonPath('data.status', 'needs_attention');

        // The hidden scores on the step C scoring list.
        config(['expertsystem.min_match_threshold' => 0]);
        $this->assertSame(
            [
                'Coccidiosis' => 50,
                'Fowl Cholera' => 25,
                'Fowl Pox' => 13,
                'Newcastle Disease' => 11,
                'Infectious Coryza' => 10,
            ],
            $this->scores($report),
        );
    }

    /**
     * Engine output for a list of symptom names, as disease => score in
     * ranked order (assertSame on the result also checks that order).
     *
     * @param  array<int, string>  $names
     * @return array<string, int>
     */
    private function scores(array $names): array
    {
        return app(DiagnosticEngine::class)->diagnose($this->symptomIds($names))
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
