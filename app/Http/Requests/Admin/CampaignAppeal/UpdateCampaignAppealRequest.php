<?php

namespace App\Http\Requests\Admin\CampaignAppeal;

use Illuminate\Validation\Rule;

class UpdateCampaignAppealRequest extends StoreCampaignAppealRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $appealId = $this->route('appeal')?->id;

        return [
            ...parent::rules(),
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('campaign_appeals', 'slug')->ignore($appealId),
            ],
        ];
    }
}
