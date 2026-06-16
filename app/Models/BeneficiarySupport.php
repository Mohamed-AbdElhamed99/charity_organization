<?php

namespace App\Models;

use App\Enums\SupportStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeneficiarySupport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'supported_at' => 'date',
            'status' => SupportStatus::class,
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BeneficiarySupportItem::class);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', SupportStatus::Delivered);
    }

    public function scopeBetweenDates($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('supported_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('supported_at', '<=', $to));
    }

    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeForBeneficiary($query, int $beneficiaryId)
    {
        return $query->where('beneficiary_id', $beneficiaryId);
    }

    protected function totalCost(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->relationLoaded('items')
                ? (int) $this->items->sum('total_cost')
                : (int) $this->items()->sum('total_cost'),
        );
    }
}
