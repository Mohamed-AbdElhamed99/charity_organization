<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\NewsCategoryServiceInterface;
use App\DTOs\CreateNewsCategoryDTO;
use App\DTOs\UpdateNewsCategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsCategory\BulkDestroyNewsCategoryRequest;
use App\Http\Requests\Admin\NewsCategory\RestoreNewsCategoryRequest;
use App\Http\Requests\Admin\NewsCategory\StoreNewsCategoryRequest;
use App\Http\Requests\Admin\NewsCategory\UpdateNewsCategoryRequest;
use App\Http\Resources\Admin\NewsCategory\NewsCategoryResource;
use App\Models\NewsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsCategoryController extends Controller
{
    public function __construct(
        private readonly NewsCategoryServiceInterface $newsCategoryService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'status', 'page', 'per_page']);
        $paginator = $this->newsCategoryService->getPaginatedNewsCategories($filters);

        $newsCategories = $paginator->toArray();
        $newsCategories['data'] = NewsCategoryResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/news-categories/news-categories-index', [
            'newsCategories' => $newsCategories,
            'search' => $filters,
        ]);
    }

    public function store(StoreNewsCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->newsCategoryService->createNewsCategory(new CreateNewsCategoryDTO(
            nameAr: $validated['name_ar'],
            nameEn: $validated['name_en'],
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News category created successfully.')]);

        return back();
    }

    public function update(UpdateNewsCategoryRequest $request, NewsCategory $newsCategory): RedirectResponse
    {
        $validated = $request->validated();

        $this->newsCategoryService->updateNewsCategory($newsCategory, new UpdateNewsCategoryDTO(
            nameAr: $validated['name_ar'],
            nameEn: $validated['name_en'],
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News category updated successfully.')]);

        return back();
    }

    public function destroy(NewsCategory $newsCategory): RedirectResponse
    {
        $this->newsCategoryService->deleteNewsCategory($newsCategory);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News category deleted successfully.')]);

        return back();
    }

    public function bulkDestroy(BulkDestroyNewsCategoryRequest $request): RedirectResponse
    {
        $this->newsCategoryService->bulkDelete($request->validated('ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News categories deleted successfully.')]);

        return back();
    }

    public function restore(RestoreNewsCategoryRequest $request, int|string $id): RedirectResponse
    {
        $this->newsCategoryService->restoreNewsCategory($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News category restored successfully.')]);

        return back();
    }
}
