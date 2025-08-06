<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@wallet.com',
            'password' => Hash::make('password'),
            'amount' => 0.00,
            'description' => 'System Administrator',
            'email_verified_at' => now(),
        ]);

        // Create default merchant user for testing
        User::factory()->merchant()->create([
            'name' => 'Test Merchant',
            'email' => 'merchant@wallet.com',
            'password' => Hash::make('password'),
            'amount' => 1000.00,
            'description' => 'Test merchant account',
            'email_verified_at' => now(),
        ]);

        // Create additional admin users
        User::factory(2)
            ->admin()
            ->withoutBalance()
            ->create([
                'email_verified_at' => now(),
            ]);

        // Create merchant users with various balances
        User::factory(10)
            ->merchant()
            ->create([
                'email_verified_at' => now(),
            ]);

        // Create some merchants with high balances
        User::factory(5)
            ->merchant()
            ->withBalance(fake()->randomFloat(2, 2000, 10000))
            ->create([
                'email_verified_at' => now(),
            ]);

        // Create some merchants with zero balance
        User::factory(5)
            ->merchant()
            ->withoutBalance()
            ->create([
                'email_verified_at' => now(),
            ]);

        $this->command->info('Users seeded successfully!');
        $this->command->table(
            ['Role', 'Count'],
            [
                ['Admin', User::admin()->count()],
                ['Merchant', User::merchant()->count()],
                ['With Balance', User::withBalance()->count()],
                ['Total', User::count()],
            ]
        );
    }
}
