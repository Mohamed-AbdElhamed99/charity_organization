<?php

namespace App\Http\Requests\Admin\Transaction;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\BankAccount;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTransactionRequest extends FormRequest
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
            'account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')],
            'transaction_type' => ['required', 'string', Rule::enum(TransactionType::class)],
            'direction' => ['required', 'string', Rule::enum(TransactionDirection::class)],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'fee_amount' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method_id' => ['nullable', 'integer', Rule::exists('payment_methods', 'id')],
            'original_currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')],
            'original_amount' => ['nullable', 'numeric', 'min:0'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.00000001'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:jpeg,jpg,png,pdf', 'max:10240'],
            'remove_document_ids' => ['nullable', 'array'],
            'remove_document_ids.*' => ['integer'],
            'transfer' => ['nullable', 'array'],
            'transfer.recipient_kind' => ['required_if:transaction_type,transfer', Rule::in(['user', 'beneficiary', 'other'])],
            'transfer.recipient_id' => ['nullable', 'integer'],
            'transfer.recipient_label' => ['nullable', 'string', 'max:255'],
            'transfer.recipient_phone' => ['nullable', 'string', 'max:50'],
            'transfer.purpose' => ['required_if:transaction_type,transfer', 'nullable', 'string', 'max:255'],
            'transfer.campaign_id' => ['nullable', 'integer', Rule::exists('campaigns', 'id')],
            'transfer.transfer_date' => ['nullable', 'date'],
            'transfer.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('transaction_type') !== TransactionType::Transfer->value) {
                return;
            }

            $kind = $this->input('transfer.recipient_kind');
            $recipientId = $this->input('transfer.recipient_id');
            $label = $this->input('transfer.recipient_label');

            if ($kind === 'user') {
                if (! $recipientId || ! User::query()->whereKey($recipientId)->exists()) {
                    $validator->errors()->add('transfer.recipient_id', __('Select a valid user recipient.'));
                }
            } elseif ($kind === 'beneficiary') {
                if (! $recipientId || ! Beneficiary::query()->whereKey($recipientId)->exists()) {
                    $validator->errors()->add('transfer.recipient_id', __('Select a valid beneficiary recipient.'));
                }
            } elseif ($kind === 'other' && blank($label)) {
                $validator->errors()->add('transfer.recipient_label', __('Enter a recipient name.'));
            }

            $account = BankAccount::query()->find($this->input('account_id'));
            $originalCurrencyId = $this->input('original_currency_id');

            if ($account && $originalCurrencyId && (int) $originalCurrencyId !== (int) $account->currency_id) {
                if ($this->input('original_amount') === null) {
                    $validator->errors()->add('original_amount', __('Original amount is required for FX conversions.'));
                }
                if ($this->input('exchange_rate') === null) {
                    $validator->errors()->add('exchange_rate', __('Exchange rate is required for FX conversions.'));
                }
            }
        });
    }
}
