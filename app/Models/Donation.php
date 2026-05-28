<?php

namespace App\Models;

use App\Enums\StripeStatus;
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
            'stripe_status'    => StripeStatus::class,
            'is_general'       => 'boolean',
            'donor_covers_fee' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /** The unified ledger entry for this donation */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /** The donor user (nullable for anonymous donations) */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    /** The campaign this donation is designated for (null = general) */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeGeneral($query)
    {
        return $query->where('is_general', true);
    }

    public function scopeDesignated($query)
    {
        return $query->where('is_general', false)->whereNotNull('campaign_id');
    }

    public function scopeSucceeded($query)
    {
        return $query->where(function ($q) {
            // Non-Stripe donations are always considered confirmed
            $q->whereNull('stripe_status')
              ->orWhere('stripe_status', StripeStatus::Succeeded);
        });
    }

    public function scopeByDonor($query, int $donorId)
    {
        return $query->where('donor_id', $donorId);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /** Shortcut to the transaction's net_amount (what org actually received) */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->transaction?->net_amount,
        );
    }

    protected function grossAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->transaction?->gross_amount,
        );
    }

    protected function feeAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->transaction?->fee_amount,
        );
    }

    /** Human-readable purpose: campaign title or "General Donation" */
    protected function purposeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_general
                ? 'General Donation'
                : ($this->campaign?->title ?? $this->purpose_note ?? 'Unspecified'),
        );
    }

    /** Donor display name: donor name or "Anonymous" */
    protected function donorName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->donor?->donorProfile?->displayName
                ?? $this->donor?->name
                ?? 'Anonymous',
        );
    }
}
