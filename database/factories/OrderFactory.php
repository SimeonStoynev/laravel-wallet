<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
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
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'status' => fake()->randomElement([
                Order::STATUS_PENDING_PAYMENT,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
                Order::STATUS_REFUNDED,
            ]),
            'external_reference' => fake()->optional()->uuid(),
            'metadata' => fake()->optional()->randomElement([
                ['source' => 'web', 'campaign' => 'summer_sale'],
                ['source' => 'mobile', 'version' => '2.1.0'],
                ['source' => 'api', 'client' => 'third_party'],
            ]),
        ];
    }

    /**
     * Indicate that the order should be pending payment.
     */
    public function pendingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);
    }

    /**
     * Indicate that the order should be completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_COMPLETED,
        ]);
    }

    /**
     * Indicate that the order should be cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_CANCELLED,
        ]);
    }

    /**
     * Indicate that the order should be refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_REFUNDED,
        ]);
    }

    /**
     * Indicate that the order should have a specific amount.
     */
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Indicate that the order should belong to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the order should have external reference.
     */
    public function withExternalReference(?string $reference = null): static
    {
        return $this->state(fn (array $attributes) => [
            'external_reference' => $reference ?? fake()->uuid(),
        ]);
    }

    /**
     * Indicate that the order should have specific metadata.
     *
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }
}
