<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\DonorProfileServiceInterface;
use App\DTOs\CreateDonorProfileDTO;
use App\DTOs\UpdateDonorProfileDTO;
use App\Enums\DonorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DonorProfile\RestoreDonorProfileRequest;
use App\Http\Requests\Admin\DonorProfile\StoreDonorProfileRequest;
use App\Http\Requests\Admin\DonorProfile\UpdateDonorProfileRequest;
use App\Http\Resources\Admin\DonorProfile\DonorProfileListResource;
use App\Http\Resources\Admin\DonorProfile\DonorProfileResource;
use App\Models\Country;
use App\Models\DonorProfile;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DonorProfileController extends Controller
{
    public function __construct(
        private readonly DonorProfileServiceInterface $donorProfileService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view_donor_profiles'), 403);

        $filters = $request->only(['query', 'type', 'page', 'per_page']);
        $paginator = $this->donorProfileService->getPaginatedDonorProfiles($filters);

        $donorProfiles = $paginator->toArray();
        $donorProfiles['data'] = DonorProfileListResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/donor-profiles/donor-profiles-index', [
            'donorProfiles' => $donorProfiles,
            'availableUsers' => $this->donorProfileService->getAvailableDonorUsers(),
            'typeOptions' => collect(DonorType::cases())->map(fn (DonorType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'geoOptions' => $this->geoOptions(),
            'search' => $filters,
        ]);
    }

    public function show(DonorProfile $donorProfile): Response
    {
        abort_unless(request()->user()?->can('view_donor_profiles'), 403);

        $donorProfile->load(['user', 'country', 'state']);

        return Inertia::render('admin/donor-profiles/donor-profiles-show', [
            'donorProfile' => (new DonorProfileResource($donorProfile))->resolve(),
            'geoOptions' => $this->geoOptions(),
            'typeOptions' => collect(DonorType::cases())->map(fn (DonorType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
        ]);
    }

    public function store(StoreDonorProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $donorProfile = $this->donorProfileService->createDonorProfile(new CreateDonorProfileDTO(
            userId: (int) $validated['user_id'],
            type: $validated['type'],
            organizationName: $validated['organization_name'] ?? null,
            address: $validated['address'] ?? null,
            countryId: isset($validated['country_id']) ? (int) $validated['country_id'] : null,
            stateId: isset($validated['state_id']) ? (int) $validated['state_id'] : null,
            notes: $validated['notes'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Donor profile created successfully.')]);

        return redirect()->route('admin.donor-profiles.show', $donorProfile);
    }

    public function update(UpdateDonorProfileRequest $request, DonorProfile $donorProfile): RedirectResponse
    {
        $validated = $request->validated();

        $this->donorProfileService->updateDonorProfile($donorProfile, new UpdateDonorProfileDTO(
            type: $validated['type'],
            organizationName: $validated['organization_name'] ?? null,
            address: $validated['address'] ?? null,
            countryId: isset($validated['country_id']) ? (int) $validated['country_id'] : null,
            stateId: isset($validated['state_id']) ? (int) $validated['state_id'] : null,
            notes: $validated['notes'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Donor profile updated successfully.')]);

        return back();
    }

    public function destroy(DonorProfile $donorProfile): RedirectResponse
    {
        abort_unless(request()->user()?->can('delete_donor_profiles'), 403);

        $this->donorProfileService->deleteDonorProfile($donorProfile);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Donor profile deleted successfully.')]);

        return redirect()->route('admin.donor-profiles.index');
    }

    public function restore(RestoreDonorProfileRequest $request, int|string $id): RedirectResponse
    {
        $this->donorProfileService->restoreDonorProfile($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Donor profile restored successfully.')]);

        return back();
    }

    /**
     * @return array{countries: array<int, array{id: int, name: string}>, states: array<int, array{id: int, name: string, country_id: int}>}
     */
    private function geoOptions(): array
    {
        return [
            'countries' => Country::query()->orderBy('name')->get(['id', 'name'])->toArray(),
            'states' => State::query()->orderBy('name')->get(['id', 'name', 'country_id'])->toArray(),
        ];
    }
}
