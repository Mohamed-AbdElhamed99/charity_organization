<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expense_date'      => 'date',
            'item_price'        => 'decimal:2',
            'quantity'          => 'decimal:3',
            'amount'            => 'decimal:2',
            'residual_quantity' => 'decimal:3',
            'residual_amount'   => 'decimal:2',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeWithResidue($query)
    {
        return $query->where('residual_quantity', '>', 0);
    }

    public function scopeInDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('expense_date', [$from, $to]);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /** Quantity that has been fully distributed */
    protected function distributedQuantity(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->quantity - $this->residual_quantity,
        );
    }
}
