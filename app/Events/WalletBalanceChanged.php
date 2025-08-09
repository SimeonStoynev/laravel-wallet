<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;

class WalletBalanceChanged extends WalletEvent
{
    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        User $user,
        float $previousBalance,
        float $newBalance,
        int $changedBy,
        ?int $transactionId = null,
        array $metadata = []
    ) {
        $eventData = [
            'user_id' => $user->id,
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
            'balance_change' => $newBalance - $previousBalance,
            'changed_by' => $changedBy,
            'transaction_id' => $transactionId,
            'changed_at' => now()->toISOString(),
        ];

        parent::__construct(
            User::class,
            $user->id,
            $eventData,
            array_merge([
                'user_email' => $user->email,
                'user_name' => $user->name,
                'user_role' => $user->role,
            ], $metadata)
        );
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return 'WalletBalanceChanged';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.$this->aggregateId"),
            new PrivateChannel('wallet.balances'),
        ];
    }

    /**
     * Get the balance change amount.
     */
    public function getBalanceChange(): float
    {
        $value = $this->eventData['balance_change'];
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Check if this was a credit (positive change).
     */
    public function isCredit(): bool
    {
        return $this->getBalanceChange() > 0;
    }

    /**
     * Check if this was a debit (negative change).
     */
    public function isDebit(): bool
    {
        return $this->getBalanceChange() < 0;
    }
}
