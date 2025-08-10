<?php

namespace App\Listeners;

use App\Events\MoneyTransferred;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class MoneyTransferredListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MoneyTransferred $event): void
    {
        // 1. Log the transfer
        Log::info('Money transferred', [
            'sender_id' => $event->sender->id,
            'recipient_id' => $event->recipient->id,
            'amount' => $event->amount,
            'debit_transaction_id' => $event->debitTransaction->id,
            'credit_transaction_id' => $event->creditTransaction->id,
            'timestamp' => now()->toISOString(),
        ]);

        // 2. Send notifications to both parties
        $this->sendTransferNotifications($event);

        // 3. Check for suspicious activity
        $this->checkForSuspiciousActivity($event);

        // 4. Update transfer statistics
        $this->updateTransferStatistics($event);
    }

    /**
     * Send transfer notifications to both parties
     */
    private function sendTransferNotifications(MoneyTransferred $event): void
    {
        // In a real application, this would send emails or push notifications
        // For demonstration purposes, we'll just log it

        // Sender notification
        Log::info('Transfer sent notification would be sent', [
            'user_email' => $event->sender->email,
            'amount' => $event->amount,
            'recipient' => $event->recipient->name,
            'balance_after' => $event->debitTransaction->balance_after,
        ]);

        // Recipient notification
        Log::info('Transfer received notification would be sent', [
            'user_email' => $event->recipient->email,
            'amount' => $event->amount,
            'sender' => $event->sender->name,
            'balance_after' => $event->creditTransaction->balance_after,
        ]);
    }

    /**
     * Check for suspicious activity
     */
    private function checkForSuspiciousActivity(MoneyTransferred $event): void
    {
        // Example: Check if this is a large transfer
        $largeTransferThreshold = 1000.0; // This could be configurable

        if ($event->amount > $largeTransferThreshold) {
            Log::warning('Large transfer detected', [
                'sender_id' => $event->sender->id,
                'recipient_id' => $event->recipient->id,
                'amount' => $event->amount,
                'threshold' => $largeTransferThreshold,
            ]);

            // In a real application, this might trigger a review or additional verification
        }

        // Example: Check for multiple transfers in a short period
        $recentTransfers = $event->sender->transactions()
            ->where('type', 'debit')
            ->where('reference_type', 'transfer')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($recentTransfers > 5) { // More than 5 transfers in 24 hours
            Log::warning('Multiple transfers in short period detected', [
                'sender_id' => $event->sender->id,
                'transfer_count_24h' => $recentTransfers,
            ]);

            // In a real application, this might trigger a review or additional verification
        }
    }

    /**
     * Update transfer statistics
     */
    private function updateTransferStatistics(MoneyTransferred $event): void
    {
        // This would typically update a statistics table or cache
        // For demonstration purposes, we'll just log it

        // Sender statistics
        $senderTransferCount = $event->sender->transactions()
            ->where('type', 'debit')
            ->where('reference_type', 'transfer')
            ->count();

        $senderTransferTotal = $event->sender->transactions()
            ->where('type', 'debit')
            ->where('reference_type', 'transfer')
            ->sum('amount');

        Log::info('Updated sender transfer statistics', [
            'user_id' => $event->sender->id,
            'total_transfers_sent' => $senderTransferCount,
            'total_amount_sent' => $senderTransferTotal,
        ]);

        // Recipient statistics
        $recipientTransferCount = $event->recipient->transactions()
            ->where('type', 'credit')
            ->where('reference_type', 'transfer')
            ->count();

        $recipientTransferTotal = $event->recipient->transactions()
            ->where('type', 'credit')
            ->where('reference_type', 'transfer')
            ->sum('amount');

        Log::info('Updated recipient transfer statistics', [
            'user_id' => $event->recipient->id,
            'total_transfers_received' => $recipientTransferCount,
            'total_amount_received' => $recipientTransferTotal,
        ]);
    }
}
