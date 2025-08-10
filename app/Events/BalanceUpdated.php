<?php

namespace App\Events;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;

class BalanceUpdated extends WalletEvent
{
    /**
     * Public properties for test compatibility
     */
    public User $user;
    public Transaction $transaction;
    public float $previousBalance;
    public float $newBalance;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        User $user,
        Transaction $transaction,
        float $previousBalance,
        float $newBalance,
        array $metadata = []
    ) {
        // Expose public fields as used by tests
        $this->user = $user;
        $this->transaction = $transaction;
        $this->previousBalance = $previousBalance;
        $this->newBalance = $newBalance;

        $eventData = [
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
            'balance_change' => $newBalance - $previousBalance,
            'transaction_type' => $transaction->type,
            'transaction_amount' => (float) $transaction->amount,
            'updated_at' => now()->toISOString(),
        ];

        parent::__construct(
            User::class,
            $user->id,
            $eventData,
            array_merge([
                'user_email' => $user->email,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'transaction_reference' => $transaction->reference_type ? [
                    'type' => $transaction->reference_type,
                    'id' => $transaction->reference_id,
                ] : null,
            ], $metadata)
        );
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return 'BalanceUpdated';
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
            new PrivateChannel('admin.balances'),
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
     * Get the transaction that caused this balance update.
     */
    public function getTransactionId(): int
    {
        $value = $this->eventData['transaction_id'];
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Check if this was a positive balance change.
     */
    public function isPositiveChange(): bool
    {
        return $this->getBalanceChange() > 0;
    }

    /**
     * Check if this was a negative balance change.
     */
    public function isNegativeChange(): bool
    {
        return $this->getBalanceChange() < 0;
    }

    /**
     * Get the new balance amount.
     */
    public function getNewBalance(): float
    {
        $value = $this->eventData['new_balance'];
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Get the previous balance amount.
     */
    public function getPreviousBalance(): float
    {
        $value = $this->eventData['previous_balance'];
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
