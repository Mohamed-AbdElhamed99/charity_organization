<?php

namespace App\Models;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = ['id'];

    // ─── Media Collections ───────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        // Invoices, receipts, cheque scans
        $this->addMediaCollection('receipts')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    // ─── Casts ───────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'transaction_type'  => TransactionType::class,
            'direction'         => TransactionDirection::class,
            'transaction_date'  => 'date',
            'gross_amount'      => 'decimal:2',
            'fee_amount'        => 'decimal:2',
            'net_amount'        => 'decimal:2',
            'running_balance'   => 'decimal:2',
            'is_reconciled'     => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Detail table relationships (one-to-one, one will be non-null per record)
    public function donation(): HasOne
    {
        return $this->hasOne(Donation::class);
    }

    public function campaignExpense(): HasOne
    {
        return $this->hasOne(CampaignExpense::class);
    }

    public function generalExpense(): HasOne
    {
        return $this->hasOne(GeneralExpense::class);
    }

    public function transfer(): HasOne
    {
        return $this->hasOne(Transfer::class);
    }

    public function bankExpense(): HasOne
    {
        return $this->hasOne(BankExpense::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeIn($query)
    {
        return $query->where('direction', TransactionDirection::In);
    }

    public function scopeOut($query)
    {
        return $query->where('direction', TransactionDirection::Out);
    }

    public function scopeOfType($query, TransactionType $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeInDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    protected function isIncome(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->direction === TransactionDirection::In,
        );
    }

    protected function isExpense(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->direction === TransactionDirection::Out,
        );
    }

    /**
     * Dynamically load the detail record regardless of type.
     * Usage: $transaction->detail
     */
    protected function detail(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->transaction_type) {
                TransactionType::Donation        => $this->donation,
                TransactionType::CampaignExpense => $this->campaignExpense,
                TransactionType::GeneralExpense  => $this->generalExpense,
                TransactionType::Transfer        => $this->transfer,
                TransactionType::BankTransfer,
                TransactionType::Adjustment      => $this->bankExpense,
                default                          => null,
            },
        );
    }
}
