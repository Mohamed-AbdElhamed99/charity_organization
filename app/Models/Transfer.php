<?php

namespace App\Models;

use App\Enums\TransferRecipientType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'recipient_type' => TransferRecipientType::class,
            'amount'         => 'decimal:2',
            'transfer_date'  => 'date',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** When transfer goes directly to a beneficiary */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    /** When transfer is a staff reimbursement */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeToVendors($query)
    {
        return $query->where('recipient_type', TransferRecipientType::Vendor);
    }

    public function scopeToBeneficiaries($query)
    {
        return $query->where('recipient_type', TransferRecipientType::Beneficiary);
    }

    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeInDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('transfer_date', [$from, $to]);
    }
}
