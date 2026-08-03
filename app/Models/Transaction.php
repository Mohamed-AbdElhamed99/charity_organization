<?php

namespace App\Models;

use App\Enums\TransactionDirection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

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
            'direction' => TransactionDirection::class,
            'transaction_date' => 'date',
            'gross_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:8',
            'currency_equivalents' => 'array',
            'running_balance' => 'decimal:2',
            'is_reconciled' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'account_id');
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function originalCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'original_currency_id');
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

    public function transfer(): HasOne
    {
        return $this->hasOne(Transfer::class);
    }

    public function bankExpense(): HasOne
    {
        return $this->hasOne(BankExpense::class);
    }

    public function beneficiaries(): MorphToMany
    {
        return $this->morphedByMany(Beneficiary::class, 'transactionable');
    }

    public function campaigns(): MorphToMany
    {
        return $this->morphedByMany(Campaign::class, 'transactionable');
    }

    public function meetings(): MorphToMany
    {
        return $this->morphedByMany(Meeting::class, 'transactionable');
    }

    public function generalExpenses(): MorphToMany
    {
        return $this->morphedByMany(GeneralExpense::class, 'transactionable');
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

    public function scopeOfType(Builder $query, TransactionType|string|int $type): Builder
    {
        if ($type instanceof TransactionType) {
            return $query->where('transaction_type_id', $type->id);
        }

        if (is_int($type) || ctype_digit((string) $type)) {
            return $query->where('transaction_type_id', (int) $type);
        }

        return $query->whereHas('transactionType', fn (Builder $builder) => $builder->where('code', $type));
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
     * Computed amounts for each stored currency equivalent rate.
     * Rate meaning: 1 unit of ledger currency = `equivalent` units of `currency_code`.
     *
     * @return Attribute<int, array<int, array{currency_code: string, equivalent: float, amount: float}>>
     */
    protected function currencyEquivalentAmounts(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $rows = $this->currency_equivalents;

                if (! is_array($rows) || $rows === []) {
                    return [];
                }

                $netAmount = (float) $this->net_amount;

                return collect($rows)
                    ->filter(fn ($row) => is_array($row)
                        && filled($row['currency_code'] ?? null)
                        && isset($row['equivalent'])
                        && is_numeric($row['equivalent']))
                    ->map(function (array $row) use ($netAmount): array {
                        $equivalent = round((float) $row['equivalent'], 8);

                        return [
                            'currency_code' => strtoupper((string) $row['currency_code']),
                            'equivalent' => $equivalent,
                            'amount' => round($netAmount * $equivalent, 2),
                        ];
                    })
                    ->values()
                    ->all();
            },
        );
    }

    /**
     * Dynamically load the detail record regardless of type.
     * Usage: $transaction->detail
     */
    protected function detail(): Attribute
    {
        return Attribute::make(
            get: function () {
                $code = $this->transactionType?->code
                    ?? ($this->relationLoaded('transactionType') ? null : $this->transactionType()->value('code'));

                return match ($code) {
                    TransactionType::CODE_DONATION => $this->donation,
                    TransactionType::CODE_CAMPAIGN_EXPENSE => $this->campaignExpense,
                    TransactionType::CODE_GENERAL_EXPENSE => $this->generalExpenses->first(),
                    TransactionType::CODE_TRANSFER => $this->transfer,
                    TransactionType::CODE_BANK_TRANSFER,
                    TransactionType::CODE_ADJUSTMENT => $this->bankExpense,
                    default => null,
                };
            },
        );
    }
}
