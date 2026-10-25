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
            'gift_options' => 'array',
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

    protected function ctaLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->cta_label_ar ?? $this->cta_label_en)
                : ($this->cta_label_en ?? $this->cta_label_ar),
        );
    }

    protected $appends = ['title', 'subtitle', 'cta_label'];

    public function isDonatable(): bool
    {
        $campaign = $this->campaign;

        return $this->is_active
            && $campaign !== null
            && $campaign->is_public
            && $campaign->status === CampaignStatus::Active;
    }

    /**
     * Confirm a donor's named gift, or a custom amount, against this appeal.
     *
     * @return array{campaign_id: int, amount_cents: int, quantity: int|null, unit_amount_cents: int|null, unit_label: string|null}
     */
    public function resolvedGift(?int $giftOption, int $submittedAmountCents): array
    {
        if (! $this->isDonatable()) {
            throw ValidationException::withMessages([
                'appeal_id' => __('This appeal is not accepting donations.'),
            ]);
        }

        if ($giftOption !== null) {
            $options = array_values($this->gift_options ?? []);
            $option = $options[$giftOption] ?? null;

            if (! is_array($option)) {
                throw ValidationException::withMessages([
                    'gift_option' => __('Choose one of the listed gifts.'),
                ]);
            }

            $amountCents = (int) $option['amount_cents'];

            if ($submittedAmountCents !== $amountCents) {
                throw ValidationException::withMessages([
                    'amount' => __('The donation total does not match the selected gift.'),
                ]);
            }

            return [
                'campaign_id' => $this->campaign_id,
                'amount_cents' => $amountCents,
                'quantity' => null,
                'unit_amount_cents' => $amountCents,
                'unit_label' => $this->optionName($option),
            ];
        }

        if (! $this->allow_custom_amount) {
            throw ValidationException::withMessages([
                'gift_option' => __('Choose a gift for this appeal.'),
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

    /**
     * Localized options for the public give page.
     *
     * @return list<array{name: string, amount_cents: int}>
     */
    public function localizedGiftOptions(): array
    {
        return array_map(fn (array $option): array => [
            'name' => $this->optionName($option),
            'amount_cents' => (int) $option['amount_cents'],
        ], array_values($this->gift_options ?? []));
    }

    /**
     * @param  array<string, mixed>  $option
     */
    private function optionName(array $option): string
    {
        $english = (string) ($option['name_en'] ?? '');
        $arabic = (string) ($option['name_ar'] ?? '');

        if (app()->getLocale() === 'ar') {
            return $arabic !== '' ? $arabic : $english;
        }

        return $english !== '' ? $english : $arabic;
    }
}
