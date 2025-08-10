<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RemoveMoneyRequest extends FormRequest
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
        return [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
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
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least 0.01.',
            'amount.max' => 'Amount cannot exceed 999,999.99.',
            'description.max' => 'Description cannot exceed 255 characters.',
        ];
    }
}
