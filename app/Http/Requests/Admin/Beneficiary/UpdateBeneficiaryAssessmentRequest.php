<?php

namespace App\Http\Requests\Admin\Beneficiary;

use App\Enums\AssessmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBeneficiaryAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assessment = $this->route('assessment');

        return $assessment !== null
            && ($this->user()?->can('update', $assessment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assessment_date' => ['sometimes', 'date'],
            'purpose' => ['nullable', 'string'],
            'housing_details' => ['nullable', 'array'],
            'economic_details' => ['nullable', 'array'],
            'health_details' => ['nullable', 'array'],
            'family_details' => ['nullable', 'array'],
            'researcher_opinion' => ['nullable', 'string'],
            'recommended_aid_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::enum(AssessmentStatus::class)],
            'rejection_reason' => [
                Rule::requiredIf(fn () => $this->input('status') === AssessmentStatus::Rejected->value),
                'nullable',
                'string',
            ],
        ];
    }
}
