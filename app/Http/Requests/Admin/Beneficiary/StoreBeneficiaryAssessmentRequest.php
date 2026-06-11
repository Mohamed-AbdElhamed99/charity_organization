<?php

namespace App\Http\Requests\Admin\Beneficiary;

use App\Models\BeneficiaryAssessment;
use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $beneficiary = $this->route('beneficiary');

        return $beneficiary !== null
            && ($this->user()?->can('create', [BeneficiaryAssessment::class, $beneficiary]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assessment_date' => ['required', 'date'],
            'purpose' => ['nullable', 'string'],
            'housing_details' => ['nullable', 'array'],
            'economic_details' => ['nullable', 'array'],
            'health_details' => ['nullable', 'array'],
            'family_details' => ['nullable', 'array'],
            'researcher_opinion' => ['nullable', 'string'],
            'recommended_aid_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
