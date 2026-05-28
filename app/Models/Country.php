<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'flag'      => 'boolean',
            'is_active' => 'boolean',
            'latitude'  => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
