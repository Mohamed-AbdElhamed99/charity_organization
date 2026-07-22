<?php

namespace App\Http\Resources\Admin\Transaction;

use App\Enums\TransactionType;
use App\Models\Beneficiary;
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
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'currency_id' => $this->account->currency_id,
            ]),
            'transaction_type' => $this->transaction_type?->value,
            'transaction_type_label' => $this->transaction_type?->label(),
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
            'original_amount' => $this->original_amount,
            'exchange_rate' => $this->exchange_rate,
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
            'general_expense' => $this->whenLoaded('generalExpense', fn () => [
                'id' => $this->generalExpense->id,
                'amount' => $this->generalExpense->amount,
                'expense_date' => $this->generalExpense->expense_date?->toDateString(),
            ]),
            'transfer' => $this->whenLoaded('transfer', function () {
                $recipient = $this->transfer->relationLoaded('recipient') ? $this->transfer->recipient : null;
                $kind = match ($this->transfer->recipient_type) {
                    User::class => 'user',
                    Beneficiary::class => 'beneficiary',
                    default => 'other',
                };

                return [
                    'id' => $this->transfer->id,
                    'campaign_id' => $this->transfer->campaign_id,
                    'campaign' => $this->transfer->relationLoaded('campaign') && $this->transfer->campaign
                        ? [
                            'id' => $this->transfer->campaign->id,
                            'title_en' => $this->transfer->campaign->title_en,
                            'title_ar' => $this->transfer->campaign->title_ar,
                        ]
                        : null,
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
            'export_expenses' => $this->exportAmountForTypes([
                TransactionType::CampaignExpense,
                TransactionType::GeneralExpense,
            ]),
            'export_donations' => $this->exportAmountForTypes([TransactionType::Donation]),
            'export_transfer' => $this->exportAmountForTypes([
                TransactionType::Transfer,
                TransactionType::BankTransfer,
            ]),
            'export_details' => $this->exportDetails(),
        ];
    }

    /**
     * @param  array<int, TransactionType>  $types
     */
    private function exportAmountForTypes(array $types): ?string
    {
        if (! in_array($this->transaction_type, $types, true)) {
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
