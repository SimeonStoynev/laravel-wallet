<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $balanceBefore = fake()->randomFloat(2, 0, 5000);
        $amount = fake()->randomFloat(2, 10, 500);
        $type = fake()->randomElement([Transaction::TYPE_CREDIT, Transaction::TYPE_DEBIT]);

        $balanceAfter = $type === Transaction::TYPE_CREDIT
            ? $balanceBefore + $amount
            : $balanceBefore - $amount;

        return [
            'user_id' => User::factory(),
            'created_by' => User::factory(),
            'type' => $type,
            'amount' => $amount,
            'description' => fake()->optional()->sentence(),
            'reference_type' => fake()->optional()->randomElement([Order::class, null]),
            'reference_id' => fake()->optional()->numberBetween(1, 1000),
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ];
    }

    /**
     * Indicate that the transaction should be a credit.
     */
    public function credit(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? fake()->randomFloat(2, 10, 500);
            $balanceBefore = $attributes['balance_before'] ?? fake()->randomFloat(2, 0, 5000);

            return [
                'type' => Transaction::TYPE_CREDIT,
                'amount' => $amount,
                'balance_after' => $balanceBefore + $amount,
            ];
        });
    }

    /**
     * Indicate that the transaction should be a debit.
     */
    public function debit(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? fake()->randomFloat(2, 10, 500);
            $balanceBefore = $attributes['balance_before'] ?? fake()->randomFloat(2, 500, 5000); // Ensure sufficient balance

            return [
                'type' => Transaction::TYPE_DEBIT,
                'amount' => $amount,
                'balance_after' => $balanceBefore - $amount,
            ];
        });
    }

    /**
     * Indicate that the transaction should belong to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the transaction should be created by a specific user.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Indicate that the transaction should have a specific amount.
     */
    public function withAmount(float $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            $balanceBefore = $attributes['balance_before'] ?? fake()->randomFloat(2, 0, 5000);
            $type = $attributes['type'] ?? Transaction::TYPE_CREDIT;

            $balanceAfter = $type === Transaction::TYPE_CREDIT
                ? $balanceBefore + $amount
                : $balanceBefore - $amount;

            return [
                'amount' => $amount,
                'balance_after' => $balanceAfter,
            ];
        });
    }

    /**
     * Indicate that the transaction should have specific balance values.
     */
    public function withBalance(float $before, float $after): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_before' => $before,
            'balance_after' => $after,
        ]);
    }

    /**
     * Indicate that the transaction should reference an order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'description' => "Transaction for order #{$order->id}",
        ]);
    }

    /**
     * Create a credit transaction with proper balance calculation.
     */
    public function creditWithBalance(float $currentBalance, float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_CREDIT,
            'amount' => $amount,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance + $amount,
        ]);
    }

    /**
     * Create a debit transaction with proper balance calculation.
     */
    public function debitWithBalance(float $currentBalance, float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Transaction::TYPE_DEBIT,
            'amount' => $amount,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance - $amount,
        ]);
    }
}
