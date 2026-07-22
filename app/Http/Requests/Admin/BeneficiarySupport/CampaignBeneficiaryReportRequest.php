<?php

namespace App\Http\Requests\Admin\BeneficiarySupport;

use App\Enums\BeneficiaryType;
use App\Enums\SupportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignBeneficiaryReportRequest extends FormRequest
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
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'beneficiary_type' => ['nullable', Rule::enum(BeneficiaryType::class)],
            'aid_item_id' => ['nullable', 'integer', Rule::exists('aid_items', 'id')],
            'status' => ['nullable', Rule::enum(SupportStatus::class)],
            'query' => ['nullable', 'string', 'max:255'],
            'cost_min' => ['nullable', 'integer', 'min:0'],
            'cost_max' => ['nullable', 'integer', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'format' => ['nullable', 'string', Rule::in(['csv', 'xlsx'])],
        ];
    }
}
