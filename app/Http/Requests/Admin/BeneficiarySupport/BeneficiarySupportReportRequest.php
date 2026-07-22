<?php

namespace App\Http\Requests\Admin\BeneficiarySupport;

use App\Enums\SupportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeneficiarySupportReportRequest extends FormRequest
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
            'campaign_id' => ['nullable', 'integer', Rule::exists('campaigns', 'id')],
            'aid_item_id' => ['nullable', 'integer', Rule::exists('aid_items', 'id')],
            'status' => ['nullable', Rule::enum(SupportStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'format' => ['nullable', 'string', Rule::in(['csv', 'xlsx'])],
        ];
    }
}
