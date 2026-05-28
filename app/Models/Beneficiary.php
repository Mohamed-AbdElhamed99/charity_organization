<?php

namespace App\Models;

use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Beneficiary extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = ['id'];

    // ─── Media Collections ───────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    // ─── Casts ───────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'type'   => BeneficiaryType::class,
            'status' => BeneficiaryStatus::class,
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Profile extension — individual */
    public function individual(): HasOne
    {
        return $this->hasOne(BeneficiaryIndividual::class);
    }

    /** Profile extension — family */
    public function family(): HasOne
    {
        return $this->hasOne(BeneficiaryFamily::class);
    }

    /** Profile extension — organization */
    public function organization(): HasOne
    {
        return $this->hasOne(BeneficiaryOrganization::class);
    }

    /** Social investigation/intake assessments */
    public function assessments(): HasMany
    {
        return $this->hasMany(BeneficiaryAssessment::class);
    }

    /** Latest assessment (for status checks) */
    public function latestAssessment(): HasOne
    {
        return $this->hasOne(BeneficiaryAssessment::class)->latestOfMany();
    }

    /** Access grants for this beneficiary */
    public function userAccess(): HasMany
    {
        return $this->hasMany(BeneficiaryUserAccess::class);
    }

    /** Campaigns this beneficiary received aid in */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_beneficiaries')
            ->withPivot(['aid_amount', 'aid_type', 'aid_description', 'aid_date'])
            ->withTimestamps()
            ->using(CampaignBeneficiary::class);
    }

    /** Transfers sent directly to this beneficiary */
    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', BeneficiaryStatus::Active);
    }

    public function scopePendingAssessment($query)
    {
        return $query->where('status', BeneficiaryStatus::PendingAssessment);
    }

    public function scopeOfType($query, BeneficiaryType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeIndividuals($query)
    {
        return $query->where('type', BeneficiaryType::Individual);
    }

    public function scopeFamilies($query)
    {
        return $query->where('type', BeneficiaryType::Family);
    }

    public function scopeOrganizations($query)
    {
        return $query->where('type', BeneficiaryType::Organization);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /**
     * Dynamically return the correct profile based on type.
     * Usage: $beneficiary->profile
     */
    protected function profile(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type) {
                BeneficiaryType::Individual   => $this->individual,
                BeneficiaryType::Family       => $this->family,
                BeneficiaryType::Organization => $this->organization,
                default                       => null,
            },
        );
    }

    /** Display name derived from the profile */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->type) {
                    BeneficiaryType::Individual   => trim(implode(' ', array_filter([
                        $this->individual?->first_name,
                        $this->individual?->last_name,
                    ]))),
                    BeneficiaryType::Family       => $this->family?->household_name,
                    BeneficiaryType::Organization => $this->organization?->name,
                    default                       => $this->code,
                };
            },
        );
    }

    /** Total aid amount across all campaigns */
    protected function totalAidReceived(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->campaigns()->sum('campaign_beneficiaries.aid_amount'),
        );
    }

    /**
     * Check whether a given user has access to a given field.
     * Respects expiry (expires_in_seconds from granted_at).
     */
    public function userCanAccessField(User $user, string $field): bool
    {
        $grant = $this->userAccess()
            ->where('user_id', $user->id)
            ->first();

        if (! $grant) {
            return false;
        }

        // Check expiry
        if ($grant->expires_in_seconds !== null) {
            $expiresAt = $grant->granted_at->addSeconds($grant->expires_in_seconds);
            if (now()->isAfter($expiresAt)) {
                return false;
            }
        }

        return in_array($field, $grant->allowed_fields ?? []);
    }

    protected $appends = ['display_name'];
}
