<?php

namespace App\Http\Requests\Admin\Transfer;

use App\Enums\TransferRecipientType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
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
            'account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'recipient_type' => ['required', Rule::enum(TransferRecipientType::class)],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:50'],
            'beneficiary_id' => [
                'nullable',
                'integer',
                'exists:beneficiaries,id',
                Rule::requiredIf(fn () => $this->input('recipient_type') === TransferRecipientType::Beneficiary->value),
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::requiredIf(fn () => $this->input('recipient_type') === TransferRecipientType::User->value),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'transfer_date' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'reference_number' => ['nullable', 'string', 'max:255'],
        ];
    }
}
