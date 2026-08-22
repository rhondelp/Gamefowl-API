<?php

namespace Tests\Feature\KnowledgeBase;

use App\Models\Disease;
use App\Models\DiseaseSymptomRule;
use App\Models\Symptom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * File: tests/Feature/KnowledgeBase/PublicReadTest.php
 *
 * Purpose:
 *   Verifies the read-visibility split for knowledge-base data.
 *
 * Covers: general endpoints hide deactivated entries AND internal data
 * (rule weights, is_active); admins see everything through /admin/* routes.
 */
class PublicReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_endpoints_hide_deactivated_entries_and_rule_weights(): void
    {
        [, $ownerToken] = $this->userWithRole('owner');

        $active = Disease::create([
            'name' => 'Visible Disease',
            'description' => 'Shown to owners.',
            'severity' => 'moderate',
            'recommended_action' => 'Isolate and consult a vet.',
        ]);
        $inactive = Disease::create([
            'name' => 'Hidden Disease',
            'description' => 'Deactivated, hidden from owners.',
            'severity' => 'mild',
            'recommended_action' => 'Observe.',
            'is_active' => false,
        ]);
        $symptom = Symptom::create([
            'name' => 'Drooping tail',
            'category' => 'physical',
            'severity' => 'mild',
        ]);
        $deactivatedSymptom = Symptom::create([
            'name' => 'Old removed sign',
            'category' => 'physical',
            'severity' => 'mild',
            'is_active' => false,
        ]);
        $rule = DiseaseSymptomRule::create([
            'disease_id' => $active->id,
            'symptom_id' => $symptom->id,
            'weight' => 3,
        ]);

        $list = $this->withToken($ownerToken)->getJson('/api/v1/diseases')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissing(['name' => 'Hidden Disease']);

        $first = $list->json('data.items.0');
        $this->assertSame('Visible Disease', $first['name']);
        $this->assertArrayNotHasKey('is_active', $first);
        $this->assertArrayNotHasKey('weight', $first);
        $this->assertArrayNotHasKey('rules', $first);

        $show = $this->withToken($ownerToken)->getJson("/api/v1/diseases/{$active->id}")
            ->assertOk()
            ->assertJsonPath('data.disease.name', 'Visible Disease');

        $shownDisease = $show->json('data.disease');
        $this->assertArrayNotHasKey('rules', $shownDisease);
        $this->assertArrayNotHasKey('is_active', $shownDisease);

        foreach ($shownDisease['symptoms'] as $exposedSymptom) {
            $this->assertArrayNotHasKey('weight', $exposedSymptom);
        }

        $this->withToken($ownerToken)->getJson("/api/v1/diseases/{$inactive->id}")
            ->assertNotFound()
            ->assertJson(['success' => false]);

        $this->withToken($ownerToken)->getJson('/api/v1/symptoms')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Old removed sign'])
            ->assertJsonPath('data.items.0.name', 'Drooping tail');
    }

    public function test_admin_sees_inactive_entries_and_rule_weights(): void
    {
        [, $ownerToken] = $this->userWithRole('owner');
        [, $adminToken] = $this->userWithRole('admin');

        $disease = Disease::create([
            'name' => 'Admin Only View',
            'description' => 'Inactive entry for admin visibility.',
            'severity' => 'severe',
            'recommended_action' => 'Consult a veterinarian.',
            'is_active' => false,
        ]);
        $symptom = Symptom::create([
            'name' => 'Hidden weight sign',
            'category' => 'behavioral',
            'severity' => 'mild',
        ]);
        $rule = DiseaseSymptomRule::create([
            'disease_id' => $disease->id,
            'symptom_id' => $symptom->id,
            'weight' => 5,
        ]);

        Auth::forgetGuards();

        $adminList = $this->withToken($adminToken)->getJson('/api/v1/admin/diseases')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'Admin Only View', 'is_active' => false])
            ->assertJsonFragment(['symptom_name' => 'Hidden weight sign', 'weight' => 5]);

        $firstItem = $adminList->json('data.items.0');
        $this->assertSame(5, $firstItem['rules'][0]['weight']);
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
