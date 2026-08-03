<?php

namespace App\Http\Resources\Admin\Transfer;

use App\Http\Resources\Admin\Transaction\TransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $campaigns = $this->whenLoaded('transaction', function () {
            if (! $this->transaction?->relationLoaded('campaigns')) {
                return [];
            }

            return $this->transaction->campaigns->map(fn ($campaign) => [
                'id' => $campaign->id,
                'title_en' => $campaign->title_en,
                'title_ar' => $campaign->title_ar,
            ])->values()->all();
        });

        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'transaction' => new TransactionResource($this->whenLoaded('transaction')),
            'campaigns' => $campaigns,
            'campaign_id' => $this->whenLoaded('transaction', function () {
                return $this->transaction?->relationLoaded('campaigns')
                    ? $this->transaction->campaigns->first()?->id
                    : null;
            }),
            'recipient_type' => $this->recipient_type,
            'recipient_label' => $this->recipient_label,
            'recipient_phone' => $this->recipient_phone,
            'recipient_id' => $this->recipient_id,
            'recipient' => $this->whenLoaded('recipient'),
            'amount' => $this->amount,
            'transfer_date' => $this->transfer_date?->toDateString(),
            'purpose' => $this->purpose,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
