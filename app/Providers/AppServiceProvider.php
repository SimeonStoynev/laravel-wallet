<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register our EventServiceProvider
        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Bootstrap pagination views (Bootstrap 4)
        if (method_exists(Paginator::class, 'useBootstrapFour')) {
            Paginator::useBootstrapFour();
        } elseif (method_exists(Paginator::class, 'useBootstrap')) {
            // Fallback for older/newer Laravel versions
            Paginator::useBootstrap();
        }
    }
}
