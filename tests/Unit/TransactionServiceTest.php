<?php

namespace Tests\Unit;

use App\Events\BalanceUpdated;
use App\Events\MoneyTransferred;
use App\Events\TransactionCreated;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_money_creates_credit_and_dispatches_events(): void
    {
        Event::fake([TransactionCreated::class, BalanceUpdated::class]);

        $service = new TransactionService();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->withoutBalance()->create();

        $this->actingAs($admin);
        $tx = $service->addMoney($user, 100.00, 'Initial top up', $admin);

        $this->assertDatabaseHas('transactions', [
            'id' => $tx->id,
            'user_id' => $user->id,
            'type' => Transaction::TYPE_CREDIT,
            'amount' => 100.00,
        ]);
        $user->refresh();
        $this->assertSame(100.00, (float) $user->amount);

        Event::assertDispatched(TransactionCreated::class, fn ($e) => $e->transaction->id === $tx->id);
        Event::assertDispatched(BalanceUpdated::class, fn ($e) => (int) $e->user->id === (int) $user->id && (float) $e->newBalance === 100.00);
    }

    public function test_remove_money_creates_debit_and_dispatches_events(): void
    {
        Event::fake([TransactionCreated::class, BalanceUpdated::class]);

        $service = new TransactionService();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->withBalance(120.00)->create();

        $this->actingAs($admin);
        $tx = $service->removeMoney($user, 20.00, 'Admin debit', $admin);

        $this->assertDatabaseHas('transactions', [
            'id' => $tx->id,
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEBIT,
            'amount' => 20.00,
        ]);
        $user->refresh();
        $this->assertSame(100.00, (float) $user->amount);

        Event::assertDispatched(TransactionCreated::class, fn ($e) => $e->transaction->id === $tx->id);
        Event::assertDispatched(BalanceUpdated::class, fn ($e) => (int) $e->user->id === (int) $user->id && (float) $e->newBalance === 100.00);
    }

    public function test_transfer_money_creates_debit_and_credit_updates_balances_and_dispatches_events(): void
    {
        Event::fake([TransactionCreated::class, BalanceUpdated::class, MoneyTransferred::class]);

        $service = new TransactionService();
        $from = User::factory()->withBalance(200.00)->create();
        $to = User::factory()->withBalance(10.00)->create();

        $result = $service->transferMoney($from, $to, 50.00, 'Gift');

        $this->assertSame(Transaction::TYPE_DEBIT, $result['debit']->type);
        $this->assertSame(Transaction::TYPE_CREDIT, $result['credit']->type);

        $from->refresh();
        $to->refresh();
        $this->assertSame(150.00, (float) $from->amount);
        $this->assertSame(60.00, (float) $to->amount);

        Event::assertDispatched(TransactionCreated::class, 2);
        Event::assertDispatched(BalanceUpdated::class, 2);
        Event::assertDispatched(MoneyTransferred::class, fn ($e) => (int) $e->fromUser->id === (int) $from->id && (int) $e->toUser->id === (int) $to->id && (float) $e->amount === 50.00);
    }
}
