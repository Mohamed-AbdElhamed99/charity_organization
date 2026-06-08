<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\CampaignServiceInterface;
use App\DTOs\CreateCampaignDTO;
use App\DTOs\UpdateCampaignDTO;
use App\Enums\CampaignRecurrence;
use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Campaign\StoreCampaignRequest;
use App\Http\Requests\Admin\Campaign\UpdateCampaignRequest;
use App\Http\Resources\Admin\Campaign\CampaignResource;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignServiceInterface $campaignService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'category', 'status', 'sort', 'direction', 'page', 'per_page']);
        $paginator = $this->campaignService->getPaginatedCampaigns($filters);

        $campaigns = $paginator->toArray();
        $campaigns['data'] = CampaignResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/campaigns/campaigns-index', [
            'campaigns' => $campaigns,
            'categories' => CampaignCategory::query()
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get(['id', 'name_ar', 'name_en']),
            'search' => $filters,
        ]);
    }

    public function show(Campaign $campaign): Response
    {
        abort_unless(request()->user()?->can('view_campaigns'), 403);

        $campaign->load(['category', 'media'])->loadCount(['expenses', 'donations']);

        return Inertia::render('admin/campaigns/campaigns-show', [
            'campaign' => (new CampaignResource($campaign))->resolve(),
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $slug = $this->resolveUniqueSlug($validated['slug'] ?? null, $validated['title_en']);

        $this->campaignService->createCampaign(new CreateCampaignDTO(
            slug: $slug,
            titleAr: $validated['title_ar'],
            titleEn: $validated['title_en'],
            categoryId: $validated['category_id'] ?? null,
            excerptAr: $validated['excerpt_ar'] ?? null,
            excerptEn: $validated['excerpt_en'] ?? null,
            descriptionAr: $validated['description_ar'] ?? null,
            descriptionEn: $validated['description_en'] ?? null,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
            address: $validated['address'] ?? null,
            countryId: $validated['country_id'] ?? null,
            stateId: $validated['state_id'] ?? null,
            lat: isset($validated['lat']) ? (float) $validated['lat'] : null,
            lng: isset($validated['lng']) ? (float) $validated['lng'] : null,
            budget: (float) $validated['budget'],
            donationTarget: isset($validated['donation_target']) ? (float) $validated['donation_target'] : null,
            status: CampaignStatus::from($validated['status']),
            isPublic: (bool) $validated['is_public'],
            openDonationForm: (bool) $validated['open_donation_form'],
            isRepeated: CampaignRecurrence::from($validated['is_repeated']),
            repeatUntil: $validated['repeat_until'] ?? null,
            metaTitleAr: $validated['meta_title_ar'] ?? null,
            metaTitleEn: $validated['meta_title_en'] ?? null,
            metaDescriptionAr: $validated['meta_description_ar'] ?? null,
            metaDescriptionEn: $validated['meta_description_en'] ?? null,
            createdBy: Auth::id(),
            cover: $request->file('cover'),
            gallery: $request->file('gallery') ?? [],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign created successfully.')]);

        return back();
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validated();

        $this->campaignService->updateCampaign($campaign, new UpdateCampaignDTO(
            slug: $validated['slug'],
            titleAr: $validated['title_ar'],
            titleEn: $validated['title_en'],
            categoryId: $validated['category_id'] ?? null,
            excerptAr: $validated['excerpt_ar'] ?? null,
            excerptEn: $validated['excerpt_en'] ?? null,
            descriptionAr: $validated['description_ar'] ?? null,
            descriptionEn: $validated['description_en'] ?? null,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
            address: $validated['address'] ?? null,
            countryId: $validated['country_id'] ?? null,
            stateId: $validated['state_id'] ?? null,
            lat: isset($validated['lat']) ? (float) $validated['lat'] : null,
            lng: isset($validated['lng']) ? (float) $validated['lng'] : null,
            budget: (float) $validated['budget'],
            donationTarget: isset($validated['donation_target']) ? (float) $validated['donation_target'] : null,
            status: CampaignStatus::from($validated['status']),
            isPublic: (bool) $validated['is_public'],
            openDonationForm: (bool) $validated['open_donation_form'],
            isRepeated: CampaignRecurrence::from($validated['is_repeated']),
            repeatUntil: $validated['repeat_until'] ?? null,
            metaTitleAr: $validated['meta_title_ar'] ?? null,
            metaTitleEn: $validated['meta_title_en'] ?? null,
            metaDescriptionAr: $validated['meta_description_ar'] ?? null,
            metaDescriptionEn: $validated['meta_description_en'] ?? null,
            cover: $request->file('cover'),
            gallery: $request->file('gallery'),
            removedGalleryIds: $validated['removed_gallery_ids'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign updated successfully.')]);

        return back();
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        abort_unless(request()->user()?->can('delete_campaigns'), 403);

        try {
            $this->campaignService->deleteCampaign($campaign);
        } catch (DomainException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign deleted successfully.')]);

        return back();
    }

    private function resolveUniqueSlug(?string $slug, string $titleEn): string
    {
        $slug = $slug ?: Str::slug($titleEn);
        $baseSlug = $slug;
        $counter = 1;

        while (Campaign::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
