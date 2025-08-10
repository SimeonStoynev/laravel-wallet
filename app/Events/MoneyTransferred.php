<?php

namespace App\Events;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MoneyTransferred
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * The sender user instance.
     *
     * @var User
     */
    public User $sender;

    /**
     * The recipient user instance.
     *
     * @var User
     */
    public User $recipient;

    /**
     * The debit transaction instance.
     *
     * @var Transaction
     */
    public Transaction $debitTransaction;

    /**
     * The credit transaction instance.
     *
     * @var Transaction
     */
    public Transaction $creditTransaction;

    /**
     * The amount transferred.
     *
     * @var float
     */
    public float $amount;

    /**
     * Create a new event instance.
     */
    public function __construct(
        User $sender,
        User $recipient,
        Transaction $debitTransaction,
        Transaction $creditTransaction,
        float $amount
    ) {
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->debitTransaction = $debitTransaction;
        $this->creditTransaction = $creditTransaction;
        $this->amount = $amount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('wallet.'.$this->sender->id),
            new PrivateChannel('wallet.'.$this->recipient->id),
        ];
    }
}
