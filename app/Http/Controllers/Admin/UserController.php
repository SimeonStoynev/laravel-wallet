<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use App\Services\TransactionService;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\AddMoneyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Contracts\View\View as ViewContract;

class UserController extends Controller
{
    protected UserService $userService;
    protected TransactionService $transactionService;

    public function __construct(UserService $userService, TransactionService $transactionService)
    {
        $this->userService = $userService;
        $this->transactionService = $transactionService;
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request): ViewContract|JsonResponse
    {
        if ($request->has('search')) {
            $searchQuery = $request->get('search');
            $users = $this->userService->searchUsers(is_string($searchQuery) ? $searchQuery : '');
        } else {
            $perPage = $request->get('per_page', 15);
            $users = $this->userService->getPaginatedUsers(is_numeric($perPage) ? (int) $perPage : 15);
        }

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        /** @var view-string $view */
        $view = 'admin.users.index';
        return view($view, compact('users'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create(): ViewContract
    {
        /** @var view-string $view */
        $view = 'admin.users.create';
        return view($view);
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $data = [
            'name' => isset($validated['name']) && is_string($validated['name']) ? $validated['name'] : '',
            'email' => isset($validated['email']) && is_string($validated['email']) ? $validated['email'] : '',
            'password' => isset($validated['password']) && is_string($validated['password']) ? $validated['password'] : '',
            'role' => isset($validated['role']) && is_string($validated['role']) ? $validated['role'] : 'customer',
        ];
        $user = $this->userService->createUser($data);

        if ($request->wantsJson()) {
            return response()->json($user, 201);
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User created successfully');
    }

    /**
     * Display the specified user with transactions
     */
    public function show(User $user): ViewContract|JsonResponse
    {
        $user = $this->userService->getUserWithRelations($user->id);
        $transactions = $this->transactionService->getUserTransactions($user);
        $stats = $this->transactionService->getUserTransactionStats($user);

        if (request()->wantsJson()) {
            return response()->json([
                'user' => $user,
                'transactions' => $transactions,
                'stats' => $stats,
            ]);
        }

        /** @var view-string $view */
        $view = 'admin.users.show';
        return view($view, compact('user', 'transactions', 'stats'));
    }

    /**
     * Show the form for editing the user
     */
    public function edit(User $user): ViewContract
    {
        /** @var view-string $view */
        $view = 'admin.users.edit';
        return view($view, compact('user'));
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $data = [];
        if (isset($validated['name']) && is_string($validated['name'])) {
            $data['name'] = $validated['name'];
        }
        if (isset($validated['email']) && is_string($validated['email'])) {
            $data['email'] = $validated['email'];
        }
        if (isset($validated['role']) && is_string($validated['role'])) {
            $data['role'] = $validated['role'];
        }
        if (isset($validated['password']) && is_string($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user = $this->userService->updateUser($user, $data);

        if ($request->wantsJson()) {
            return response()->json($user);
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user): RedirectResponse|JsonResponse
    {
        try {
            $this->userService->deleteUser($user);

            if (request()->wantsJson()) {
                return response()->json(['message' => 'User deleted successfully']);
            }

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Add money to user's wallet
     */
    public function addMoney(AddMoneyRequest $request, User $user): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validated();
            $amount = $validated['amount'] ?? 0;
            $description = $validated['description'] ?? '';

            $transaction = $this->transactionService->addMoney(
                $user,
                is_numeric($amount) ? (float) $amount : 0,
                is_string($description) ? $description : '',
                auth()->user()
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Money added successfully',
                    'transaction' => $transaction,
                    'new_balance' => $this->userService->getUserBalance($user),
                ]);
            }

            $amountDisplay = $validated['amount'] ?? 0;
            $amountStr = is_scalar($amountDisplay) ? (string) $amountDisplay : '0';
            return redirect()->route('admin.users.show', $user)
                ->with('success', "Successfully added {$amountStr} to user's wallet");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
