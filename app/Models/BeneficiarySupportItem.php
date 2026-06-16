<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiarySupportItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'integer',
            'total_cost' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $quantity = max(1, (int) $item->quantity);
            $unitCost = max(0, (int) $item->unit_cost);

            $item->quantity = $quantity;
            $item->unit_cost = $unitCost;
            $item->total_cost = $quantity * $unitCost;
        });
    }

    public function support(): BelongsTo
    {
        return $this->belongsTo(BeneficiarySupport::class, 'beneficiary_support_id');
    }

    public function aidItem(): BelongsTo
    {
        return $this->belongsTo(AidItem::class);
    }

    public function campaignExpense(): BelongsTo
    {
        return $this->belongsTo(CampaignExpense::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
