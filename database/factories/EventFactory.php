<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $aggregateType = fake()->randomElement([User::class, Order::class, Transaction::class]);
        $eventTypes = $this->getEventTypesForAggregate($aggregateType);

        return [
            'aggregate_type' => $aggregateType,
            'aggregate_id' => fake()->numberBetween(1, 1000),
            'event_type' => fake()->randomElement($eventTypes),
            'event_data' => $this->generateEventData($aggregateType),
            'metadata' => fake()->optional()->randomElement([
                ['user_agent' => 'Mozilla/5.0', 'ip' => fake()->ipv4()],
                ['source' => 'api', 'version' => '1.0'],
                ['correlation_id' => fake()->uuid()],
            ]),
            'version' => fake()->numberBetween(1, 10),
            'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the event should be for a User aggregate.
     */
    public function forUser(User $user = null): static
    {
        $userId = $user ? $user->id : fake()->numberBetween(1, 1000);

        return $this->state(fn (array $attributes) => [
            'aggregate_type' => User::class,
            'aggregate_id' => $userId,
            'event_type' => fake()->randomElement([
                EventType::WALLET_BALANCE_CHANGED->value,
                EventType::USER_ROLE_CHANGED->value,
                EventType::USER_CREATED->value,
                EventType::USER_UPDATED->value,
            ]),
            'event_data' => [
                'user_id' => $userId,
                'previous_balance' => fake()->randomFloat(2, 0, 1000),
                'new_balance' => fake()->randomFloat(2, 0, 1000),
                'changed_by' => fake()->numberBetween(1, 100),
            ],
        ]);
    }

    /**
     * Indicate that the event should be for an Order aggregate.
     */
    public function forOrder(Order $order = null): static
    {
        $orderId = $order ? $order->id : fake()->numberBetween(1, 1000);

        return $this->state(fn (array $attributes) => [
            'aggregate_type' => Order::class,
            'aggregate_id' => $orderId,
            'event_type' => fake()->randomElement([
                EventType::ORDER_CREATED->value,
                EventType::ORDER_STATUS_CHANGED->value,
                EventType::ORDER_COMPLETED->value,
                EventType::ORDER_CANCELLED->value,
                EventType::ORDER_REFUNDED->value,
            ]),
            'event_data' => [
                'order_id' => $orderId,
                'user_id' => fake()->numberBetween(1, 100),
                'amount' => fake()->randomFloat(2, 10, 1000),
                'status' => fake()->randomElement(['pending_payment', 'completed', 'cancelled', 'refunded']),
            ],
        ]);
    }

    /**
     * Indicate that the event should be for a Transaction aggregate.
     */
    public function forTransaction(Transaction $transaction = null): static
    {
        $transactionId = $transaction ? $transaction->id : fake()->numberBetween(1, 1000);

        return $this->state(fn (array $attributes) => [
            'aggregate_type' => Transaction::class,
            'aggregate_id' => $transactionId,
            'event_type' => fake()->randomElement([
                EventType::TRANSACTION_CREATED->value,
                EventType::BALANCE_UPDATED->value,
            ]),
            'event_data' => [
                'transaction_id' => $transactionId,
                'user_id' => fake()->numberBetween(1, 100),
                'type' => fake()->randomElement(['credit', 'debit']),
                'amount' => fake()->randomFloat(2, 10, 500),
                'balance_before' => fake()->randomFloat(2, 0, 5000),
                'balance_after' => fake()->randomFloat(2, 0, 5000),
            ],
        ]);
    }

    /**
     * Indicate that the event should have a specific version.
     */
    public function withVersion(int $version): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => $version,
        ]);
    }

    /**
     * Indicate that the event should have occurred at a specific time.
     */
    public function occurredAt(\DateTimeInterface $timestamp): static
    {
        return $this->state(fn (array $attributes) => [
            'occurred_at' => $timestamp,
        ]);
    }

    /**
     * Indicate that the event should have specific metadata.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get event types for a specific aggregate type.
     */
    private function getEventTypesForAggregate(string $aggregateType): array
    {
        return match ($aggregateType) {
            User::class => array_map(fn($enum) => $enum->value, EventType::userEvents()),
            Order::class => array_map(fn($enum) => $enum->value, EventType::orderEvents()),
            Transaction::class => array_map(fn($enum) => $enum->value, EventType::transactionEvents()),
            default => ['GenericEvent'],
        };
    }

    /**
     * Generate event data based on aggregate type.
     */
    private function generateEventData(string $aggregateType): array
    {
        return match ($aggregateType) {
            User::class => [
                'user_id' => fake()->numberBetween(1, 1000),
                'previous_balance' => fake()->randomFloat(2, 0, 1000),
                'new_balance' => fake()->randomFloat(2, 0, 1000),
                'changed_by' => fake()->numberBetween(1, 100),
            ],
            Order::class => [
                'order_id' => fake()->numberBetween(1, 1000),
                'user_id' => fake()->numberBetween(1, 100),
                'amount' => fake()->randomFloat(2, 10, 1000),
                'status' => fake()->randomElement(['pending_payment', 'completed', 'cancelled', 'refunded']),
            ],
            Transaction::class => [
                'transaction_id' => fake()->numberBetween(1, 1000),
                'user_id' => fake()->numberBetween(1, 100),
                'type' => fake()->randomElement(['credit', 'debit']),
                'amount' => fake()->randomFloat(2, 10, 500),
                'balance_before' => fake()->randomFloat(2, 0, 5000),
                'balance_after' => fake()->randomFloat(2, 0, 5000),
            ],
            default => [
                'data' => fake()->words(3, true),
            ],
        };
    }
}
