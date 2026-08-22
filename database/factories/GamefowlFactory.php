<?php

namespace Database\Factories;

use App\Models\Gamefowl;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gamefowl>
 */
class GamefowlFactory extends Factory
{
    /**
     * Define the model's default state.
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

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
