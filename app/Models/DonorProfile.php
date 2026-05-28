<?php

namespace App\Models;

use App\Enums\DonorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonorProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    // ─── Casts ───────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'type' => DonorType::class,
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function scopeIndividuals($query)
    {
        return $query->where('type', DonorType::Individual);
    }

    public function scopeOrganizations($query)
    {
        return $query->where('type', DonorType::Organization);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /** Display name: org name for organizations, user name for individuals */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === DonorType::Organization && $this->organization_name) {
            return $this->organization_name;
        }

        return $this->user->name ?? '';
    }
}
