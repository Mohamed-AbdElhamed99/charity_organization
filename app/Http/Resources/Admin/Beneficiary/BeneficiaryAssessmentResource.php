<?php

namespace App\Http\Resources\Admin\Beneficiary;

use App\Models\Beneficiary;
use App\Models\BeneficiaryAssessment;
use App\Support\BeneficiarySensitiveFields;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BeneficiaryAssessment */
class BeneficiaryAssessmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $beneficiary = $this->beneficiary ?? $this->whenLoaded('beneficiary');
        $canViewSensitive = $beneficiary instanceof Beneficiary
            && $user !== null
            && $user->can('viewSensitiveDetails', $beneficiary);

        $mask = fn (mixed $value, string $field) => $canViewSensitive
            && ($user === null || BeneficiarySensitiveFields::userCanViewField($user, $beneficiary, $field))
            ? $value
            : BeneficiarySensitiveFields::mask($value);

        return [
            'id' => $this->id,
            'beneficiary_id' => $this->beneficiary_id,
            'assessed_by' => $this->assessed_by,
            'assessor' => $this->whenLoaded('assessor', fn () => [
                'id' => $this->assessor->id,
                'name' => $this->assessor->name,
            ]),
            'assessment_date' => $this->assessment_date?->toDateString(),
            'purpose' => $mask($this->purpose, 'purpose'),
            'housing_details' => $canViewSensitive ? $this->housing_details : null,
            'economic_details' => $canViewSensitive ? $this->economic_details : null,
            'health_details' => $canViewSensitive ? $this->health_details : null,
            'family_details' => $canViewSensitive ? $this->family_details : null,
            'researcher_opinion' => $mask($this->researcher_opinion, 'researcher_opinion'),
            'recommended_aid_amount' => $mask($this->recommended_aid_amount, 'recommended_aid_amount'),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'rejection_reason' => $mask($this->rejection_reason, 'rejection_reason'),
            'reviewed_by' => $this->reviewed_by,
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null),
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
