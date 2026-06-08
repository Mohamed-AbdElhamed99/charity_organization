<?php

namespace App\Http\Requests\Admin\Transaction;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_transactions') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')],
            'transaction_type' => ['required', 'string', Rule::enum(TransactionType::class)],
            'direction' => ['required', 'string', Rule::enum(TransactionDirection::class)],
            'currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method_id' => ['nullable', 'integer', Rule::exists('payment_methods', 'id')],
        ];
    }
}
