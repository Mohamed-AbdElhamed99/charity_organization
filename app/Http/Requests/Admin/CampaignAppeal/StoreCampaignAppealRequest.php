<?php

namespace App\Http\Requests\Admin\CampaignAppeal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity_options' => $this->normalizeQuantityOptions($this->input('quantity_options')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('campaign_appeals', 'slug')],
            'subtitle_en' => ['nullable', 'string', 'max:500'],
            'subtitle_ar' => ['nullable', 'string', 'max:500'],
            'unit_amount' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'unit_label_en' => ['required', 'string', 'max:50'],
            'unit_label_ar' => ['required', 'string', 'max:50'],
            'unit_label_plural_en' => ['required', 'string', 'max:50'],
            'unit_label_plural_ar' => ['required', 'string', 'max:50'],
            'quantity_options' => ['required', 'array', 'min:1', 'max:6'],
            'quantity_options.*' => ['integer', 'min:1', 'distinct'],
            'allow_custom_amount' => ['required', 'boolean'],
            'cta_label_en' => ['required', 'string', 'max:120'],
            'cta_label_ar' => ['required', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'hero' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /**
     * @return list<int>
     */
    private function normalizeQuantityOptions(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map(fn ($item) => (int) $item, $value));
        }

        if (! is_string($value)) {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', trim($value)) ?: [];

        return array_values(array_filter(
            array_map(fn ($part) => (int) $part, $parts),
            fn (int $quantity) => $quantity > 0,
        ));
    }
}
