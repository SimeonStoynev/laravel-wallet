<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Http\Requests\Admin\RefundOrderRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Contracts\View\View as ViewContract;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
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
        } catch (Exception $e) {
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
            $order = $this->orderService->refundOrder($order, is_numeric($amount) ? (float) $amount : null);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Order refunded successfully',
                    'order' => $order,
                ]);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Order refunded successfully');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
