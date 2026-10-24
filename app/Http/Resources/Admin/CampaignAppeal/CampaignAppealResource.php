<?php

namespace App\Http\Resources\Admin\CampaignAppeal;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignAppealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'slug' => $this->slug,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'subtitle_en' => $this->subtitle_en,
            'subtitle_ar' => $this->subtitle_ar,
            'unit_amount' => Money::centsToDecimal($this->unit_amount_cents),
            'unit_amount_cents' => $this->unit_amount_cents,
            'unit_label_en' => $this->unit_label_en,
            'unit_label_ar' => $this->unit_label_ar,
            'unit_label_plural_en' => $this->unit_label_plural_en,
            'unit_label_plural_ar' => $this->unit_label_plural_ar,
            'quantity_options' => array_map('intval', $this->quantity_options ?? []),
            'quantity_options_text' => implode(', ', $this->quantity_options ?? []),
            'allow_custom_amount' => (bool) $this->allow_custom_amount,
            'cta_label_en' => $this->cta_label_en,
            'cta_label_ar' => $this->cta_label_ar,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'hero_url' => $this->getFirstMediaUrl('hero') ?: null,
            'share_url' => route('appeals.show', ['appeal' => $this->slug]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
