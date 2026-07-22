<?php

namespace App\Policies;

use App\Enums\AssessmentStatus;
use App\Models\Beneficiary;
use App\Models\BeneficiaryAssessment;
use App\Models\User;

class BeneficiaryAssessmentPolicy
{
    public function viewAny(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('view_beneficiaries')
            && (new BeneficiaryPolicy)->viewSensitiveDetails($user, $beneficiary);
    }

    public function view(User $user, BeneficiaryAssessment $assessment): bool
    {
        return $this->viewAny($user, $assessment->beneficiary);
    }

    public function create(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('create_beneficiary_assessments')
            && $user->can('view_beneficiaries');
    }

    public function update(User $user, BeneficiaryAssessment $assessment): bool
    {
        if ($assessment->status !== AssessmentStatus::Pending) {
            return $user->can('review_beneficiary_assessments');
        }

        return $user->can('create_beneficiary_assessments')
            || $user->can('review_beneficiary_assessments');
    }

    public function approve(User $user, BeneficiaryAssessment $assessment): bool
    {
        return $user->can('approve_beneficiary_assessments')
            && $assessment->status === AssessmentStatus::Pending;
    }

    public function delete(User $user, BeneficiaryAssessment $assessment): bool
    {
        return $user->can('delete_beneficiaries')
            && $assessment->status === AssessmentStatus::Pending;
    }
}
