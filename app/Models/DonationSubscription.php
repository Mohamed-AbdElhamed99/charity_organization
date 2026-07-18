<?php

namespace App\Models;

use App\Enums\DonationSubscriptionStatus;
use App\Enums\RecurrenceFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonationSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => DonationSubscriptionStatus::class,
            'frequency' => RecurrenceFrequency::class,
            'donor_covers_fee' => 'boolean',
            'amount_cents' => 'integer',
            'billing_cycle_anchor_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(DonationSubscriptionAllocation::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', DonationSubscriptionStatus::Active);
    }
}
