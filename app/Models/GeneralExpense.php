<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneralExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'is_recurring' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function transactions(): MorphToMany
    {
        return $this->morphToMany(Transaction::class, 'transactionable');
    }

    /**
     * Primary linked ledger transaction (first morph relation).
     */
    protected function transaction(): Attribute
    {
        return Attribute::get(function (): ?Transaction {
            if ($this->relationLoaded('transactions')) {
                return $this->transactions->first();
            }

            return $this->transactions()->first();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GeneralExpenseCategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeOneTime($query)
    {
        return $query->where('is_recurring', false);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeInDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('expense_date', [$from, $to]);
    }
}
