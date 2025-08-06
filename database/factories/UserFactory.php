<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
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
            'role' => fake()->randomElement(['admin', 'merchant']),
            'amount' => fake()->randomFloat(2, 0, 10000),
            'description' => fake()->optional()->sentence(),
            'version' => 1,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user should be an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the user should be a merchant.
     */
    public function merchant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'merchant',
        ]);
    }

    /**
     * Indicate that the user should have a specific balance.
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $balance,
        ]);
    }

    /**
     * Indicate that the user should have no balance.
     */
    public function withoutBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => 0.00,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
