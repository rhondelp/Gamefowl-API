<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

/**
 * File: tests/Feature/Auth/MeTest.php
 *
 * Purpose:
 *   Tests GET /api/v1/auth/me: returns the authenticated user's profile via
 *   UserResource without leaking sensitive fields (password/tokens), and
 *   returns 401 when no token is presented.
 */
class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_authenticated_user_without_sensitive_fields(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
                ->where('data.user.id', $user->id)
                ->where('data.user.name', $user->name)
                ->where('data.user.email', $user->email)
                ->where('data.user.role', 'owner')
                ->has('data.user.created_at')
                ->missing('data.user.password')
                ->missing('data.user.remember_token')
                ->etc()
            );
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }
}
