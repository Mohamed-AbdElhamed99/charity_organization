<?php

namespace App\Http\Requests\Admin\Campaign;

use App\Enums\CampaignRecurrence;
use App\Enums\CampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_campaigns') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $campaignId = $this->route('campaign')?->id;

        return [
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('campaigns', 'slug')->ignore($campaignId)],
            'category_id' => ['nullable', 'integer', Rule::exists('campaign_categories', 'id')],
            'excerpt_ar' => ['nullable', 'string'],
            'excerpt_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'address' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'budget' => ['required', 'numeric', 'min:0'],
            'donation_target' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(CampaignStatus::class)],
            'is_public' => ['required', 'boolean'],
            'open_donation_form' => ['required', 'boolean'],
            'is_repeated' => ['required', Rule::enum(CampaignRecurrence::class)],
            'repeat_until' => ['nullable', 'date'],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_ar' => ['nullable', 'string'],
            'meta_description_en' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4'],
            'removed_gallery_ids' => ['nullable', 'array'],
            'removed_gallery_ids.*' => ['integer'],
        ];
    }
}
