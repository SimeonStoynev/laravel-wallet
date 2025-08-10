<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;

class OrderCreated extends WalletEvent
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
            'description' => $order->description,
            'amount' => (float) $order->amount,
            'status' => $order->status,
            'external_reference' => $order->external_reference,
            'order_metadata' => $order->metadata,
            'created_at' => $order->created_at?->toISOString(),
        ];

        parent::__construct(
            Order::class,
            $order->id,
            $eventData,
            array_merge([
                'user_email' => $order->user->email ?? null,
                'user_name' => $order->user->name ?? null,
                'user_role' => $order->user->role ?? null,
            ], $metadata)
        );
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return 'OrderCreated';
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
}
