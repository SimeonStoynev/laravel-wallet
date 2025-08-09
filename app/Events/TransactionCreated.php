<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\PrivateChannel;

class TransactionCreated extends WalletEvent
{
    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(Transaction $transaction, array $metadata = [])
    {
        $eventData = [
            'transaction_id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'created_by' => $transaction->created_by,
            'type' => $transaction->type,
            'amount' => (float) $transaction->amount,
            'description' => $transaction->description,
            'reference_type' => $transaction->reference_type,
            'reference_id' => $transaction->reference_id,
            'balance_before' => (float) $transaction->balance_before,
            'balance_after' => (float) $transaction->balance_after,
            'balance_change' => $transaction->getBalanceChange(),
            'created_at' => $transaction->created_at?->toISOString(),
        ];

        parent::__construct(
            Transaction::class,
            $transaction->id,
            $eventData,
            array_merge([
                'user_email' => $transaction->user->email ?? null,
                'user_name' => $transaction->user->name ?? null,
                'creator_email' => $transaction->createdBy->email ?? null,
                'creator_name' => $transaction->createdBy->name ?? null,
            ], $metadata)
        );
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return 'TransactionCreated';
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
            new PrivateChannel("transaction.$this->aggregateId"),
            new PrivateChannel('admin.transactions'),
        ];
    }

    /**
     * Get the transaction amount.
     */
    public function getTransactionAmount(): float
    {
        $value = $this->eventData['amount'];
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Check if transaction is a credit.
     */
    public function isCredit(): bool
    {
        return $this->eventData['type'] === Transaction::TYPE_CREDIT;
    }

    /**
     * Check if transaction is a debit.
     */
    public function isDebit(): bool
    {
        return $this->eventData['type'] === Transaction::TYPE_DEBIT;
    }

    /**
     * Get the balance change amount.
     */
    public function getBalanceChange(): float
    {
        $value = $this->eventData['balance_change'];
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
