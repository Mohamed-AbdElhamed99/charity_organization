<?php

namespace App\Http\Requests\Admin\Transaction;

use App\Enums\TransactionDirection;
use App\Models\BankAccount;
use App\Models\Beneficiary;
use App\Models\TransactionType;
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
            'transaction_type_id' => ['required', 'integer', Rule::exists('transaction_types', 'id')],
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
            'currency_equivalents' => ['nullable', 'array'],
            'currency_equivalents.*.currency_code' => [
                'required',
                'string',
                'max:10',
                Rule::exists('currencies', 'code'),
            ],
            'currency_equivalents.*.equivalent' => ['required', 'numeric', 'gt:0'],
            'balance_before' => ['nullable', 'numeric'],
            'running_balance' => ['nullable', 'numeric'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:jpeg,jpg,png,pdf', 'max:10240'],
            'remove_document_ids' => ['nullable', 'array'],
            'remove_document_ids.*' => ['integer'],
            'beneficiary_ids' => ['nullable', 'array'],
            'beneficiary_ids.*' => ['integer', Rule::exists('beneficiaries', 'id')],
            'campaign_ids' => ['nullable', 'array'],
            'campaign_ids.*' => ['integer', Rule::exists('campaigns', 'id')],
            'meeting_ids' => ['nullable', 'array'],
            'meeting_ids.*' => ['integer', Rule::exists('meetings', 'id')],
            'general_expense_ids' => ['nullable', 'array'],
            'general_expense_ids.*' => ['integer', Rule::exists('general_expenses', 'id')],
            'transfer' => ['nullable', 'array'],
            'transfer.recipient_kind' => ['nullable', Rule::in(['user', 'beneficiary', 'other'])],
            'transfer.recipient_id' => ['nullable', 'integer'],
            'transfer.recipient_label' => ['nullable', 'string', 'max:255'],
            'transfer.recipient_phone' => ['nullable', 'string', 'max:50'],
            'transfer.purpose' => ['nullable', 'string', 'max:255'],
            'transfer.transfer_date' => ['nullable', 'date'],
            'transfer.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = TransactionType::query()->find($this->input('transaction_type_id'));

            if ($type?->isTransfer()) {
                if (blank($this->input('transfer.recipient_kind'))) {
                    $validator->errors()->add('transfer.recipient_kind', __('The recipient kind field is required.'));
                }

                if (blank($this->input('transfer.purpose'))) {
                    $validator->errors()->add('transfer.purpose', __('The purpose field is required.'));
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
            }

            $account = BankAccount::query()->with('currency')->find($this->input('account_id'));
            $originalCurrencyId = $this->input('original_currency_id');

            if ($account && $originalCurrencyId && (int) $originalCurrencyId !== (int) $account->currency_id) {
                if ($this->input('original_amount') === null) {
                    $validator->errors()->add('original_amount', __('Original amount is required for FX conversions.'));
                }
                if ($this->input('exchange_rate') === null) {
                    $validator->errors()->add('exchange_rate', __('Exchange rate is required for FX conversions.'));
                }
            }

            $this->validateCurrencyEquivalents($validator, $account);
        });
    }

    private function validateCurrencyEquivalents(Validator $validator, ?BankAccount $account): void
    {
        $rows = $this->input('currency_equivalents');

        if (! is_array($rows) || $rows === []) {
            return;
        }

        $ledgerCode = strtoupper((string) ($account?->currency?->code ?? ''));
        $seen = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = strtoupper(trim((string) ($row['currency_code'] ?? '')));

            if ($code === '') {
                continue;
            }

            if ($ledgerCode !== '' && $code === $ledgerCode) {
                $validator->errors()->add(
                    "currency_equivalents.{$index}.currency_code",
                    __('Currency equivalent cannot use the ledger currency.'),
                );
            }

            if (isset($seen[$code])) {
                $validator->errors()->add(
                    "currency_equivalents.{$index}.currency_code",
                    __('Currency equivalent codes must be unique.'),
                );
            }

            $seen[$code] = true;
        }
    }
}
