<?php

namespace App\Http\Requests\Admin\CampaignExpense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignExpenseRequest extends FormRequest
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
            'campaign_id' => ['required', 'integer', Rule::exists('campaigns', 'id')],
            'account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')],
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'item_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'expense_date' => ['required', 'date'],
            'responsible_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'payment_method_id' => ['nullable', 'integer', Rule::exists('payment_methods', 'id')],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'original_currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')],
            'original_amount' => ['nullable', 'numeric', 'min:0'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.00000001'],
        ];
    }
}
