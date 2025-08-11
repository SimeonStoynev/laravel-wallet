<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
// Wallet Events (active)
use App\Events\PaymentReceived;
use App\Events\MoneyTransferred;
use App\Events\BalanceUpdated;
// Wallet Listeners (active)
use App\Listeners\PaymentReceivedListener;
use App\Listeners\MoneyTransferredListener;
use App\Listeners\StoreWalletEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        PaymentReceived::class => [
            PaymentReceivedListener::class,
        ],

        MoneyTransferred::class => [
            MoneyTransferredListener::class,
            StoreWalletEvent::class,
        ],

        BalanceUpdated::class => [
            StoreWalletEvent::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
