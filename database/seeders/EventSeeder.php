<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $orders = Order::all();
        $transactions = Transaction::all();

        if ($users->isEmpty()) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        // Create user-related events
        foreach ($users->random(min(10, $users->count())) as $user) {
            // User creation event
            Event::factory()
                ->forUser($user)
                ->withVersion(1)
                ->occurredAt($user->created_at)
                ->create([
                    'event_type' => 'UserCreated',
                    'event_data' => [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'initial_balance' => 0.00,
                    ],
                    'metadata' => [
                        'source' => 'registration',
                        'ip' => fake()->ipv4(),
                        'user_agent' => 'Mozilla/5.0 (compatible)',
                    ],
                ]);

            // Balance change events (if user has transactions)
            $userTransactions = $transactions->where('user_id', $user->id)->sortBy('created_at');
            $version = 2;

            foreach ($userTransactions as $transaction) {
                Event::factory()
                    ->forUser($user)
                    ->withVersion($version)
                    ->occurredAt($transaction->created_at)
                    ->create([
                        'event_type' => 'WalletBalanceChanged',
                        'event_data' => [
                            'user_id' => $user->id,
                            'transaction_id' => $transaction->id,
                            'previous_balance' => (float) $transaction->balance_before,
                            'new_balance' => (float) $transaction->balance_after,
                            'amount' => (float) $transaction->amount,
                            'type' => $transaction->type,
                            'changed_by' => $transaction->created_by,
                        ],
                        'metadata' => [
                            'transaction_reference' => $transaction->reference_type ? [
                                'type' => $transaction->reference_type,
                                'id' => $transaction->reference_id,
                            ] : null,
                        ],
                    ]);
                $version++;
            }
        }

        // Create order-related events
        foreach ($orders->random(min(15, $orders->count())) as $order) {
            $version = 1;

            // Order creation event
            Event::factory()
                ->forOrder($order)
                ->withVersion($version)
                ->occurredAt($order->created_at)
                ->create([
                    'event_type' => 'OrderCreated',
                    'event_data' => [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'title' => $order->title,
                        'amount' => (float) $order->amount,
                        'status' => $order->status,
                        'external_reference' => $order->external_reference,
                    ],
                    'metadata' => $order->metadata ?? [],
                ]);
            $version++;

            // Status change event (if not pending)
            if ($order->status !== 'pending_payment') {
                Event::factory()
                    ->forOrder($order)
                    ->withVersion($version)
                    ->occurredAt($order->updated_at ?? $order->created_at->addMinutes(rand(5, 60)))
                    ->create([
                        'event_type' => 'OrderStatusChanged',
                        'event_data' => [
                            'order_id' => $order->id,
                            'user_id' => $order->user_id,
                            'previous_status' => 'pending_payment',
                            'new_status' => $order->status,
                            'changed_at' => $order->updated_at ?? now(),
                        ],
                        'metadata' => [
                            'reason' => $this->getStatusChangeReason($order->status),
                        ],
                    ]);
                $version++;

                // Completion event for completed orders
                if ($order->status === 'completed') {
                    Event::factory()
                        ->forOrder($order)
                        ->withVersion($version)
                        ->occurredAt($order->updated_at ?? $order->created_at->addMinutes(rand(10, 120)))
                        ->create([
                            'event_type' => 'OrderCompleted',
                            'event_data' => [
                                'order_id' => $order->id,
                                'user_id' => $order->user_id,
                                'amount' => (float) $order->amount,
                                'completed_at' => $order->updated_at ?? now(),
                            ],
                            'metadata' => [
                                'completion_method' => fake()->randomElement(['automatic', 'manual', 'webhook']),
                            ],
                        ]);
                }
            }
        }

        // Create transaction-related events
        foreach ($transactions->random(min(20, $transactions->count())) as $transaction) {
            Event::factory()
                ->forTransaction($transaction)
                ->withVersion(1)
                ->occurredAt($transaction->created_at)
                ->create([
                    'event_type' => 'TransactionCreated',
                    'event_data' => [
                        'transaction_id' => $transaction->id,
                        'user_id' => $transaction->user_id,
                        'created_by' => $transaction->created_by,
                        'type' => $transaction->type,
                        'amount' => (float) $transaction->amount,
                        'balance_before' => (float) $transaction->balance_before,
                        'balance_after' => (float) $transaction->balance_after,
                        'description' => $transaction->description,
                    ],
                    'metadata' => [
                        'reference' => $transaction->reference_type ? [
                            'type' => $transaction->reference_type,
                            'id' => $transaction->reference_id,
                        ] : null,
                        'automated' => fake()->boolean(70),
                    ],
                ]);

            // Balance update event
            Event::factory()
                ->forTransaction($transaction)
                ->withVersion(2)
                ->occurredAt($transaction->created_at->addSecond())
                ->create([
                    'event_type' => 'BalanceUpdated',
                    'event_data' => [
                        'transaction_id' => $transaction->id,
                        'user_id' => $transaction->user_id,
                        'previous_balance' => (float) $transaction->balance_before,
                        'new_balance' => (float) $transaction->balance_after,
                        'change_amount' => $transaction->type === 'credit'
                            ? (float) $transaction->amount
                            : -(float) $transaction->amount,
                    ],
                ]);
        }

        // Create some historical events for audit trail
        Event::factory(10)
            ->create([
                'aggregate_type' => fake()->randomElement([User::class, Order::class, Transaction::class]),
                'event_type' => 'SystemMaintenance',
                'event_data' => [
                    'maintenance_type' => fake()->randomElement(['backup', 'update', 'cleanup']),
                    'duration_minutes' => fake()->numberBetween(5, 60),
                    'affected_records' => fake()->numberBetween(1, 1000),
                ],
                'occurred_at' => fake()->dateTimeBetween('-1 year', '-1 month'),
            ]);

        $this->command->info('Events seeded successfully!');
        $this->command->table(
            ['Event Type', 'Count'],
            [
                ['UserCreated', Event::byEventType('UserCreated')->count()],
                ['WalletBalanceChanged', Event::byEventType('WalletBalanceChanged')->count()],
                ['OrderCreated', Event::byEventType('OrderCreated')->count()],
                ['OrderStatusChanged', Event::byEventType('OrderStatusChanged')->count()],
                ['OrderCompleted', Event::byEventType('OrderCompleted')->count()],
                ['TransactionCreated', Event::byEventType('TransactionCreated')->count()],
                ['BalanceUpdated', Event::byEventType('BalanceUpdated')->count()],
                ['Total Events', Event::count()],
            ]
        );

        $this->command->table(
            ['Aggregate Type', 'Count'],
            [
                ['User Events', Event::forAggregateType(User::class)->count()],
                ['Order Events', Event::forAggregateType(Order::class)->count()],
                ['Transaction Events', Event::forAggregateType(Transaction::class)->count()],
            ]
        );
    }

    /**
     * Get a reason for status change based on the new status.
     */
    private function getStatusChangeReason(string $status): string
    {
        return match ($status) {
            'completed' => fake()->randomElement(['payment_received', 'manual_approval', 'automatic_processing']),
            'cancelled' => fake()->randomElement(['user_request', 'payment_failed', 'fraud_detection']),
            'refunded' => fake()->randomElement(['user_request', 'dispute_resolution', 'error_correction']),
            default => 'system_update',
        };
    }
}
