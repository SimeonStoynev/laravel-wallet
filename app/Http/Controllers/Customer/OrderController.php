<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Http\Requests\Customer\CreateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View as ViewContract;
use App\Models\User;
use Exception;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display user's orders
     */
    public function index(Request $request): ViewContract|JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query = is_string($search) ? $search : '';
            // If your service supports user-scoped search, replace with that method.
            $orders = $this->orderService->searchOrders($query, $user);
        } else {
            $perPage = $request->get('per_page', 15);
            $orders = $this->orderService->getUserOrders($user, is_numeric($perPage) ? (int) $perPage : 15);
        }

        $stats = $this->orderService->getOrderStats($user);

        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $orders,
                'stats' => $stats,
            ]);
        }

        /** @var view-string $view */
        $view = 'customer.orders.index';
        return view($view, compact('orders', 'stats'));
    }

    /**
     * Show the form for creating a new order (add money)
     */
    public function create(): ViewContract
    {
        /** @var view-string $view */
        $view = 'customer.orders.create';
        return view($view);
    }

    /**
     * Store a newly created order (add money request)
     */
    public function store(CreateOrderRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validated();
            $amount = $validated['amount'];
            $amountOut = is_float($amount) || is_int($amount)
                ? $amount
                : (is_string($amount) ? $amount : '0');
            $title = isset($validated['title']) && is_string($validated['title'])
                ? $validated['title']
                : 'Add Money to Wallet';
            $description = isset($validated['description']) && is_string($validated['description'])
                ? $validated['description']
                : null;
            $paymentMethod = isset($validated['payment_method']) && is_string($validated['payment_method'])
                ? $validated['payment_method']
                : 'manual';

            $orderData = [
                'amount' => $amountOut,
                'title' => $title,
                'description' => $description,
                'metadata' => [
                    'payment_method' => $paymentMethod,
                    'created_by' => 'merchant',
                ],
            ];
            $user = auth()->user();
            if (!$user instanceof User) {
                return redirect()->route('login');
            }
            $order = $this->orderService->createOrder($user, $orderData);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Order created successfully',
                    'order' => $order,
                ], 201);
            }

            return redirect()->route('customer.orders.show', $order)
                ->with('success', 'Order created successfully. Please complete payment to add money to your wallet.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified order
     */
    public function show(int $id): ViewContract|JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $order = $user->orders()->with('transactions')->whereKey($id)->firstOrFail();

        if (request()->wantsJson()) {
            return response()->json($order);
        }

        /** @var view-string $view */
        $view = 'customer.orders.show';
        return view($view, compact('order'));
    }

    /**
     * Cancel an order
     */
    public function cancel(int $id): RedirectResponse|JsonResponse
    {
        try {
            /** @var User $user */
            $user = auth()->user();
            $order = $user->orders()->whereKey($id)->firstOrFail();

            $order = $this->orderService->cancelOrder($order);

            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Order cancelled successfully',
                    'order' => $order,
                ]);
            }

            return redirect()->route('customer.orders.index')
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
     * Simulate payment completion (for demo purposes)
     */
    public function simulatePayment(int $id): RedirectResponse|JsonResponse
    {
        try {
            /** @var User $user */
            $user = auth()->user();
            $order = $user->orders()->whereKey($id)->firstOrFail();

            // In production, this would be handled by payment gateway callback
            $order = $this->orderService->processOrder($order);

            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Payment successful. Money added to wallet.',
                    'order' => $order,
                ]);
            }

            return redirect()->route('customer.orders.show', $order)
                ->with('success', 'Payment successful! Money has been added to your wallet.');
        } catch (Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
