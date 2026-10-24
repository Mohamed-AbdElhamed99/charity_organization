<?php

namespace App\Http\Resources\Site;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignAppealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hero = $this->getFirstMediaUrl('hero');
        $campaign = $this->campaign;
        $campaignCover = $campaign?->getFirstMediaUrl('cover') ?: '';

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'gift_options' => $this->localizedGiftOptions(),
            'allow_custom_amount' => (bool) $this->allow_custom_amount,
            'cta_label' => $this->cta_label,
            'image' => $hero ?: $campaignCover,
            'campaign' => [
                'id' => $campaign?->id,
                'slug' => $campaign?->slug,
                'title' => $campaign?->title,
            ],
        ];
    }
}
