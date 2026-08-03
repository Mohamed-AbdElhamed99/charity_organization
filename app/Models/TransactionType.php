<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionType extends Model
{
    use HasFactory, SoftDeletes;

    public const CODE_DONATION = 'donation';

    public const CODE_CAMPAIGN_EXPENSE = 'campaign_expense';

    public const CODE_GENERAL_EXPENSE = 'general_expense';

    public const CODE_TRANSFER = 'transfer';

    public const CODE_BANK_TRANSFER = 'bank_transfer';

    public const CODE_ADJUSTMENT = 'adjustment';

    /**
     * @return array<int, array{name: string, code: string, description: string|null, is_active: bool, is_system: bool}>
     */
    public static function systemDefinitions(): array
    {
        return [
            [
                'name' => 'Donation',
                'code' => self::CODE_DONATION,
                'description' => 'Money in from a donor',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Campaign Expense',
                'code' => self::CODE_CAMPAIGN_EXPENSE,
                'description' => 'Money out for a campaign',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'General Expense',
                'code' => self::CODE_GENERAL_EXPENSE,
                'description' => 'Money out for org operations',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Transfer',
                'code' => self::CODE_TRANSFER,
                'description' => 'Money sent to a person or vendor',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Bank Transfer',
                'code' => self::CODE_BANK_TRANSFER,
                'description' => 'Money moved between org accounts',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Adjustment',
                'code' => self::CODE_ADJUSTMENT,
                'description' => 'Manual correction entry',
                'is_active' => true,
                'is_system' => true,
            ],
        ];
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function idForCode(string $code): int
    {
        return (int) static::query()->where('code', $code)->firstOrFail()->id;
    }

    public static function findByCode(string $code): ?self
    {
        return static::query()->where('code', $code)->first();
    }

    public function isDonation(): bool
    {
        return $this->code === self::CODE_DONATION;
    }

    public function isCampaignExpense(): bool
    {
        return $this->code === self::CODE_CAMPAIGN_EXPENSE;
    }

    public function isGeneralExpense(): bool
    {
        return $this->code === self::CODE_GENERAL_EXPENSE;
    }

    public function isTransfer(): bool
    {
        return $this->code === self::CODE_TRANSFER;
    }

    public function isBankTransfer(): bool
    {
        return $this->code === self::CODE_BANK_TRANSFER;
    }

    public function isAdjustment(): bool
    {
        return $this->code === self::CODE_ADJUSTMENT;
    }

    public function isIncome(): bool
    {
        return $this->isDonation();
    }

    public function isExpense(): bool
    {
        return in_array($this->code, [
            self::CODE_CAMPAIGN_EXPENSE,
            self::CODE_GENERAL_EXPENSE,
            self::CODE_TRANSFER,
            self::CODE_BANK_TRANSFER,
        ], true);
    }
}
