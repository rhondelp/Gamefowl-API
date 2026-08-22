<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * File: database/factories/UserFactory.php
 *
 * Purpose:
 *   Test fixture builder for User accounts. Used by every feature test that
 *   needs an authenticated user; tests pass ['role' => 'admin'] explicitly
 *   when they need an admin (the default state relies on the DB default,
 *   which is 'owner').
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Default attribute set for a new test user. The password is hashed once
     * per process and reused (static::$password) so bcrypt cost doesn't slow
     * down large suites — tests log in with the plain value 'password'.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * State: mark the account's email_verified_at as null (unused by the
     * current API but kept for framework compatibility).
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
