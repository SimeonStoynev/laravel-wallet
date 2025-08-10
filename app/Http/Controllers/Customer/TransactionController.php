<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TransactionService;
use App\Http\Requests\Customer\TransferMoneyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View as ViewContract;
use App\Models\User as UserModel;
use Exception;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display user's transactions
     */
    public function index(Request $request): ViewContract|JsonResponse
    {
        /** @var UserModel $user */
        $user = auth()->user();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query = is_string($search) ? $search : '';
            $transactions = $this->transactionService->searchTransactions($query, $user);
        } else {
            $perPage = $request->get('per_page', 15);
            $transactions = $this->transactionService->getUserTransactions($user, is_numeric($perPage) ? (int) $perPage : 15);
        }

        $stats = $this->transactionService->getUserTransactionStats($user);

        if ($request->wantsJson()) {
            return response()->json([
                'transactions' => $transactions,
                'stats' => $stats,
            ]);
        }

        /** @var view-string $view */
        $view = 'customer.transactions.index';
        return view($view, compact('transactions', 'stats'));
    }

    /**
     * Show transfer money form
     */
    public function showTransferForm(): ViewContract
    {
        $users = UserModel::where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        /** @var view-string $view */
        $view = 'customer.transactions.transfer';
        return view($view, compact('users'));
    }

    /**
     * Transfer money to another user
     */
    public function transfer(TransferMoneyRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validated();
            /** @var UserModel $fromUser */
            $fromUser = auth()->user();
            $recipientId = isset($validated['recipient_id']) && is_numeric($validated['recipient_id']) ? (int) $validated['recipient_id'] : 0;
            $recipient = UserModel::query()->whereKey($recipientId)->firstOrFail();
            $amount = isset($validated['amount']) && is_numeric($validated['amount']) ? (float) $validated['amount'] : 0.0;
            $description = isset($validated['description']) && is_string($validated['description']) ? $validated['description'] : '';

            $transactions = $this->transactionService->transferMoney(
                $fromUser,
                $recipient,
                $amount,
                $description
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Money transferred successfully',
                    'debit_transaction' => $transactions['debit'],
                    'credit_transaction' => $transactions['credit'],
                    'new_balance' => $this->transactionService->calculateUserBalance($fromUser),
                ]);
            }

            $amountFormatted = number_format($amount, 2, '.', '');
            $recipientName = (string) $recipient->name;
            return redirect()->route('customer.transactions.index')
                ->with('success', 'Successfully transferred '.$amountFormatted.' to '.$recipientName);
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
     * Get current balance
     */
    public function balance(): JsonResponse
    {
        /** @var UserModel $user */
        $user = auth()->user();
        $balance = $this->transactionService->calculateUserBalance($user);
        $stats = $this->transactionService->getUserTransactionStats($user);

        return response()->json([
            'balance' => $balance,
            'stats' => $stats,
        ]);
    }
}
