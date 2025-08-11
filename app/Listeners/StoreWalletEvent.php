<?php

namespace App\Listeners;

use App\Events\WalletEvent;
use App\Events\MoneyTransferred;
use App\Models\Event as EventModel;

class StoreWalletEvent
{
    /**
     * Handle the event by persisting it to the event store.
     * Accept any event; if it's a WalletEvent, call store(); otherwise map known events.
     *
     * @param mixed $event
     */
    public function handle($event): void
    {
        if ($event instanceof WalletEvent) {
            $event->store();
            return;
        }

        // Map non-WalletEvent domain events we care about
        if ($event instanceof MoneyTransferred) {
            EventModel::createForAggregate(
                \App\Models\User::class,
                $event->sender->id,
                'MoneyTransferred',
                [
                    'sender_id' => $event->sender->id,
                    'recipient_id' => $event->recipient->id,
                    'amount' => (float) $event->amount,
                    'debit_transaction_id' => $event->debitTransaction->id,
                    'credit_transaction_id' => $event->creditTransaction->id,
                ],
                [
                    'recipient_email' => $event->recipient->email,
                    'sender_email' => $event->sender->email,
                ]
            );
        }
    }
}
