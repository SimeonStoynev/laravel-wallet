<?php

namespace Tests\Feature;

use App\Events\OrderCompleted;
use App\Events\OrderStatusChanged;
use App\Events\PaymentReceived;
use App\Events\RefundProcessed;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminOrderActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_process_order_updates_balance_and_dispatches_events(): void
    {
        Event::fake([OrderStatusChanged::class, OrderCompleted::class, PaymentReceived::class]);

        $admin = $this->admin();
        $user = User::factory()->merchant()->withoutBalance()->create();
        $order = Order::factory()
            ->forUser($user)
            ->pendingPayment()
            ->withAmount(55.00)
            ->withMetadata(['payment_method' => 'card'])
            ->create();

        $this->actingAs($admin)
            ->post(route('admin.orders.process', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $user->refresh();
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertSame(55.00, (float) $user->amount);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => Transaction::TYPE_CREDIT,
            'amount' => 55.00,
        ]);

        Event::assertDispatched(OrderStatusChanged::class);
        Event::assertDispatched(OrderCompleted::class);
        Event::assertDispatched(PaymentReceived::class, fn ($e) => $e->order->id === $order->id && $e->paymentMethod === 'card');
    }

    public function test_admin_cancel_order_sets_status_and_dispatches_event(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $admin = $this->admin();
        $order = Order::factory()->pendingPayment()->create();

        $this->actingAs($admin)
            ->post(route('admin.orders.cancel', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_admin_refund_order_debits_balance_and_dispatches_events(): void
    {
        Event::fake([OrderStatusChanged::class, RefundProcessed::class]);

        $admin = $this->admin();
        $user = User::factory()->merchant()->withoutBalance()->create();
        $order = Order::factory()->forUser($user)->pendingPayment()->withAmount(70.00)->create();

        // First process the order to credit funds
        $this->actingAs($admin)->post(route('admin.orders.process', $order))->assertRedirect();

        $order->refresh();
        $user->refresh();
        $this->assertSame(70.00, (float) $user->amount);
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);

        // Then refund partially 20.00
        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order), ['amount' => 20.00])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $user->refresh();
        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
        $this->assertSame(50.00, (float) $user->amount);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEBIT,
            'amount' => 20.00,
        ]);

        Event::assertDispatched(OrderStatusChanged::class);
        Event::assertDispatched(RefundProcessed::class, fn ($e) => $e->order->id === $order->id && (float) $e->amount === 20.00);
    }

    public function test_admin_refund_fails_when_insufficient_user_balance(): void
    {
        $admin = $this->admin();
        $user = User::factory()->merchant()->withoutBalance()->create();
        $order = Order::factory()->forUser($user)->pendingPayment()->withAmount(30.00)->create();

        // Process credits +30
        $this->actingAs($admin)->post(route('admin.orders.process', $order))->assertRedirect();

        // Manually remove user's funds to simulate spending
        $user->forceFill(['amount' => 0.00])->save();

        // Refund should error due to insufficient balance for debit
        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order), ['amount' => 10.00])
            ->assertRedirect()
            ->assertSessionHas('error');

        // Ensure no debit transaction of 10 was created
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEBIT,
            'amount' => 10.00,
        ]);
    }
}
