<?php

namespace App\Models;

use App\Enums\DonationStatus;
use App\Enums\StripeStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => DonationStatus::class,
            'stripe_status' => StripeStatus::class,
            'is_general' => 'boolean',
            'donor_covers_fee' => 'boolean',
            'is_anonymous' => 'boolean',
            'amount' => 'integer',
            'metadata' => 'array',
            'receipt_sent_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopeGeneral($query)
    {
        return $query->where('is_general', true);
    }

    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeDesignated($query)
    {
        return $query->where('is_general', false)->whereNotNull('campaign_id');
    }

    public function scopeSucceeded($query)
    {
        return $query->where('status', DonationStatus::Succeeded);
    }

    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);
    }

    public function scopeByDonor($query, int $donorId)
    {
        return $query->where('donor_id', $donorId);
    }

    protected function grossAmountCents(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->transaction
                ? Money::decimalToCents($this->transaction->gross_amount)
                : null,
        );
    }

    protected function feeAmountCents(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->transaction
                ? Money::decimalToCents($this->transaction->fee_amount)
                : null,
        );
    }

    protected function netAmountCents(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->transaction
                ? Money::decimalToCents($this->transaction->net_amount)
                : null,
        );
    }

    protected function purposeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_general
                ? 'General Donation'
                : ($this->campaign?->title ?? $this->purpose_note ?? 'Unspecified'),
        );
    }

    protected function donorDisplayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_anonymous
                ? 'Anonymous'
                : ($this->donor?->donorProfile?->displayName
                    ?? $this->donor?->name
                    ?? 'Anonymous'),
        );
    }

    protected function donorAdminName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->donor?->donorProfile?->displayName
                ?? $this->donor?->name
                ?? 'Unknown',
        );
    }
}
