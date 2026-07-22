<?php

namespace App\Services;

use App\Contracts\Services\AssessmentServiceInterface;
use App\Enums\AssessmentStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiaryAssessment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssessmentService implements AssessmentServiceInterface
{
    public function createAssessment(Beneficiary $beneficiary, array $data, User $assessor): BeneficiaryAssessment
    {
        return DB::transaction(function () use ($beneficiary, $data, $assessor) {
            return $beneficiary->assessments()->create([
                'assessed_by' => $assessor->id,
                'assessment_date' => $data['assessment_date'],
                'purpose' => $data['purpose'] ?? null,
                'housing_details' => $data['housing_details'] ?? null,
                'economic_details' => $data['economic_details'] ?? null,
                'health_details' => $data['health_details'] ?? null,
                'family_details' => $data['family_details'] ?? null,
                'researcher_opinion' => $data['researcher_opinion'] ?? null,
                'recommended_aid_amount' => isset($data['recommended_aid_amount'])
                    ? round((float) $data['recommended_aid_amount'], 2)
                    : null,
                'status' => AssessmentStatus::Pending,
            ])->load(['assessor', 'reviewer']);
        });
    }

    public function updateAssessment(BeneficiaryAssessment $assessment, array $data, User $user): BeneficiaryAssessment
    {
        return DB::transaction(function () use ($assessment, $data, $user) {
            $payload = [
                'assessment_date' => $data['assessment_date'] ?? $assessment->assessment_date,
                'purpose' => array_key_exists('purpose', $data) ? $data['purpose'] : $assessment->purpose,
                'housing_details' => array_key_exists('housing_details', $data) ? $data['housing_details'] : $assessment->housing_details,
                'economic_details' => array_key_exists('economic_details', $data) ? $data['economic_details'] : $assessment->economic_details,
                'health_details' => array_key_exists('health_details', $data) ? $data['health_details'] : $assessment->health_details,
                'family_details' => array_key_exists('family_details', $data) ? $data['family_details'] : $assessment->family_details,
                'researcher_opinion' => array_key_exists('researcher_opinion', $data) ? $data['researcher_opinion'] : $assessment->researcher_opinion,
                'recommended_aid_amount' => array_key_exists('recommended_aid_amount', $data)
                    ? ($data['recommended_aid_amount'] !== null ? round((float) $data['recommended_aid_amount'], 2) : null)
                    : $assessment->recommended_aid_amount,
            ];

            if ($user->can('review_beneficiary_assessments') && isset($data['status'])) {
                $status = AssessmentStatus::from($data['status']);

                if ($status === AssessmentStatus::Approved) {
                    return $this->approveAssessment($assessment, $user);
                }

                if ($status === AssessmentStatus::Rejected) {
                    return $this->rejectAssessment(
                        $assessment,
                        $user,
                        (string) ($data['rejection_reason'] ?? '')
                    );
                }
            }

            $assessment->update($payload);

            return $assessment->fresh(['assessor', 'reviewer']);
        });
    }

    public function approveAssessment(BeneficiaryAssessment $assessment, User $reviewer): BeneficiaryAssessment
    {
        $assessment->approve($reviewer);

        return $assessment->fresh(['assessor', 'reviewer', 'beneficiary']);
    }

    public function rejectAssessment(BeneficiaryAssessment $assessment, User $reviewer, string $reason): BeneficiaryAssessment
    {
        $assessment->reject($reviewer, $reason);

        return $assessment->fresh(['assessor', 'reviewer', 'beneficiary']);
    }
}
