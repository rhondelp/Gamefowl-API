<?php

namespace Tests\Feature;

use App\Models\Symptom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SymptomApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_symptom_endpoint_hides_sensitive_database_columns(): void
    {
        // 1. Create a user with the 'owner' role and generate a Sanctuam token
        $owner = User::factory()->create(['role' => 'owner']);
        $token = $owner->createToken('mobile')->plainTextToken;

        // 2. Insert a real test record into your symptoms table
        $symptom = Symptom::create([
            'name' => 'Bloody droppings',
            'description' => 'Visible signs of blood in the fecal matter.',
            'category' => 'Digestive',
            'severity' => 'High',
            'is_active' => true,
        ]);

        // 3. Make the API request to the index route as the authorized owner
        $response = $this->withToken($token)
            ->getJson('/api/v1/symptoms');

        // 4. Assertions: Prove that the public fields exist...
        $response->assertOk()
            ->assertJsonPath('data.items.0.name', 'Bloody droppings')
            ->assertJsonPath('data.items.0.category', 'Digestive');

        // 5. Assertions: ...and strictly prove that the sensitive fields are entirely HIDDEN
        $response->assertJsonMissing([
            'is_active' => true,
            'created_at' => $symptom->created_at->toJson(),
            'updated_at' => $symptom->updated_at->toJson(),
        ]);

        // Double-check by ensuring the keys don't exist anywhere in the first item's payload
        $firstItem = $response->json('data.items.0');
        $this->assertArrayNotHasKey('is_active', $firstItem);
        $this->assertArrayNotHasKey('created_at', $firstItem);
        $this->assertArrayNotHasKey('updated_at', $firstItem);
    }
}
