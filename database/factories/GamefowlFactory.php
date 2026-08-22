<?php

namespace Database\Factories;

use App\Models\Gamefowl;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * File: database/factories/GamefowlFactory.php
 *
 * Purpose:
 *   Test fixture builder for Gamefowl profiles. Produces realistic birds
 *   (real breed/color names, plausible dates and weights) so tests that list
 *   or score them read like production data. ->for($user) sets the owner.
 *
 * @extends Factory<Gamefowl>
 */
class GamefowlFactory extends Factory
{
    /**
     * Default attribute set. Most fields are wrapped in optional() so tests
     * exercise both filled and null paths; is_active defaults to true since
     * that mirrors real creation via the API.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->firstName(),
            'breed' => fake()->randomElement(['Hatch', 'Kelso', 'Sweater', 'Roundhead']),
            'date_of_birth' => fake()->optional()->dateTimeBetween('-3 years', '-6 months'),
            'sex' => fake()->randomElement(['male', 'female', 'unknown']),
            'color' => fake()->optional()->randomElement(['Black', 'Red', 'White', 'Grey']),
            'weight' => fake()->optional()->randomFloat(2, 2, 5),
            'date_acquired' => fake()->optional()->dateTimeBetween('-2 years', 'now'),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * State: an inactive (retired) bird — hidden from default listings but
     * visible with ?include_inactive=1.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
