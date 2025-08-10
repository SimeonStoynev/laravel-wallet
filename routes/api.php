<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Customer\TransactionController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // Admin API Routes
    Route::middleware('role:admin')->prefix('admin')->name('api.admin.')->group(function () {
        // Users
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/add-money', [AdminUserController::class, 'addMoney'])->name('users.add-money');

        // Orders
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/process', [AdminOrderController::class, 'process'])->name('orders.process');
        Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
    });

    // Customer API Routes
    Route::middleware('role:customer')->prefix('customer')->name('api.customer.')->group(function () {
        // Transactions
        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::post('transactions/transfer', [TransactionController::class, 'transfer'])->name('transactions.transfer');
        Route::get('balance', [TransactionController::class, 'balance'])->name('balance');

        // Orders
        Route::get('orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [CustomerOrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/simulate-payment', [CustomerOrderController::class, 'simulatePayment'])->name('orders.simulate-payment');
    });

    // Current user info
    Route::get('user', function (Request $request) {
        return $request->user();
    })->name('api.user');
});
