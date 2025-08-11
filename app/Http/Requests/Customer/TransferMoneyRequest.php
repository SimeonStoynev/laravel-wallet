<?php

namespace App\Http\Requests\Customer;

use App\Services\TransactionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class TransferMoneyRequest extends FormRequest
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        parent::__construct();
        $this->transactionService = $transactionService;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'recipient_id' => 'required|exists:users,id|different:'.$userId,
            // Let the controller enforce sufficient funds to ensure consistent session error key
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_id.required' => 'Please select a recipient.',
            'recipient_id.exists' => 'Selected recipient does not exist.',
            'recipient_id.different' => 'You cannot transfer money to yourself.',
            'amount.required' => 'Transfer amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Transfer amount must be at least 0.01.',
            'description.required' => 'Please provide a description for this transfer.',
            'description.max' => 'Description cannot exceed 255 characters.',
        ];
    }
}
