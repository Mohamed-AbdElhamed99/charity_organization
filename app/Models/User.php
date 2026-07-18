<?php

namespace App\Models;

use App\Enums\UserGender;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'password_set_at',
    'phone', 'status', 'national_id', 'job', 'birthdate',
    'bio', 'social_links', 'gender',
    'address', 'country_id', 'state_id',
    'provider', 'provider_id', 'provider_token', 'provider_refresh_token',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasMedia, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, MustVerifyEmailTrait, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    // ─── Casts ───────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_set_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'birthdate' => 'date',
            'social_links' => 'array',
            'gender' => UserGender::class,
            'status' => UserStatus::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('avatars')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function donorProfile(): HasOne
    {
        return $this->hasOne(DonorProfile::class);
    }

    public function donorPaymentMethods(): HasMany
    {
        return $this->hasMany(DonorPaymentMethod::class);
    }

    /** Recurring donation subscriptions started by this user as a donor */
    public function donationSubscriptions(): HasMany
    {
        return $this->hasMany(DonationSubscription::class, 'donor_id');
    }

    /** Campaigns this user created */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    /** Beneficiaries this user registered */
    public function registeredBeneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class, 'created_by');
    }

    /** Beneficiary access grants for this user (which beneficiary files they can read) */
    public function beneficiaryAccess(): HasMany
    {
        return $this->hasMany(BeneficiaryUserAccess::class);
    }

    /** Access grants this user has issued (as super admin) */
    public function grantedBeneficiaryAccess(): HasMany
    {
        return $this->hasMany(BeneficiaryUserAccess::class, 'granted_by');
    }

    /** Assessments this user conducted as field worker */
    public function conductedAssessments(): HasMany
    {
        return $this->hasMany(BeneficiaryAssessment::class, 'assessed_by');
    }

    /** Assessments this user reviewed/approved */
    public function reviewedAssessments(): HasMany
    {
        return $this->hasMany(BeneficiaryAssessment::class, 'reviewed_by');
    }

    /** Transactions this user recorded */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    /** Campaign expenses this user is responsible for */
    public function campaignExpenses(): HasMany
    {
        return $this->hasMany(CampaignExpense::class, 'responsible_user_id');
    }

    /** Donations made by this user as a donor */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'donor_id');
    }

    /** News articles created by this user */
    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /** Filter users who are donors (have the donor role) */
    public function scopeDonors($query)
    {
        return $query->role('donor');
    }

    /** Filter users who are staff (not donors) */
    public function scopeStaff($query)
    {
        return $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'donor'));
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getIsDonorAttribute(): bool
    {
        return $this->hasRole('donor');
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('super_admin');
    }

    /** Whether this donor has ever set a real password (false for guest-checkout-created accounts). */
    public function getHasUsablePasswordAttribute(): bool
    {
        return $this->password_set_at !== null;
    }
}
