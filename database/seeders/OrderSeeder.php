<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users to create orders for
        $merchants = User::merchant()->limit(10)->get();
        $admins = User::admin()->limit(2)->get();

        if ($merchants->isEmpty()) {
            $this->command->error('No merchant users found. Please run UserSeeder first.');
            return;
        }

        // Create orders for merchants
        foreach ($merchants as $merchant) {
            // Create some pending payment orders
            Order::factory(rand(2, 5))
                ->pendingPayment()
                ->forUser($merchant)
                ->create();

            // Create some completed orders
            Order::factory(rand(1, 3))
                ->completed()
                ->forUser($merchant)
                ->create();

            // Create some cancelled orders
            Order::factory(rand(0, 2))
                ->cancelled()
                ->forUser($merchant)
                ->create();

            // Create some refunded orders
            Order::factory(rand(0, 1))
                ->refunded()
                ->forUser($merchant)
                ->create();
        }

        // Create some orders with external references
        Order::factory(10)
            ->forUser($merchants->random())
            ->withExternalReference()
            ->create();

        // Create orders with metadata
        Order::factory(5)
            ->forUser($merchants->random())
            ->withMetadata([
                'source' => 'mobile_app',
                'version' => '2.1.0',
                'campaign' => 'black_friday_2024',
            ])
            ->create();

        // Create high-value orders
        Order::factory(5)
            ->forUser($merchants->random())
            ->withAmount(fake()->randomFloat(2, 1000, 5000))
            ->completed()
            ->create();

        // Create orders from admin users (for testing admin functionality)
        if ($admins->isNotEmpty()) {
            Order::factory(3)
                ->forUser($admins->first())
                ->completed()
                ->create();
        }

        $this->command->info('Orders seeded successfully!');
        $this->command->table(
            ['Status', 'Count'],
            [
                ['Pending Payment', Order::pendingPayment()->count()],
                ['Completed', Order::completed()->count()],
                ['Cancelled', Order::cancelled()->count()],
                ['Refunded', Order::refunded()->count()],
                ['With External Ref', Order::whereNotNull('external_reference')->count()],
                ['Total', Order::count()],
            ]
        );
    }
}
