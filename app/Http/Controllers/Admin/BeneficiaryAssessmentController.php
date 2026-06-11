<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\AssessmentServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Beneficiary\StoreBeneficiaryAssessmentRequest;
use App\Http\Requests\Admin\Beneficiary\UpdateBeneficiaryAssessmentRequest;
use App\Models\Beneficiary;
use App\Models\BeneficiaryAssessment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BeneficiaryAssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentServiceInterface $assessmentService,
    ) {}

    public function store(StoreBeneficiaryAssessmentRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        $this->assessmentService->createAssessment(
            $beneficiary,
            $request->validated(),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Assessment created successfully.')]);

        return back();
    }

    public function update(
        UpdateBeneficiaryAssessmentRequest $request,
        Beneficiary $beneficiary,
        BeneficiaryAssessment $assessment,
    ): RedirectResponse {
        abort_unless($assessment->beneficiary_id === $beneficiary->id, 404);

        $this->assessmentService->updateAssessment(
            $assessment,
            $request->validated(),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Assessment updated successfully.')]);

        return back();
    }
}
