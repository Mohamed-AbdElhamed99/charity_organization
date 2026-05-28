<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeneficiaryFamily extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'monthly_income' => 'decimal:2',
            'monthly_rent'   => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(BeneficiaryFamilyMember::class, 'family_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BeneficiaryFamilyMember::class, 'family_id')
            ->where('subtype', 'child');
    }

    public function adults(): HasMany
    {
        return $this->hasMany(BeneficiaryFamilyMember::class, 'family_id')
            ->where('subtype', 'adult');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeWithMembers($query)
    {
        return $query->with('members');
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    protected function actualMembersCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->members()->count(),
        );
    }
}