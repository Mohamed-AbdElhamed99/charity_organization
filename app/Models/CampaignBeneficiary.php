<?php

namespace App\Models;

use App\Enums\AidType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CampaignBeneficiary extends Pivot
{
    protected $table = 'campaign_beneficiaries';

    protected function casts(): array
    {
        return [
            'aid_type'   => AidType::class,
            'aid_amount' => 'decimal:2',
            'aid_date'   => 'date',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
