<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type'            => AccountType::class,
            'opening_balance' => 'decimal:2',
            'is_active'       => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBankAccounts($query)
    {
        return $query->where('type', AccountType::Bank);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /** Current balance = opening_balance + all IN transactions - all OUT transactions */
    protected function currentBalance(): Attribute
    {
        return Attribute::make(
            get: function () {
                $in  = $this->transactions()->where('direction', 'in')->sum('net_amount');
                $out = $this->transactions()->where('direction', 'out')->sum('net_amount');

                return $this->opening_balance + $in - $out;
            },
        );
    }
}
