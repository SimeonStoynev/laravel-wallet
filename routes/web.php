<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Customer\TransactionController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;

Route::get('/', function () {
    /** @var view-string $view */
    $view = 'react';
    return view($view);
});

// Authentication routes (Laravel Breeze/Jetstream should be installed)
require __DIR__.'/auth.php';

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        /** @var view-string $view */
        $view = 'admin.dashboard';
        return view($view);
    })->name('dashboard');

    // User Management
    Route::resource('users', AdminUserController::class);
    Route::post('users/{user}/add-money', [AdminUserController::class, 'addMoney'])->name('users.add-money');
    Route::post('users/{user}/remove-money', [AdminUserController::class, 'removeMoney'])->name('users.remove-money');

    // Order Management
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/process', [AdminOrderController::class, 'process'])->name('orders.process');
    Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
});

// Wallet routes (merchant access only)
Route::middleware(['auth', 'role:merchant'])->prefix('customer')->name('customer.')->group(function () {
    // Transactions
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/transfer', [TransactionController::class, 'showTransferForm'])->name('transactions.transfer-form');
    Route::post('transactions/transfer', [TransactionController::class, 'transfer'])->name('transactions.transfer');
    Route::get('balance', [TransactionController::class, 'balance'])->name('balance');

    // Orders (Add Money)
    Route::resource('orders', CustomerOrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/simulate-payment', [CustomerOrderController::class, 'simulatePayment'])->name('orders.simulate-payment');
});

// Merchant Routes
Route::middleware(['auth', 'role:merchant'])->prefix('merchant')->name('merchant.')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        /** @var view-string $view */
        $view = 'merchant.dashboard';
        return view($view);
    })->name('dashboard');
});

// Redirect authenticated users to their respective dashboards
Route::middleware('auth')->get('/dashboard', function () {
    $role = auth()->user()->role ?? 'merchant';
    // Normalize/Map roles to existing dashboard routes
    $map = [
        'admin'    => 'admin',
        'merchant' => 'merchant',
    ];
    $target = $map[$role] ?? 'merchant';
    return redirect()->route($target.'.dashboard');
})->name('dashboard');
