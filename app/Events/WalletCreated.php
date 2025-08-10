<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * The user instance.
     *
     * @var User
     */
    public User $user;

    /**
     * The initial balance.
     *
     * @var float
     */
    public float $initialBalance;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, float $initialBalance = 0)
    {
        $this->user = $user;
        $this->initialBalance = $initialBalance;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('wallet.'.$this->user->id),
        ];
    }
}
