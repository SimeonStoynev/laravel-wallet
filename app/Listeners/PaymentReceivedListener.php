<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class PaymentReceivedListener implements ShouldQueue
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
    public function handle(PaymentReceived $event): void
    {
        // 1. Log the payment
        Log::info('Payment received', [
            'order_id' => $event->order->id,
            'user_id' => $event->user->id,
            'amount' => $event->amount,
            'payment_method' => $event->paymentMethod,
            'timestamp' => now()->toISOString(),
        ]);

        // 2. Update payment statistics
        $this->updatePaymentStatistics($event);

        // 3. Send confirmation notification
        $this->sendPaymentConfirmation($event);

        // 4. Check for loyalty rewards
        $this->checkLoyaltyRewards($event);
    }

    /**
     * Update payment statistics
     */
    private function updatePaymentStatistics(PaymentReceived $event): void
    {
        // This would typically update a statistics table or cache
        // For demonstration purposes, we'll just log it
        Log::info('Updated payment statistics', [
            'user_id' => $event->user->id,
            'total_payments' => $event->user->orders()->where('status', 'completed')->count(),
            'total_amount' => $event->user->orders()->where('status', 'completed')->sum('amount'),
        ]);
    }

    /**
     * Send payment confirmation
     */
    private function sendPaymentConfirmation(PaymentReceived $event): void
    {
        // In a real application, this would send an email or push notification
        // For demonstration purposes, we'll just log it
        Log::info('Payment confirmation notification would be sent', [
            'user_email' => $event->user->email,
            'order_id' => $event->order->id,
            'amount' => $event->amount,
        ]);
    }

    /**
     * Check for loyalty rewards
     */
    private function checkLoyaltyRewards(PaymentReceived $event): void
    {
        // Check if user qualifies for loyalty rewards
        $completedOrdersCount = $event->user->orders()
            ->where('status', 'completed')
            ->count();

        // For example, every 5th payment gets a reward
        if ($completedOrdersCount % 5 === 0) {
            Log::info('User qualifies for loyalty reward', [
                'user_id' => $event->user->id,
                'completed_orders' => $completedOrdersCount,
                'reward_type' => 'cashback',
                'reward_amount' => $event->amount * 0.05, // 5% cashback
            ]);

            // In a real application, this would trigger a reward process
            // For demonstration purposes, we'll just log it
        }
    }
}
