<?php

namespace App\Http\Requests\Admin\GeneralExpense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeneralExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('general_expense_categories', 'id')->where('is_active', true),
            ],
            'payment_method_id' => [
                'nullable',
                'integer',
                Rule::exists('payment_methods', 'id')->where('is_active', true),
            ],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'is_recurring' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'original_currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')],
            'original_amount' => ['nullable', 'numeric', 'min:0'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.00000001'],
        ];
    }
}
