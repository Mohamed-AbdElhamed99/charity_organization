<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\GeneralExpenseCategoryServiceInterface;
use App\DTOs\CreateGeneralExpenseCategoryDTO;
use App\DTOs\UpdateGeneralExpenseCategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GeneralExpenseCategory\BulkDestroyGeneralExpenseCategoryRequest;
use App\Http\Requests\Admin\GeneralExpenseCategory\RestoreGeneralExpenseCategoryRequest;
use App\Http\Requests\Admin\GeneralExpenseCategory\StoreGeneralExpenseCategoryRequest;
use App\Http\Requests\Admin\GeneralExpenseCategory\UpdateGeneralExpenseCategoryRequest;
use App\Http\Resources\Admin\GeneralExpenseCategory\GeneralExpenseCategoryResource;
use App\Models\GeneralExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralExpenseCategoryController extends Controller
{
    public function __construct(
        private readonly GeneralExpenseCategoryServiceInterface $generalExpenseCategoryService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'status', 'page', 'per_page']);
        $paginator = $this->generalExpenseCategoryService->getPaginatedCategories($filters);

        $categories = $paginator->toArray();
        $categories['data'] = GeneralExpenseCategoryResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/general-expense-categories/general-expense-categories-index', [
            'categories' => $categories,
            'search' => $filters,
        ]);
    }

    public function store(StoreGeneralExpenseCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->generalExpenseCategoryService->createCategory(new CreateGeneralExpenseCategoryDTO(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General expense category created successfully.')]);

        return back();
    }

    public function update(UpdateGeneralExpenseCategoryRequest $request, GeneralExpenseCategory $generalExpenseCategory): RedirectResponse
    {
        $validated = $request->validated();

        $this->generalExpenseCategoryService->updateCategory($generalExpenseCategory, new UpdateGeneralExpenseCategoryDTO(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General expense category updated successfully.')]);

        return back();
    }

    public function destroy(GeneralExpenseCategory $generalExpenseCategory): RedirectResponse
    {
        $result = $this->generalExpenseCategoryService->deleteCategory($generalExpenseCategory);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result === 'deactivated'
                ? __('Category is in use and was deactivated instead of deleted.')
                : __('General expense category deleted successfully.'),
        ]);

        return back();
    }

    public function bulkDestroy(BulkDestroyGeneralExpenseCategoryRequest $request): RedirectResponse
    {
        $result = $this->generalExpenseCategoryService->bulkDelete($request->validated('ids'));

        $message = match (true) {
            $result['deleted'] > 0 && $result['deactivated'] > 0 => __(':deleted category(ies) deleted and :deactivated deactivated because they are in use.', [
                'deleted' => $result['deleted'],
                'deactivated' => $result['deactivated'],
            ]),
            $result['deactivated'] > 0 => __(':count category(ies) deactivated because they are in use.', [
                'count' => $result['deactivated'],
            ]),
            default => __('General expense categories deleted successfully.'),
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function restore(RestoreGeneralExpenseCategoryRequest $request, int|string $id): RedirectResponse
    {
        $this->generalExpenseCategoryService->restoreCategory($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General expense category restored successfully.')]);

        return back();
    }
}
