<?php

namespace App\Http\Requests\Admin\CampaignCategory;

use Illuminate\Foundation\Http\FormRequest;

class RestoreCampaignCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_campaign_categories') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
