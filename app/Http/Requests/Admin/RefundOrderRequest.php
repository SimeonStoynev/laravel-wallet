<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Order;

class RefundOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user !== null && isset($user->role) && $user->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeOrder = $this->route('order');
        $maxAmount = 999999.99;
        if ($routeOrder instanceof Order && isset($routeOrder->amount)) {
            $maxAmount = (float) $routeOrder->amount;
        }

        return [
            'amount' => 'nullable|numeric|min:0.01|max:'.$maxAmount,
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
            'amount.numeric' => 'Refund amount must be a valid number.',
            'amount.min' => 'Refund amount must be at least 0.01.',
            'amount.max' => 'Refund amount cannot exceed the order amount.',
        ];
    }
}
