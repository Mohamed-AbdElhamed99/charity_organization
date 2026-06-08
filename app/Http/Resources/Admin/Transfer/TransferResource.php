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
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'transaction' => new TransactionResource($this->whenLoaded('transaction')),
            'campaign_id' => $this->campaign_id,
            'campaign' => $this->whenLoaded('campaign', fn () => [
                'id' => $this->campaign->id,
                'title_en' => $this->campaign->title_en,
                'title_ar' => $this->campaign->title_ar,
            ]),
            'recipient_type' => $this->recipient_type?->value,
            'recipient_type_label' => $this->recipient_type?->label(),
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'beneficiary_id' => $this->beneficiary_id,
            'beneficiary' => $this->whenLoaded('beneficiary', fn () => [
                'id' => $this->beneficiary->id,
                'display_name' => $this->beneficiary->displayName ?? null,
            ]),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
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
