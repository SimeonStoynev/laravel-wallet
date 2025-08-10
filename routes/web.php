<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Customer\TransactionController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;

Route::get('/', function () {
    return view('welcome');
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

    // Order Management
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/process', [AdminOrderController::class, 'process'])->name('orders.process');
    Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
});

// Customer Routes
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        /** @var view-string $view */
        $view = 'customer.dashboard';
        return view($view);
    })->name('dashboard');

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

// Redirect authenticated users to their respective dashboards
Route::middleware('auth')->get('/dashboard', function () {
    $role = auth()->user()->role ?? 'customer';
    return redirect()->route($role.'.dashboard');
})->name('dashboard');
