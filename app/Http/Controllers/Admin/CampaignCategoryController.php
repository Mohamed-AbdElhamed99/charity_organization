<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\CampaignCategoryServiceInterface;
use App\DTOs\CreateCampaignCategoryDTO;
use App\DTOs\UpdateCampaignCategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CampaignCategory\BulkDestroyCampaignCategoryRequest;
use App\Http\Requests\Admin\CampaignCategory\RestoreCampaignCategoryRequest;
use App\Http\Requests\Admin\CampaignCategory\StoreCampaignCategoryRequest;
use App\Http\Requests\Admin\CampaignCategory\UpdateCampaignCategoryRequest;
use App\Http\Resources\Admin\CampaignCategory\CampaignCategoryResource;
use App\Models\CampaignCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignCategoryController extends Controller
{
    public function __construct(
        private readonly CampaignCategoryServiceInterface $campaignCategoryService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'status', 'page', 'per_page']);
        $paginator = $this->campaignCategoryService->getPaginatedCampaignCategories($filters);

        $campaignCategories = $paginator->toArray();
        $campaignCategories['data'] = CampaignCategoryResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/campaign-categories/campaign-categories-index', [
            'campaignCategories' => $campaignCategories,
            'search' => $filters,
        ]);
    }

    public function store(StoreCampaignCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->campaignCategoryService->createCampaignCategory(new CreateCampaignCategoryDTO(
            nameAr: $validated['name_ar'],
            nameEn: $validated['name_en'],
            description: $validated['description'] ?? null,
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign category created successfully.')]);

        return back();
    }

    public function update(UpdateCampaignCategoryRequest $request, CampaignCategory $campaignCategory): RedirectResponse
    {
        $validated = $request->validated();

        $this->campaignCategoryService->updateCampaignCategory($campaignCategory, new UpdateCampaignCategoryDTO(
            nameAr: $validated['name_ar'],
            nameEn: $validated['name_en'],
            description: $validated['description'] ?? null,
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign category updated successfully.')]);

        return back();
    }

    public function destroy(CampaignCategory $campaignCategory): RedirectResponse
    {
        $this->campaignCategoryService->deleteCampaignCategory($campaignCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign category deleted successfully.')]);

        return back();
    }

    public function bulkDestroy(BulkDestroyCampaignCategoryRequest $request): RedirectResponse
    {
        $this->campaignCategoryService->bulkDelete($request->validated('ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign categories deleted successfully.')]);

        return back();
    }

    public function restore(RestoreCampaignCategoryRequest $request, int|string $id): RedirectResponse
    {
        $this->campaignCategoryService->restoreCampaignCategory($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign category restored successfully.')]);

        return back();
    }
}
