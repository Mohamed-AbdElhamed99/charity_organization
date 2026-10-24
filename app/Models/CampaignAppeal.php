<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CampaignAppeal extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'allow_custom_amount' => true,
        'is_active' => true,
        'sort_order' => 0,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    protected function casts(): array
    {
        return [
            'unit_amount_cents' => 'integer',
            'quantity_options' => 'array',
            'allow_custom_amount' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'appeal_id');
    }

    /**
     * @param  Builder<CampaignAppeal>  $query
     * @return Builder<CampaignAppeal>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<CampaignAppeal>  $query
     * @return Builder<CampaignAppeal>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->title_ar ?? $this->title_en)
                : ($this->title_en ?? $this->title_ar),
        );
    }

    protected function subtitle(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->subtitle_ar ?? $this->subtitle_en)
                : ($this->subtitle_en ?? $this->subtitle_ar),
        );
    }

    protected function unitLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->unit_label_ar ?? $this->unit_label_en)
                : ($this->unit_label_en ?? $this->unit_label_ar),
        );
    }

    protected function unitLabelPlural(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->unit_label_plural_ar ?? $this->unit_label_plural_en)
                : ($this->unit_label_plural_en ?? $this->unit_label_plural_ar),
        );
    }

    protected function ctaLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->cta_label_ar ?? $this->cta_label_en)
                : ($this->cta_label_en ?? $this->cta_label_ar),
        );
    }

    protected $appends = ['title', 'subtitle', 'unit_label', 'unit_label_plural', 'cta_label'];

    public function isDonatable(): bool
    {
        $campaign = $this->campaign;

        return $this->is_active
            && $campaign !== null
            && $campaign->is_public
            && $campaign->open_donation_form
            && $campaign->status === CampaignStatus::Active;
    }

    /**
     * Confirm a donor's quantity or custom amount against this appeal.
     *
     * @return array{campaign_id: int, amount_cents: int, quantity: int|null, unit_amount_cents: int|null, unit_label: string|null}
     */
    public function resolvedGift(?int $quantity, int $submittedAmountCents): array
    {
        if (! $this->isDonatable()) {
            throw ValidationException::withMessages([
                'appeal_id' => __('This appeal is not accepting donations.'),
            ]);
        }

        if ($quantity !== null) {
            $options = array_map('intval', $this->quantity_options ?? []);

            if (! in_array($quantity, $options, true)) {
                throw ValidationException::withMessages([
                    'quantity' => __('Choose one of the listed quantities.'),
                ]);
            }

            $amountCents = $quantity * $this->unit_amount_cents;

            if ($submittedAmountCents !== $amountCents) {
                throw ValidationException::withMessages([
                    'amount' => __('The donation total does not match the selected quantity.'),
                ]);
            }

            return [
                'campaign_id' => $this->campaign_id,
                'amount_cents' => $amountCents,
                'quantity' => $quantity,
                'unit_amount_cents' => $this->unit_amount_cents,
                'unit_label' => $quantity === 1 ? $this->unit_label : $this->unit_label_plural,
            ];
        }

        if (! $this->allow_custom_amount) {
            throw ValidationException::withMessages([
                'quantity' => __('Choose a quantity for this appeal.'),
            ]);
        }

        $minimum = (int) config('donations.min_amount_cents', 100);

        if ($submittedAmountCents < $minimum) {
            throw ValidationException::withMessages([
                'amount' => __('The donation amount is below the minimum.'),
            ]);
        }

        return [
            'campaign_id' => $this->campaign_id,
            'amount_cents' => $submittedAmountCents,
            'quantity' => null,
            'unit_amount_cents' => null,
            'unit_label' => null,
        ];
    }
}
