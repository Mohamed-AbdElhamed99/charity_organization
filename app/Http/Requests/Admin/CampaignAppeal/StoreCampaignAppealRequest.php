<?php

namespace App\Http\Requests\Admin\CampaignAppeal;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minimumAmount = Money::centsToDecimal((int) config('donations.min_amount_cents', 100));

        return [
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('campaign_appeals', 'slug')],
            'subtitle_en' => ['nullable', 'string', 'max:500'],
            'subtitle_ar' => ['nullable', 'string', 'max:500'],
            'gift_options' => ['required', 'array', 'min:1', 'max:6'],
            'gift_options.*.name_en' => ['required', 'string', 'max:80'],
            'gift_options.*.name_ar' => ['required', 'string', 'max:80'],
            'gift_options.*.amount' => ['required', 'numeric', 'min:'.$minimumAmount, 'max:100000'],
            'allow_custom_amount' => ['required', 'boolean'],
            'cta_label_en' => ['required', 'string', 'max:120'],
            'cta_label_ar' => ['required', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'hero' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
