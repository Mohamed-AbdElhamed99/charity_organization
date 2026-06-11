<?php

namespace App\Contracts\Services;

use App\Models\Beneficiary;
use App\Models\BeneficiaryAssessment;
use App\Models\User;

interface AssessmentServiceInterface
{
    public function createAssessment(Beneficiary $beneficiary, array $data, User $assessor): BeneficiaryAssessment;

    public function updateAssessment(BeneficiaryAssessment $assessment, array $data, User $user): BeneficiaryAssessment;

    public function approveAssessment(BeneficiaryAssessment $assessment, User $reviewer): BeneficiaryAssessment;

    public function rejectAssessment(BeneficiaryAssessment $assessment, User $reviewer, string $reason): BeneficiaryAssessment;
}
