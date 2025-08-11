<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\OrderCompleted;
use App\Events\PaymentReceived;
use App\Events\RefundProcessed;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Get all orders (admin)
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Order>
     */
    public function getAllOrders(int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['user', 'transactions'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get user's orders
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Order>
     */
    public function getUserOrders(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->orders()
            ->with('transactions')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create an order for adding money
     *
     * @param array{
     *   amount: float|int|string,
     *   title?: string,
     *   description?: string|null,
     *   external_reference?: string|null,
     *   metadata?: array<string, mixed>
     * } $data
     */
    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $rawAmount = $data['amount'];
            if (!is_numeric($rawAmount)) {
                throw new \InvalidArgumentException('Amount must be numeric.');
            }
            $amount = (float) $rawAmount;
            $order = Order::create([
                'user_id' => $user->id,
                'title' => $data['title'] ?? 'Add Money to Wallet',
                'description' => $data['description'] ?? null,
                'amount' => $amount,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'external_reference' => $data['external_reference'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            // Dispatch order created event
            event(new OrderCreated($order));

            return $order;
        });
    }

    /**
     * Process an order (complete payment)
     */
    public function processOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
                throw new \Exception('Order cannot be processed in current status: '.$order->status);
            }

            $oldStatus = $order->status;

            // Add money to user's wallet
            $user = $order->user()->firstOrFail();
            $transaction = $this->transactionService->addMoney(
                $user,
                (float) $order->amount,
                "Payment for order #{$order->id}: {$order->title}",
                auth()->user()
            );

            // Update order status
            $order->update([
                'status' => Order::STATUS_COMPLETED,
                'metadata' => array_merge($order->metadata ?? [], [
                    'transaction_id' => $transaction->id,
                    'processed_at' => now()->toISOString(),
                ]),
            ]);

            // Dispatch events
            event(new OrderStatusChanged($order, $oldStatus, $order->status));
            event(new OrderCompleted($order));
            // Metadata is array|null per model casting; coalesce ensures array
            $meta = $order->metadata ?? [];
            $paymentMethod = $meta['payment_method'] ?? null;
            if (!is_string($paymentMethod)) {
                $paymentMethod = null;
            }
            event(new PaymentReceived($order, $user, (float) $order->amount, $paymentMethod));

            $order->refresh();
            return $order;
        });
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            if ($order->status === Order::STATUS_CANCELLED) {
                return $order;
            }

            if ($order->status === Order::STATUS_COMPLETED) {
                throw new \Exception('Cannot cancel a completed order');
            }

            $oldStatus = $order->status;

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'metadata' => array_merge($order->metadata ?? [], [
                    'cancelled_at' => now()->toISOString(),
                    'cancelled_by' => auth()->id(),
                ]),
            ]);

            // Dispatch status changed event
            event(new OrderStatusChanged($order, $oldStatus, Order::STATUS_CANCELLED));

            $order->refresh();
            return $order;
        });
    }

    /**
     * Refund an order
     */
    public function refundOrder(Order $order, ?float $amount = null): Order
    {
        return DB::transaction(function () use ($order, $amount) {
            if (!in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED])) {
                throw new \Exception('Order must be completed to be refunded');
            }

            $refundAmount = $amount ?? $order->amount;

            if ($refundAmount > $order->amount) {
                throw new \Exception('Refund amount cannot exceed order amount');
            }

            $oldStatus = $order->status;

            // Remove money from user's wallet (refund is a debit)
            $user = $order->user()->firstOrFail();
            $transaction = $this->transactionService->removeMoney(
                $user,
                (float) $refundAmount,
                "Refund for order #{$order->id}",
                auth()->user()
            );

            // Update order
            $order->update([
                'status' => Order::STATUS_REFUNDED,
                'metadata' => array_merge($order->metadata ?? [], [
                    'refund_transaction_id' => $transaction->id,
                    'refund_amount' => $refundAmount,
                    'refunded_at' => now()->toISOString(),
                    'refunded_by' => auth()->id(),
                ]),
            ]);

            // Dispatch events
            event(new OrderStatusChanged($order, $oldStatus, Order::STATUS_REFUNDED));
            event(new RefundProcessed($order, $user, (float) $refundAmount, auth()->user()));

            $order->refresh();
            return $order;
        });
    }

    /**
     * Get order statistics
     *
     * @return array{
     *   total_orders: int,
     *   pending_orders: int,
     *   completed_orders: int,
     *   cancelled_orders: int,
     *   refunded_orders: int,
     *   total_amount: float|int
     * }
     */
    public function getOrderStats(?User $user = null): array
    {
        $query = $user ? $user->orders() : Order::query();

        $totalAmount = (float) $query->where('status', Order::STATUS_COMPLETED)->sum('amount');

        return [
            'total_orders' => $query->count(),
            'pending_orders' => $query->where('status', Order::STATUS_PENDING_PAYMENT)->count(),
            'completed_orders' => $query->where('status', Order::STATUS_COMPLETED)->count(),
            'cancelled_orders' => $query->where('status', Order::STATUS_CANCELLED)->count(),
            'refunded_orders' => $query->where('status', Order::STATUS_REFUNDED)->count(),
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Search orders
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Order>
     */
    public function searchOrders(string $query, ?User $user = null): LengthAwarePaginator
    {
        $queryBuilder = Order::query()
            ->with(['user', 'transactions']);

        if ($user) {
            $queryBuilder->where('user_id', $user->id);
        }

        return $queryBuilder
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('external_reference', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }
}
