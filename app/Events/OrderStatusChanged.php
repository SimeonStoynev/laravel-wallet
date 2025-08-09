<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;

class OrderStatusChanged extends WalletEvent
{
    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        Order $order,
        string $previousStatus,
        string $newStatus,
        array $metadata = []
    ) {
        $eventData = [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'amount' => (float) $order->amount,
            'external_reference' => $order->external_reference,
            'changed_at' => now()->toISOString(),
        ];

        parent::__construct(
            Order::class,
            $order->id,
            $eventData,
            array_merge([
                'user_email' => $order->user->email ?? null,
                'user_name' => $order->user->name ?? null,
                'order_title' => $order->title,
                'status_transition' => "{$previousStatus} -> {$newStatus}",
            ], $metadata)
        );
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return 'OrderStatusChanged';
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
            new PrivateChannel('admin.orders'),
        ];
    }

    /**
     * Check if the order was completed.
     */
    public function wasCompleted(): bool
    {
        return $this->eventData['new_status'] === Order::STATUS_COMPLETED;
    }

    /**
     * Check if the order was cancelled.
     */
    public function wasCancelled(): bool
    {
        return $this->eventData['new_status'] === Order::STATUS_CANCELLED;
    }

    /**
     * Check if the order was refunded.
     */
    public function wasRefunded(): bool
    {
        return $this->eventData['new_status'] === Order::STATUS_REFUNDED;
    }
}
