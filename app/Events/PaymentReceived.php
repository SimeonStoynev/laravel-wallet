<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * The order instance.
     *
     * @var Order
     */
    public Order $order;

    /**
     * The user instance.
     *
     * @var User
     */
    public User $user;

    /**
     * The payment amount.
     *
     * @var float
     */
    public float $amount;

    /**
     * The payment method used.
     *
     * @var string|null
     */
    public ?string $paymentMethod;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, User $user, float $amount, ?string $paymentMethod = null)
    {
        $this->order = $order;
        $this->user = $user;
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.'.$this->order->id),
            new PrivateChannel('wallet.'.$this->user->id),
        ];
    }
}
