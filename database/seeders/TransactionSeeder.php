<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $orders = Order::completed()->get();
        $admins = User::admin()->get();

        if ($users->isEmpty()) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        // Create transactions for completed orders
        foreach ($orders as $order) {
            $user = $order->user;
            $admin = $admins->random();

            // Create a credit transaction for the completed order
            Transaction::factory()
                ->credit()
                ->forUser($user)
                ->createdBy($admin)
                ->forOrder($order)
                ->creditWithBalance(
                    $user->getBalance(),
                    (float) $order->amount
                )
                ->create([
                    'description' => "Credit for completed order #{$order->id}",
                ]);

            // Update user's balance after transaction
            $user->increment('amount', $order->amount);
        }

        // Create manual credit transactions (admin adding money)
        foreach ($users->random(10) as $user) {
            $admin = $admins->random();
            $amount = fake()->randomFloat(2, 50, 500);
            $currentBalance = $user->getBalance();

            Transaction::factory()
                ->credit()
                ->forUser($user)
                ->createdBy($admin)
                ->creditWithBalance($currentBalance, $amount)
                ->create([
                    'description' => 'Manual credit added by admin',
                ]);

            $user->increment('amount', $amount);
        }

        // Create debit transactions (users spending money)
        $usersWithBalance = User::withBalance()->limit(15)->get();

        foreach ($usersWithBalance as $user) {
            $admin = $admins->random();
            $maxDebit = min($user->getBalance() * 0.8, 300); // Don't debit more than 80% of balance

            if ($maxDebit > 10) {
                $amount = fake()->randomFloat(2, 10, $maxDebit);
                $currentBalance = $user->getBalance();

                Transaction::factory()
                    ->debit()
                    ->forUser($user)
                    ->createdBy($admin)
                    ->debitWithBalance($currentBalance, $amount)
                    ->create([
                        'description' => 'Debit transaction - service fee',
                    ]);

                $user->decrement('amount', $amount);
            }
        }

        // Create some transactions with order references for refunded orders
        $refundedOrders = Order::refunded()->limit(5)->get();
        foreach ($refundedOrders as $order) {
            $user = $order->user;
            $admin = $admins->random();
            $currentBalance = $user->getBalance();

            Transaction::factory()
                ->debit()
                ->forUser($user)
                ->createdBy($admin)
                ->forOrder($order)
                ->debitWithBalance($currentBalance, (float) $order->amount)
                ->create([
                    'description' => "Refund debit for order #{$order->id}",
                ]);

            $user->decrement('amount', $order->amount);
        }

        // Create some historical transactions with older dates
        foreach ($users->random(5) as $user) {
            $admin = $admins->random();
            $amount = fake()->randomFloat(2, 100, 1000);
            $currentBalance = fake()->randomFloat(2, 0, 2000);

            Transaction::factory()
                ->credit()
                ->forUser($user)
                ->createdBy($admin)
                ->creditWithBalance($currentBalance, $amount)
                ->create([
                    'description' => 'Historical credit transaction',
                    'created_at' => fake()->dateTimeBetween('-6 months', '-1 month'),
                    'updated_at' => fake()->dateTimeBetween('-6 months', '-1 month'),
                ]);
        }

        $this->command->info('Transactions seeded successfully!');
        $this->command->table(
            ['Type', 'Count'],
            [
                ['Credit', Transaction::credits()->count()],
                ['Debit', Transaction::debits()->count()],
                ['With Order Reference', Transaction::whereNotNull('reference_id')->count()],
                ['Total', Transaction::count()],
            ]
        );

        // Show balance summary
        $this->command->info('User Balance Summary:');
        $this->command->table(
            ['Balance Range', 'Count'],
            [
                ['Zero Balance', User::where('amount', '=', 0)->count()],
                ['$1-$100', User::whereBetween('amount', [1, 100])->count()],
                ['$101-$500', User::whereBetween('amount', [101, 500])->count()],
                ['$501-$1000', User::whereBetween('amount', [501, 1000])->count()],
                ['$1000+', User::where('amount', '>', 1000)->count()],
            ]
        );
    }
}
