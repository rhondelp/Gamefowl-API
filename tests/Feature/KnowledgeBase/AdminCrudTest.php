<?php

namespace Tests\Feature\KnowledgeBase;

use App\Models\Disease;
use App\Models\DiseaseSymptomRule;
use App\Models\Recommendation;
use App\Models\Symptom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_is_forbidden_from_all_writes(): void
    {
        [, $token] = $this->userWithRole('owner');

        $attempts = [
            ['POST', '/api/v1/admin/symptoms', ['name' => 'X', 'category' => 'respiratory', 'severity' => 'mild']],
            ['POST', '/api/v1/admin/diseases', ['name' => 'X', 'description' => 'D', 'severity' => 'mild', 'recommended_action' => 'A']],
            ['POST', '/api/v1/admin/recommendations', ['title' => 'T', 'content' => 'C', 'category' => 'hygiene']],
            ['PUT', '/api/v1/admin/symptoms/1', ['name' => 'Y']],
            ['DELETE', '/api/v1/admin/symptoms/1'],
            ['PUT', '/api/v1/admin/diseases/1', ['name' => 'Y']],
            ['DELETE', '/api/v1/admin/diseases/1'],
            ['PUT', '/api/v1/admin/recommendations/1', ['title' => 'Y']],
            ['DELETE', '/api/v1/admin/recommendations/1'],
            ['PUT', '/api/v1/admin/rules/1', ['weight' => 2]],
            ['DELETE', '/api/v1/admin/rules/1'],
        ];

        foreach ($attempts as $attempt) {
            [$method, $url] = $attempt;
            $payload = $attempt[2] ?? [];

            $this->withToken($token)->json($method, $url, $payload)
                ->assertForbidden()
                ->assertJson([
                    'success' => false,
                    'message' => 'Forbidden.',
                ]);
        }
    }

    public function test_admin_can_create_update_and_deactivate_a_symptom(): void
    {
        [, $token] = $this->userWithRole('admin');
        $headers = ['Authorization' => "Bearer {$token}"];

        $created = $this->postJson('/api/v1/admin/symptoms', [
            'name' => 'Droopy wing',
            'description' => 'One wing hanging lower than the other.',
            'category' => 'physical',
            'severity' => 'moderate',
        ], $headers);

        $created->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.symptom.name', 'Droopy wing')
            ->assertJsonPath('data.symptom.is_active', true);

        $id = $created->json('data.symptom.id');

        $this->putJson("/api/v1/admin/symptoms/{$id}", [
            'severity' => 'severe',
        ], $headers)->assertOk()
            ->assertJsonPath('data.symptom.severity', 'severe');

        $this->deleteJson("/api/v1/admin/symptoms/{$id}", [], $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Symptom deactivated successfully.');

        $this->assertDatabaseHas('symptoms', [
            'id' => $id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_manage_rules_with_duplicate_and_weight_validation(): void
    {
        [, $token] = $this->userWithRole('admin');
        $headers = ['Authorization' => "Bearer {$token}"];

        $disease = Disease::create([
            'name' => 'Test Disease',
            'description' => 'A test condition.',
            'severity' => 'mild',
            'recommended_action' => 'Observe closely.',
        ]);
        $symptom = Symptom::create([
            'name' => 'Test Sign',
            'category' => 'physical',
            'severity' => 'mild',
        ]);

        $ruleId = $this->postJson('/api/v1/admin/rules', [
            'disease_id' => $disease->id,
            'symptom_id' => $symptom->id,
            'weight' => 4,
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.rule.weight', 4)
            ->json('data.rule.id');

        $this->assertDatabaseHas('disease_symptom_rules', [
            'disease_id' => $disease->id,
            'symptom_id' => $symptom->id,
            'weight' => 4,
        ]);

        foreach ([0, 6, 'abc'] as $badWeight) {
            $this->putJson("/api/v1/admin/rules/{$ruleId}", ['weight' => $badWeight], $headers)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['weight']);
        }

        $this->postJson('/api/v1/admin/rules', [
            'disease_id' => $disease->id,
            'symptom_id' => $symptom->id,
            'weight' => 2,
        ], $headers)->assertUnprocessable()
            ->assertJsonValidationErrors(['symptom_id']);

        $this->putJson("/api/v1/admin/rules/{$ruleId}", ['weight' => 2], $headers)
            ->assertOk()
            ->assertJsonPath('data.rule.weight', 2);

        $this->deleteJson("/api/v1/admin/rules/{$ruleId}", [], $headers)
            ->assertOk();

        $this->assertDatabaseCount('disease_symptom_rules', 0);
    }

    public function test_admin_can_attach_and_detach_recommendations_to_a_disease(): void
    {
        [, $token] = $this->userWithRole('admin');
        $headers = ['Authorization' => "Bearer {$token}"];

        $disease = Disease::create([
            'name' => 'Linked Disease',
            'description' => 'Condition used for link testing.',
            'severity' => 'moderate',
            'recommended_action' => 'Isolate and monitor.',
        ]);
        $recommendation = Recommendation::create([
            'title' => 'Keep pens clean',
            'content' => 'Clean the pens thoroughly.',
            'category' => 'hygiene',
        ]);

        $this->postJson("/api/v1/admin/diseases/{$disease->id}/recommendations", [
            'recommendation_id' => $recommendation->id,
        ], $headers)->assertCreated();

        $this->postJson("/api/v1/admin/diseases/{$disease->id}/recommendations", [
            'recommendation_id' => $recommendation->id,
        ], $headers)->assertUnprocessable()
            ->assertJsonValidationErrors(['recommendation_id']);

        $this->deleteJson("/api/v1/admin/diseases/{$disease->id}/recommendations/{$recommendation->id}", [], $headers)
            ->assertOk();

        $this->assertDatabaseMissing('disease_recommendations', [
            'disease_id' => $disease->id,
            'recommendation_id' => $recommendation->id,
        ]);
    }

    public function test_unauthenticated_requests_are_rejected_everywhere(): void
    {
        $this->getJson('/api/v1/symptoms')->assertUnauthorized();
        $this->getJson('/api/v1/diseases')->assertUnauthorized();
        $this->postJson('/api/v1/admin/symptoms')->assertUnauthorized();
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function userWithRole(string $role): array
    {
        $user = User::factory()->create(['role' => $role]);
        $token = $user->createToken('mobile')->plainTextToken;

        return [$user, $token];
    }
}
