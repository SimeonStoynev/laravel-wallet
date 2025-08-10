<?php

namespace Tests\Unit;

use App\Events\OrderCompleted;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\PaymentReceived;
use App\Events\RefundProcessed;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\OrderService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeServices(): array
    {
        $tx = new TransactionService();
        $order = new OrderService($tx);
        return [$order, $tx];
    }

    public function test_create_order_dispatches_event_and_is_pending(): void
    {
        Event::fake([OrderCreated::class]);

        [$orderService] = $this->makeServices();
        $user = User::factory()->merchant()->withoutBalance()->create();

        $order = $orderService->createOrder($user, [
            'amount' => 25.50,
            'metadata' => ['payment_method' => 'card'],
        ]);

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertSame($user->id, $order->user_id);
        Event::assertDispatched(OrderCreated::class, fn ($e) => $e->order->id === $order->id);
    }

    public function test_process_order_adds_money_changes_status_and_dispatches_events_with_payment_method(): void
    {
        Event::fake([OrderStatusChanged::class, OrderCompleted::class, PaymentReceived::class]);

        [$orderService] = $this->makeServices();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->merchant()->withoutBalance()->create();

        $order = Order::factory()
            ->forUser($user)
            ->pendingPayment()
            ->withAmount(40.00)
            ->withMetadata(['payment_method' => 'bank'])
            ->create();

        $this->actingAs($admin);
        $processed = $orderService->processOrder($order);

        $this->assertSame(Order::STATUS_COMPLETED, $processed->status);
        $user->refresh();
        $this->assertSame(40.00, (float) $user->amount);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => Transaction::TYPE_CREDIT,
            'amount' => 40.00,
        ]);

        Event::assertDispatched(OrderStatusChanged::class, fn ($e) => $e->order->id === $order->id && $e->newStatus === Order::STATUS_COMPLETED);
        Event::assertDispatched(OrderCompleted::class, fn ($e) => $e->order->id === $order->id);
        Event::assertDispatched(PaymentReceived::class, fn ($e) => $e->order->id === $order->id && $e->paymentMethod === 'bank');
    }

    public function test_cancel_order_sets_status_and_dispatches_event(): void
    {
        Event::fake([OrderStatusChanged::class]);

        [$orderService] = $this->makeServices();
        $order = Order::factory()->pendingPayment()->create();

        $cancelled = $orderService->cancelOrder($order);
        $this->assertSame(Order::STATUS_CANCELLED, $cancelled->status);
        Event::assertDispatched(OrderStatusChanged::class, fn ($e) => $e->order->id === $order->id && $e->newStatus === Order::STATUS_CANCELLED);
    }

    public function test_refund_order_debits_wallet_updates_status_and_dispatches_events(): void
    {
        Event::fake([OrderStatusChanged::class, RefundProcessed::class]);

        [$orderService] = $this->makeServices();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->merchant()->withBalance(60.00)->create();

        $order = Order::factory()->forUser($user)->completed()->withAmount(50.00)->create();

        $this->actingAs($admin);
        $refunded = $orderService->refundOrder($order, 20.00);

        $this->assertSame(Order::STATUS_REFUNDED, $refunded->status);
        $user->refresh();
        $this->assertSame(40.00, (float) $user->amount);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEBIT,
            'amount' => 20.00,
        ]);

        Event::assertDispatched(OrderStatusChanged::class, fn ($e) => $e->order->id === $order->id && $e->newStatus === Order::STATUS_REFUNDED);
        Event::assertDispatched(RefundProcessed::class, fn ($e) => $e->order->id === $order->id && (float) $e->amount === 20.00);
    }
}
