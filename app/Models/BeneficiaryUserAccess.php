<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryUserAccess extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'beneficiary_user_access';

    protected function casts(): array
    {
        return [
            'allowed_fields' => 'array',
            'granted_at' => 'datetime',
            'expires_in_seconds' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /** Only non-expired grants */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_in_seconds')
                ->orWhereRaw(
                    'TIMESTAMPADD(SECOND, expires_in_seconds, granted_at) > NOW()'
                );
        });
    }

    /** Only expired grants */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_in_seconds')
            ->whereRaw('TIMESTAMPADD(SECOND, expires_in_seconds, granted_at) <= NOW()');
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /** Whether this grant has expired */
    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->expires_in_seconds === null) {
                    return false;
                }

                return now()->isAfter(
                    $this->granted_at->addSeconds($this->expires_in_seconds)
                );
            },
        );
    }

    /** Human-readable expiry: converts seconds to largest sensible unit */
    protected function expiresInHuman(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->expires_in_seconds === null) {
                    return 'Never';
                }

                $seconds = $this->expires_in_seconds;

                return match (true) {
                    $seconds >= 2_592_000 => round($seconds / 2_592_000).' month(s)',
                    $seconds >= 604_800 => round($seconds / 604_800).' week(s)',
                    $seconds >= 86_400 => round($seconds / 86_400).' day(s)',
                    $seconds >= 3_600 => round($seconds / 3_600).' hour(s)',
                    default => $seconds.' second(s)',
                };
            },
        );
    }

    /** Absolute datetime when this grant expires */
    protected function expiresAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_in_seconds !== null
                ? $this->granted_at->addSeconds($this->expires_in_seconds)
                : null,
        );
    }

    /** Check if a specific field is allowed by this grant */
    public function allows(string $field): bool
    {
        if ($this->isExpired) {
            return false;
        }

        return in_array($field, $this->allowed_fields ?? []);
    }

    protected $appends = ['is_expired', 'expires_in_human', 'expires_at'];
}
