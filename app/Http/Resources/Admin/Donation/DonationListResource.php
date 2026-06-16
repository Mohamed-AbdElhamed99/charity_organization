<?php

namespace App\Http\Resources\Admin\Donation;

use App\Models\Donation;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Donation */
class DonationListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at?->toIso8601String(),
            'donor_name' => $this->donor_admin_name,
            'donor_display_name' => $this->donor_display_name,
            'donor_email' => $this->donor?->email,
            'is_anonymous' => $this->is_anonymous,
            'campaign' => $this->is_general
                ? null
                : [
                    'id' => $this->campaign?->id,
                    'title' => $this->campaign?->title,
                ],
            'purpose' => $this->purpose_label,
            'amount_cents' => $this->amount,
            'amount' => $this->amount !== null ? Money::formatUsd($this->amount) : '—',
            'gross_cents' => $this->gross_amount_cents,
            'fee_cents' => $this->fee_amount_cents,
            'net_cents' => $this->net_amount_cents,
            'gross_amount' => $this->transaction?->gross_amount,
            'fee_amount' => $this->transaction?->fee_amount,
            'net_amount' => $this->transaction?->net_amount,
            'currency' => $this->transaction?->currency?->code,
            'status' => $this->status?->value,
            'donor_covers_fee' => $this->donor_covers_fee,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
        ];
    }
}
