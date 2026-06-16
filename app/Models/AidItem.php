<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AidItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'unit' => 'array',
            'default_unit_cost' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function supportItems(): HasMany
    {
        return $this->hasMany(BeneficiarySupportItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function localizedName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolveLocalizedValue($this->name),
        );
    }

    protected function localizedUnit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolveLocalizedValue($this->unit),
        );
    }

    /**
     * @param  array<string, string>|null  $value
     */
    private function resolveLocalizedValue(?array $value): ?string
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        $locale = app()->getLocale();

        return $value[$locale] ?? $value['en'] ?? $value['ar'] ?? reset($value) ?: null;
    }
}
