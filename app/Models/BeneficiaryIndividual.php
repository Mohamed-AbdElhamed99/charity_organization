<?php

namespace App\Models;

use App\Enums\IndividualSubtype;
use App\Enums\UserGender;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryIndividual extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'subtype'              => IndividualSubtype::class,
            'gender'               => UserGender::class,
            'birthdate'            => 'date',
            'date_of_father_death' => 'date',
            'monthly_income'       => 'decimal:2',
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

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeChildren($query)
    {
        return $query->where('subtype', IndividualSubtype::Child);
    }

    public function scopeAdults($query)
    {
        return $query->where('subtype', IndividualSubtype::Adult);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
            ]))),
        );
    }

    /** Age calculated from birthdate */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->birthdate?->age,
        );
    }

    public function getIsChildAttribute(): bool
    {
        return $this->subtype === IndividualSubtype::Child;
    }

    protected $appends = ['full_name', 'age'];
}