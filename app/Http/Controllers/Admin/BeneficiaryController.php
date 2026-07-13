<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\AssessmentServiceInterface;
use App\Contracts\Services\BeneficiaryServiceInterface;
use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Beneficiary\BulkDestroyBeneficiaryRequest;
use App\Http\Requests\Admin\Beneficiary\StoreBeneficiaryRequest;
use App\Http\Requests\Admin\Beneficiary\UpdateBeneficiaryRequest;
use App\Http\Requests\Admin\Beneficiary\UpdateBeneficiaryStatusRequest;
use App\Http\Resources\Admin\Beneficiary\BeneficiaryAssessmentResource;
use App\Http\Resources\Admin\Beneficiary\BeneficiaryListResource;
use App\Http\Resources\Admin\Beneficiary\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Models\BeneficiaryAssessment;
use App\Models\Country;
use App\Models\State;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BeneficiaryController extends Controller
{
    public function __construct(
        private readonly BeneficiaryServiceInterface $beneficiaryService,
        private readonly AssessmentServiceInterface $assessmentService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('viewAny', Beneficiary::class), 403);

        $filters = $request->only([
            'query',
            'type',
            'status',
            'country_id',
            'state_id',
            'sort',
            'direction',
            'page',
            'per_page',
        ]);
        $paginator = $this->beneficiaryService->getPaginatedBeneficiaries($filters);

        $beneficiaries = $paginator->toArray();
        $beneficiaries['data'] = BeneficiaryListResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/beneficiaries/beneficiaries-index', [
            'beneficiaries' => $beneficiaries,
            'search' => $filters,
            'typeOptions' => collect(BeneficiaryType::cases())->map(fn (BeneficiaryType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'statusOptions' => collect(BeneficiaryStatus::cases())->map(fn (BeneficiaryStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
            'geoOptions' => $this->geoOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->can('create', Beneficiary::class), 403);

        return Inertia::render('admin/beneficiaries/beneficiaries-create', [
            'typeOptions' => collect(BeneficiaryType::cases())->map(fn (BeneficiaryType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'geoOptions' => $this->geoOptions(),
        ]);
    }

    public function store(StoreBeneficiaryRequest $request): RedirectResponse
    {
        $beneficiary = $this->beneficiaryService->createBeneficiary(
            $request->validated(),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Beneficiary created successfully.')]);

        return redirect()->route('admin.beneficiaries.show', $beneficiary);
    }

    public function show(Request $request, Beneficiary $beneficiary): Response
    {
        abort_unless($request->user()?->can('view', $beneficiary), 403);

        $beneficiary->load([
            'individual.country',
            'individual.state',
            'family.country',
            'family.state',
            'family.members',
            'organization.country',
            'organization.state',
            'creator',
            'assessments' => fn ($query) => $query->with(['assessor', 'reviewer'])->latest('assessment_date'),
        ])->loadCount('campaigns');

        return Inertia::render('admin/beneficiaries/beneficiaries-show', [
            'beneficiary' => (new BeneficiaryResource($beneficiary))->resolve(),
            'assessments' => BeneficiaryAssessmentResource::collection($beneficiary->assessments)->resolve(),
            'can' => [
                'update' => $request->user()?->can('update', $beneficiary) ?? false,
                'delete' => $request->user()?->can('delete', $beneficiary) ?? false,
                'createAssessment' => $request->user()?->can('create', [BeneficiaryAssessment::class, $beneficiary]) ?? false,
                'viewSensitive' => $request->user()?->can('viewSensitiveDetails', $beneficiary) ?? false,
            ],
        ]);
    }

    public function edit(Request $request, Beneficiary $beneficiary): Response
    {
        abort_unless($request->user()?->can('update', $beneficiary), 403);

        $beneficiary->load([
            'individual.country',
            'individual.state',
            'family.country',
            'family.state',
            'family.members',
            'organization.country',
            'organization.state',
        ]);

        return Inertia::render('admin/beneficiaries/beneficiaries-edit', [
            'beneficiary' => (new BeneficiaryResource($beneficiary))->resolve(),
            'geoOptions' => $this->geoOptions(),
        ]);
    }

    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        try {
            $this->beneficiaryService->updateBeneficiary($beneficiary, $request->validated());
        } catch (DomainException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Beneficiary updated successfully.')]);

        return redirect()->route('admin.beneficiaries.show', $beneficiary);
    }

    public function destroy(Request $request, Beneficiary $beneficiary): RedirectResponse
    {
        abort_unless($request->user()?->can('delete', $beneficiary), 403);

        $this->beneficiaryService->deleteBeneficiary($beneficiary);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Beneficiary deleted successfully.')]);

        return redirect()->route('admin.beneficiaries.index');
    }

    public function bulkDestroy(BulkDestroyBeneficiaryRequest $request): RedirectResponse
    {
        $this->beneficiaryService->bulkDelete($request->validated('ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Beneficiaries deleted successfully.')]);

        return back();
    }

    public function updateStatus(UpdateBeneficiaryStatusRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        $this->beneficiaryService->updateStatus(
            $beneficiary,
            BeneficiaryStatus::from($request->validated('status')),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Beneficiary status updated.')]);

        return back();
    }

    /**
     * @return array{countries: list<array{id: int, name: string}>, states: list<array{id: int, country_id: int, name: string}>}
     */
    private function geoOptions(): array
    {
        return [
            'countries' => Country::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Country $country) => [
                    'id' => $country->id,
                    'name' => $country->name,
                ])
                ->values()
                ->all(),
            'states' => State::query()
                ->orderBy('name')
                ->get(['id', 'country_id', 'name'])
                ->map(fn (State $state) => [
                    'id' => $state->id,
                    'country_id' => $state->country_id,
                    'name' => $state->name,
                ])
                ->values()
                ->all(),
        ];
    }
}
