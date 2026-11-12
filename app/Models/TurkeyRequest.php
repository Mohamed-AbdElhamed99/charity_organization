<?php

namespace App\Models;

use App\Enums\TurkeyRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurkeyRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => TurkeyRequestStatus::class,
            'delivered_at' => 'datetime',
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

    public function beneficiarySupport(): BelongsTo
    {
        return $this->belongsTo(BeneficiarySupport::class);
    }

    /** Requests that still hold a turkey (pending or delivered). */
    public function scopeHoldingStock($query)
    {
        return $query->whereIn('status', [TurkeyRequestStatus::Pending, TurkeyRequestStatus::Delivered]);
    }

    public function scopePending($query)
    {
        return $query->where('status', TurkeyRequestStatus::Pending);
    }
}
