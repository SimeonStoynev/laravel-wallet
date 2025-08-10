<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Events\TransactionCreated;
use App\Events\BalanceUpdated;
use App\Events\MoneyTransferred;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionService
{
    /**
     * Get paginated transactions for a user
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Transaction>
     */
    public function getUserTransactions(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->transactions()
            ->with(['user', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get all transactions (admin)
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Transaction>
     */
    public function getAllTransactions(int $perPage = 15): LengthAwarePaginator
    {
        return Transaction::with(['user', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Add money to user's wallet
     */
    public function addMoney(User $user, float $amount, string $description, ?User $createdBy = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $createdBy) {
            // Lock user record to prevent concurrent updates
            $user->lockForUpdate();

            // Calculate current balance
            $currentBalance = $this->calculateUserBalance($user);
            $newBalance = $currentBalance + $amount;

            // Create credit transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'created_by' => $createdBy !== null ? $createdBy->id : $user->id,
                'type' => Transaction::TYPE_CREDIT,
                'amount' => $amount,
                'balance_before' => $currentBalance,
                'balance_after' => $newBalance,
                'description' => $description,
                'reference_type' => 'manual_addition',
            ]);

            // Update user's amount field
            $user->update(['amount' => $newBalance]);

            // Dispatch events
            event(new TransactionCreated($transaction));
            event(new BalanceUpdated($user, $transaction, $currentBalance, $newBalance));

            return $transaction;
        });
    }

    /**
     * Transfer money between users
     *
     * @return array{debit: Transaction, credit: Transaction}
     */
    public function transferMoney(User $fromUser, User $toUser, float $amount, string $description): array
    {
        return DB::transaction(function () use ($fromUser, $toUser, $amount, $description) {
            // Lock both users
            $fromUser->lockForUpdate();
            $toUser->lockForUpdate();

            // Check balance
            $senderBalance = $this->calculateUserBalance($fromUser);
            if ($senderBalance < $amount) {
                throw new \Exception('Insufficient balance for transfer');
            }

            $receiverBalance = $this->calculateUserBalance($toUser);

            // Create debit transaction for sender
            $debitTransaction = Transaction::create([
                'user_id' => $fromUser->id,
                'created_by' => $fromUser->id,
                'type' => Transaction::TYPE_DEBIT,
                'amount' => $amount,
                'balance_before' => $senderBalance,
                'balance_after' => $senderBalance - $amount,
                'description' => "Transfer to {$toUser->name}: {$description}",
                'reference_type' => 'transfer',
                'metadata' => [
                    'transfer_to' => $toUser->id,
                    'transfer_type' => 'outgoing',
                ],
            ]);

            // Create credit transaction for receiver
            $creditTransaction = Transaction::create([
                'user_id' => $toUser->id,
                'created_by' => $fromUser->id,
                'type' => Transaction::TYPE_CREDIT,
                'amount' => $amount,
                'balance_before' => $receiverBalance,
                'balance_after' => $receiverBalance + $amount,
                'description' => "Transfer from {$fromUser->name}: {$description}",
                'reference_type' => 'transfer',
                'reference_id' => $debitTransaction->id,
                'metadata' => [
                    'transfer_from' => $fromUser->id,
                    'transfer_type' => 'incoming',
                    'related_transaction' => $debitTransaction->id,
                ],
            ]);

            // Update related transaction reference
            $debitTransaction->update([
                'reference_id' => $creditTransaction->id,
                'metadata' => array_merge($debitTransaction->metadata ?? [], [
                    'related_transaction' => $creditTransaction->id,
                ]),
            ]);

            // Update user balances
            $fromUser->update(['amount' => $senderBalance - $amount]);
            $toUser->update(['amount' => $receiverBalance + $amount]);

            // Dispatch events
            event(new TransactionCreated($debitTransaction));
            event(new TransactionCreated($creditTransaction));
            event(new BalanceUpdated($fromUser, $debitTransaction, $senderBalance, $senderBalance - $amount));
            event(new BalanceUpdated($toUser, $creditTransaction, $receiverBalance, $receiverBalance + $amount));
            event(new MoneyTransferred($fromUser, $toUser, $debitTransaction, $creditTransaction, $amount));

            return [
                'debit' => $debitTransaction,
                'credit' => $creditTransaction,
            ];
        });
    }

    /**
     * Calculate user's current balance from transactions
     */
    public function calculateUserBalance(User $user): float
    {
        $credits = $user->transactions()
            ->where('type', Transaction::TYPE_CREDIT)
            ->sum('amount');

        $debits = $user->transactions()
            ->where('type', Transaction::TYPE_DEBIT)
            ->sum('amount');

        return $credits - $debits;
    }

    /**
     * Get transaction statistics for a user
     *
     * @return array{
     *   total_credits: float|int,
     *   total_debits: float|int,
     *   current_balance: float|int,
     *   transaction_count: int,
     *   last_transaction: Transaction|null
     * }
     */
    public function getUserTransactionStats(User $user): array
    {
        $totalCredits = (float) $user->transactions()
            ->where('type', Transaction::TYPE_CREDIT)
            ->sum('amount');

        $totalDebits = (float) $user->transactions()
            ->where('type', Transaction::TYPE_DEBIT)
            ->sum('amount');

        $transactionCount = $user->transactions()->count();

        $lastTransaction = $user->transactions()
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'current_balance' => $totalCredits - $totalDebits,
            'transaction_count' => $transactionCount,
            'last_transaction' => $lastTransaction,
        ];
    }

    /**
     * Search transactions
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Transaction>
     */
    public function searchTransactions(string $query, ?User $user = null): LengthAwarePaginator
    {
        $queryBuilder = Transaction::query()
            ->with(['user', 'createdBy']);

        if ($user) {
            $queryBuilder->where('user_id', $user->id);
        }

        return $queryBuilder
            ->where('description', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }
}
