<?php

namespace App\Http\Controllers\Customer;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Customer\CreateOrderRequest;
use Illuminate\Contracts\View\View as ViewContract;

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
                ->with('success', 'Order received and pending payment. An admin will process and complete this order.');
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

    // Note: Order status transitions (process, cancel, refund) are admin-only.
}
