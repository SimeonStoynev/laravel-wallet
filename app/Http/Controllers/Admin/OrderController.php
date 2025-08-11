<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\TransactionService;
use App\Http\Requests\Admin\RefundOrderRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Contracts\View\View as ViewContract;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected TransactionService $transactionService;

    public function __construct(OrderService $orderService, TransactionService $transactionService)
    {
        $this->orderService = $orderService;
        $this->transactionService = $transactionService;
    }

    /**
     * Display all orders
     */
    public function index(Request $request): ViewContract|JsonResponse
    {
        if ($request->has('search')) {
            $searchQuery = $request->get('search');
            $orders = $this->orderService->searchOrders(is_string($searchQuery) ? $searchQuery : '');
        } else {
            $perPage = $request->get('per_page', 15);
            $orders = $this->orderService->getAllOrders(is_numeric($perPage) ? (int) $perPage : 15);
        }

        $stats = $this->orderService->getOrderStats();

        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $orders,
                'stats' => $stats,
            ]);
        }

        /** @var view-string $view */
        $view = 'admin.orders.index';
        return view($view, compact('orders', 'stats'));
    }

    /**
     * Display the specified order
     */
    public function show(Order $order): ViewContract|JsonResponse
    {
        $order->load(['user', 'transactions']);

        if (request()->wantsJson()) {
            return response()->json($order);
        }

        /** @var view-string $view */
        $view = 'admin.orders.show';
        return view($view, compact('order'));
    }

    /**
     * Process a pending order
     */
    public function process(Order $order): RedirectResponse|JsonResponse
    {
        try {
            $order = $this->orderService->processOrder($order);

            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Order processed successfully',
                    'order' => $order,
                ]);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Order processed successfully');
        } catch (\Throwable $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an order
     */
    public function cancel(Order $order): RedirectResponse|JsonResponse
    {
        try {
            $order = $this->orderService->cancelOrder($order);

            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Order cancelled successfully',
                    'order' => $order,
                ]);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Order cancelled successfully');
        } catch (Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Refund an order
     */
    public function refund(RefundOrderRequest $request, Order $order): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validated();
            $amount = $validated['amount'] ?? null;
            // Pre-check user balance to provide consistent error flash without relying solely on exceptions
            $requestedAmount = is_numeric($amount) ? (float) $amount : (float) $order->amount;
            Log::info('refund.precheck', ['order_id' => $order->id, 'requested' => $requestedAmount]);
            $user = User::query()->find($order->user_id);
            if (!$user) {
                Log::warning('refund.user_not_found', ['order_id' => $order->id]);
                return redirect()->route('admin.orders.show', $order)->with('error', 'User not found for order');
            }
            // Lock the row and read inside a transaction to avoid stale reads
            $lockedAmount = DB::transaction(function () use ($user) {
                $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                return (float) $locked->amount;
            });
            // Inspect various views of balance to diagnose test environment behavior
            $eloquentAmount = (float) ($user->amount ?? 0.0);
            $rawRead = DB::table('users')->where('id', $user->id)->value('amount');
            $rawWrite = DB::table('users')->useWritePdo()->where('id', $user->id)->value('amount');
            // Prefer the locked amount which is always available from the transaction above
            $currentBalance = (float) $lockedAmount;
            Log::info('refund.balance', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'balance' => $currentBalance,
                'eloquent_amount' => $eloquentAmount,
                'raw_read' => $rawRead,
                'raw_write' => $rawWrite,
                'locked_amount' => $lockedAmount,
                'db_conn' => DB::connection()->getName(),
                'user_conn' => $user->getConnectionName(),
            ]);
            if ($requestedAmount > $currentBalance) {
                Log::info('refund.precheck_insufficient', ['order_id' => $order->id, 'requested' => $requestedAmount, 'balance' => $currentBalance]);
                return redirect()->route('admin.orders.show', $order)->with('error', 'Insufficient balance for debit');
            }
            $order = $this->orderService->refundOrder($order, is_numeric($amount) ? (float) $amount : null);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Order refunded successfully',
                    'order' => $order,
                ]);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Order refunded successfully');
        } catch (\Throwable $e) {
            Log::error('refund.exception', ['order_id' => $order->id, 'message' => $e->getMessage()]);
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('error', $e->getMessage());
        }
    }
}
