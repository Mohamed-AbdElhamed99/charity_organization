<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAppeal;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CampaignAppealService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Campaign $campaign, array $data, ?UploadedFile $hero = null): CampaignAppeal
    {
        $appeal = $campaign->appeals()->create($this->attributes($data));

        if ($hero) {
            $appeal->addMedia($hero)->toMediaCollection('hero');
        }

        return $appeal;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CampaignAppeal $appeal, array $data, ?UploadedFile $hero = null): CampaignAppeal
    {
        $appeal->update($this->attributes($data, $appeal));

        if ($hero) {
            $appeal->clearMediaCollection('hero');
            $appeal->addMedia($hero)->toMediaCollection('hero');
        }

        return $appeal;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?CampaignAppeal $ignore = null): array
    {
        return [
            'slug' => $this->uniqueSlug($data['slug'] ?? null, $data['title_en'], $ignore),
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'subtitle_en' => $data['subtitle_en'] ?? null,
            'subtitle_ar' => $data['subtitle_ar'] ?? null,
            'unit_amount_cents' => Money::decimalToCents($data['unit_amount']),
            'unit_label_en' => $data['unit_label_en'],
            'unit_label_ar' => $data['unit_label_ar'],
            'unit_label_plural_en' => $data['unit_label_plural_en'],
            'unit_label_plural_ar' => $data['unit_label_plural_ar'],
            'quantity_options' => array_values(array_map('intval', $data['quantity_options'])),
            'allow_custom_amount' => (bool) $data['allow_custom_amount'],
            'cta_label_en' => $data['cta_label_en'],
            'cta_label_ar' => $data['cta_label_ar'],
            'is_active' => (bool) $data['is_active'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function uniqueSlug(?string $slug, string $titleEn, ?CampaignAppeal $ignore = null): string
    {
        $slug = $slug ?: Str::slug($titleEn);
        $baseSlug = $slug !== '' ? $slug : 'appeal';
        $candidate = $baseSlug;
        $counter = 1;

        while (
            CampaignAppeal::query()
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
