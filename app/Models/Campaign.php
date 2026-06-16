<?php

namespace App\Models;

use App\Enums\CampaignRecurrence;
use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Campaign extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $guarded = ['id'];

    // ─── Media Collections ───────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4']);
    }

    // ─── Casts ───────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'is_repeated' => CampaignRecurrence::class,
            'is_public' => 'boolean',
            'open_donation_form' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'repeat_until' => 'date',
            'budget' => 'decimal:2',
            'donation_target' => 'decimal:2',
            'collected_amount' => 'integer',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(CampaignCategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /** Beneficiaries linked to this campaign via the pivot */
    public function beneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'campaign_beneficiaries')
            ->withPivot(['aid_amount', 'aid_type', 'aid_description', 'aid_date'])
            ->withTimestamps()
            ->using(CampaignBeneficiary::class);
    }

    /** All expense transactions for this campaign */
    public function expenses(): HasMany
    {
        return $this->hasMany(CampaignExpense::class);
    }

    /** Operational support events delivered under this campaign */
    public function supports(): HasMany
    {
        return $this->hasMany(BeneficiarySupport::class);
    }

    /** Beneficiaries linked through operational support events */
    public function supportedBeneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'beneficiary_supports')
            ->withPivot(['id', 'supported_at', 'status', 'created_by', 'notes'])
            ->withTimestamps();
    }

    /** All donations designated to this campaign */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /** Transfers linked to this campaign */
    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', CampaignStatus::Active);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', CampaignStatus::Draft);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', CampaignStatus::Completed);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeWithOpenDonation($query)
    {
        return $query->where('open_donation_form', true)
            ->where('status', CampaignStatus::Active);
    }

    public function scopePublishable($query)
    {
        return $query->whereIn('status', [
            CampaignStatus::Active,
            CampaignStatus::Completed,
        ]);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    /** Locale-aware title */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->title_ar ?? $this->title_en)
                : ($this->title_en ?? $this->title_ar),
        );
    }

    /** Locale-aware excerpt */
    protected function excerpt(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->excerpt_ar ?? $this->excerpt_en)
                : ($this->excerpt_en ?? $this->excerpt_ar),
        );
    }

    /** Locale-aware description */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->description_ar ?? $this->description_en)
                : ($this->description_en ?? $this->description_ar),
        );
    }

    protected function categoryName(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->category?->name_ar ?? $this->category?->name_en)
                : ($this->category?->name_en ?? $this->category?->name_ar),
        );
    }

    protected function metaTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->meta_title_ar ?? $this->meta_title_en)
                : ($this->meta_title_en ?? $this->meta_title_ar),
        );
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->meta_description_ar ?? $this->meta_description_en)
                : ($this->meta_description_en ?? $this->meta_description_ar),
        );
    }

    /** Total donations received for this campaign */
    protected function totalDonated(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->donations()
                ->join('transactions', 'donations.transaction_id', '=', 'transactions.id')
                ->sum('transactions.net_amount'),
        );
    }

    /** Total expenses incurred for this campaign */
    protected function totalExpenses(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expenses()->sum('amount'),
        );
    }

    /** Remaining budget */
    protected function remainingBudget(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->budget - $this->totalExpenses,
        );
    }

    /** Progress percentage toward donation target (uses collected_amount cents vs target dollars) */
    protected function donationProgress(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->donation_target || (float) $this->donation_target == 0) {
                    return null;
                }

                $targetCents = (int) bcmul((string) $this->donation_target, '100', 0);
                if ($targetCents <= 0) {
                    return null;
                }

                $percent = ($this->collected_amount / $targetCents) * 100;

                return min(100, round($percent, 2));
            },
        );
    }

    protected function progressPercent(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->donation_progress,
        );
    }

    protected function supportedBeneficiariesCount(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->supports()
                ->distinct('beneficiary_id')
                ->count('beneficiary_id'),
        );
    }

    protected $appends = ['title', 'excerpt', 'description', 'category_name'];
}
