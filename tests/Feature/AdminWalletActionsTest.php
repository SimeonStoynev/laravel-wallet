<?php

namespace Tests\Feature;

use App\Events\BalanceUpdated;
use App\Events\TransactionCreated;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminWalletActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_remove_money_creates_debit_and_updates_balance(): void
    {
        Event::fake([TransactionCreated::class, BalanceUpdated::class]);

        $admin = $this->admin();
        $user = User::factory()->withBalance(75.00)->create();

        $this->actingAs($admin)
            ->post(route('admin.users.remove-money', $user), [
                'amount' => 25.00,
                'description' => 'Penalty',
            ])
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame(50.00, (float) $user->amount);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEBIT,
            'amount' => 25.00,
        ]);

        Event::assertDispatched(TransactionCreated::class);
        Event::assertDispatched(BalanceUpdated::class);
    }

    public function test_admin_remove_money_insufficient_balance_shows_error_and_no_change(): void
    {
        Event::fake([TransactionCreated::class, BalanceUpdated::class]);

        $admin = $this->admin();
        $user = User::factory()->withBalance(10.00)->create();

        $this->actingAs($admin)
            ->post(route('admin.users.remove-money', $user), [
                'amount' => 20.00,
                'description' => 'Over-debit',
            ])
            ->assertRedirect() // back
            ->assertSessionHas('error');

        $user->refresh();
        $this->assertSame(10.00, (float) $user->amount);
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEBIT,
            'amount' => 20.00,
        ]);

        Event::assertNotDispatched(TransactionCreated::class);
        Event::assertNotDispatched(BalanceUpdated::class);
    }
}
