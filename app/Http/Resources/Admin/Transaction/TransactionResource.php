<?php

namespace App\Http\Resources\Admin\Transaction;

use App\Models\Beneficiary;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->relationLoaded('transactionType')
            ? $this->transactionType
            : $this->transactionType()->first();

        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'currency_id' => $this->account->currency_id,
            ]),
            'transaction_type_id' => $this->transaction_type_id,
            'transaction_type' => $type?->code,
            'transaction_type_label' => $type?->name,
            'transaction_type_record' => $type ? [
                'id' => $type->id,
                'name' => $type->name,
                'code' => $type->code,
                'is_system' => $type->is_system,
            ] : null,
            'direction' => $this->direction?->value,
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', fn () => [
                'id' => $this->currency->id,
                'code' => $this->currency->code,
                'symbol' => $this->currency->symbol,
            ]),
            'original_currency_id' => $this->original_currency_id,
            'original_currency' => $this->whenLoaded('originalCurrency', fn () => $this->originalCurrency ? [
                'id' => $this->originalCurrency->id,
                'code' => $this->originalCurrency->code,
                'symbol' => $this->originalCurrency->symbol,
            ] : null),
            'gross_amount' => $this->gross_amount,
            'fee_amount' => $this->fee_amount,
            'net_amount' => $this->net_amount,
            'balance_before' => $this->balance_before,
            'original_amount' => $this->original_amount,
            'exchange_rate' => $this->exchange_rate,
            'currency_equivalents' => $this->currency_equivalents,
            'currency_equivalent_amounts' => $this->currency_equivalent_amounts,
            'running_balance' => $this->running_balance,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'reference_number' => $this->reference_number,
            'description' => $this->description,
            'notes' => $this->notes,
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => [
                'id' => $this->paymentMethod->id,
                'name' => $this->paymentMethod->name,
                'code' => $this->paymentMethod->code,
            ]),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'is_reconciled' => $this->is_reconciled,
            'created_at' => $this->created_at?->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
            'documents' => $this->whenLoaded('media', fn () => $this->getMedia('receipts')->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'url' => $media->getUrl(),
            ])->values()->all()),
            'donation' => $this->whenLoaded('donation', fn () => [
                'id' => $this->donation->id,
                'campaign_id' => $this->donation->campaign_id,
                'donor_name' => $this->donation->donor_name,
                'amount' => $this->donation->amount,
            ]),
            'campaign_expense' => $this->whenLoaded('campaignExpense', fn () => [
                'id' => $this->campaignExpense->id,
                'campaign_id' => $this->campaignExpense->campaign_id,
                'amount' => $this->campaignExpense->amount,
                'expense_date' => $this->campaignExpense->expense_date?->toDateString(),
            ]),
            'general_expense' => $this->whenLoaded('generalExpenses', function () {
                $expense = $this->generalExpenses->first();

                if ($expense === null) {
                    return null;
                }

                return [
                    'id' => $expense->id,
                    'name' => $expense->name,
                    'amount' => $expense->amount,
                    'expense_date' => $expense->expense_date?->toDateString(),
                ];
            }),
            'transfer' => $this->whenLoaded('transfer', function () {
                $recipient = $this->transfer->relationLoaded('recipient') ? $this->transfer->recipient : null;
                $kind = match ($this->transfer->recipient_type) {
                    User::class => 'user',
                    Beneficiary::class => 'beneficiary',
                    default => 'other',
                };

                return [
                    'id' => $this->transfer->id,
                    'recipient_kind' => $kind,
                    'recipient_id' => $this->transfer->recipient_id,
                    'recipient_label' => $this->transfer->recipient_label,
                    'recipient_phone' => $this->transfer->recipient_phone,
                    'recipient_name' => match ($kind) {
                        'user' => $recipient?->name,
                        'beneficiary' => $recipient?->display_name,
                        default => $this->transfer->recipient_label,
                    },
                    'amount' => $this->transfer->amount,
                    'purpose' => $this->transfer->purpose,
                    'transfer_date' => $this->transfer->transfer_date?->toDateString(),
                    'notes' => $this->transfer->notes,
                ];
            }),
            'bank_expense' => $this->whenLoaded('bankExpense', fn () => [
                'id' => $this->bankExpense->id,
                'amount' => $this->bankExpense->amount,
                'expense_date' => $this->bankExpense->expense_date?->toDateString(),
            ]),
            'links' => [
                'beneficiaries' => $this->whenLoaded('beneficiaries', fn () => $this->beneficiaries->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => $item->display_name,
                ])->values()->all()),
                'campaigns' => $this->whenLoaded('campaigns', fn () => $this->campaigns->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => $item->title_en,
                ])->values()->all()),
                'meetings' => $this->whenLoaded('meetings', fn () => $this->meetings->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => trim(($item->meeting_number ? "{$item->meeting_number} — " : '').($item->title ?? '')),
                ])->values()->all()),
                'general_expenses' => $this->whenLoaded('generalExpenses', fn () => $this->generalExpenses->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => $item->name,
                    'amount' => $item->amount,
                    'expense_date' => $item->expense_date?->toDateString(),
                ])->values()->all()),
            ],
            'beneficiary_ids' => $this->whenLoaded(
                'beneficiaries',
                fn () => $this->beneficiaries->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ),
            'campaign_ids' => $this->whenLoaded(
                'campaigns',
                fn () => $this->campaigns->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ),
            'meeting_ids' => $this->whenLoaded(
                'meetings',
                fn () => $this->meetings->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ),
            'general_expense_ids' => $this->whenLoaded(
                'generalExpenses',
                fn () => $this->generalExpenses->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ),
            'export_expenses' => $this->exportAmountForCodes([
                TransactionType::CODE_CAMPAIGN_EXPENSE,
                TransactionType::CODE_GENERAL_EXPENSE,
            ], $type),
            'export_donations' => $this->exportAmountForCodes([TransactionType::CODE_DONATION], $type),
            'export_transfer' => $this->exportAmountForCodes([
                TransactionType::CODE_TRANSFER,
                TransactionType::CODE_BANK_TRANSFER,
            ], $type),
            'export_details' => $this->exportDetails(),
        ];
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function exportAmountForCodes(array $codes, ?TransactionType $type): ?string
    {
        if ($type === null || ! in_array($type->code, $codes, true)) {
            return null;
        }

        return (string) $this->net_amount;
    }

    private function exportDetails(): string
    {
        $parts = array_filter([
            $this->description,
            $this->reference_number ? "Ref: {$this->reference_number}" : null,
            $this->notes,
        ]);

        return implode(' | ', $parts);
    }
}
