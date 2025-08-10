<?php

namespace Tests\Feature;

use App\Events\BalanceUpdated;
use App\Events\MoneyTransferred;
use App\Events\TransactionCreated;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CustomerTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function merchant(float $balance = 0.0): User
    {
        return User::factory()->merchant()->withBalance($balance)->create();
    }

    public function test_transfer_money_flow_succeeds_and_dispatches_events(): void
    {
        Event::fake([TransactionCreated::class, BalanceUpdated::class, MoneyTransferred::class]);

        $from = $this->merchant(100.00);
        $to = $this->merchant(5.00);

        $this->actingAs($from)
            ->post(route('customer.transactions.transfer'), [
                'recipient_id' => $to->id,
                'amount' => 30.00,
                'description' => 'Dinner split',
            ])
            ->assertRedirect(route('customer.transactions.index'))
            ->assertSessionHas('success');

        $from->refresh();
        $to->refresh();
        $this->assertSame(70.00, (float) $from->amount);
        $this->assertSame(35.00, (float) $to->amount);

        // Two transactions created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $from->id,
            'type' => Transaction::TYPE_DEBIT,
            'amount' => 30.00,
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $to->id,
            'type' => Transaction::TYPE_CREDIT,
            'amount' => 30.00,
        ]);

        Event::assertDispatched(TransactionCreated::class, 2);
        Event::assertDispatched(BalanceUpdated::class, 2);
        Event::assertDispatched(MoneyTransferred::class, fn ($e) => (int) $e->fromUser->id === (int) $from->id && (int) $e->toUser->id === (int) $to->id && (float) $e->amount === 30.00);
    }

    public function test_transfer_money_fails_when_insufficient_balance(): void
    {
        Event::fake([TransactionCreated::class, BalanceUpdated::class, MoneyTransferred::class]);

        $from = $this->merchant(10.00);
        $to = $this->merchant(0.00);

        $this->actingAs($from)
            ->post(route('customer.transactions.transfer'), [
                'recipient_id' => $to->id,
                'amount' => 20.00,
            ])
            ->assertRedirect() // back
            ->assertSessionHas('error');

        $from->refresh();
        $to->refresh();
        $this->assertSame(10.00, (float) $from->amount);
        $this->assertSame(0.00, (float) $to->amount);

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $from->id,
            'type' => Transaction::TYPE_DEBIT,
            'amount' => 20.00,
        ]);

        Event::assertNotDispatched(TransactionCreated::class);
        Event::assertNotDispatched(BalanceUpdated::class);
        Event::assertNotDispatched(MoneyTransferred::class);
    }
}
