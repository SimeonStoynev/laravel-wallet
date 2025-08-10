<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;

class OrderCompleted extends WalletEvent
{
    /**
     * Public property for test compatibility
     */
    public Order $order;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(Order $order, array $metadata = [])
    {
        // Expose order instance for direct access in tests
        $this->order = $order;

        $eventData = [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'title' => $order->title,
            'amount' => (float) $order->amount,
            'external_reference' => $order->external_reference,
            'completed_at' => now()->toISOString(),
            'completion_duration' => $order->created_at?->diffInMinutes(now()),
        ];

        parent::__construct(
            Order::class,
            $order->id,
            $eventData,
            array_merge([
                'user_email' => $order->user->email ?? null,
                'user_name' => $order->user->name ?? null,
                'order_title' => $order->title,
                'completion_method' => 'system',
            ], $metadata)
        );
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return 'OrderCompleted';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.(is_scalar($this->eventData['user_id'] ?? '') ? (string) ($this->eventData['user_id'] ?? '') : '')),
            new PrivateChannel("order.$this->aggregateId"),
            new PrivateChannel('admin.orders.completed'),
        ];
    }

    /**
     * Get the order amount.
     */
    public function getOrderAmount(): float
    {
        $value = $this->eventData['amount'];
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Get the completion duration in minutes.
     */
    public function getCompletionDuration(): int
    {
        $value = $this->eventData['completion_duration'];
        return is_numeric($value) ? (int) $value : 0;
    }
}
