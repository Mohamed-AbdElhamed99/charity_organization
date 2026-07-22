<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AidItem\StoreAidItemRequest;
use App\Http\Requests\Admin\AidItem\UpdateAidItemRequest;
use App\Models\AidItem;
use App\Services\AidItemService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AidItemController extends Controller
{
    public function __construct(
        private readonly AidItemService $aidItemService,
    ) {}

    public function index(): Response
    {
        $items = $this->aidItemService->paginate()
            ->through(fn (AidItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'default_unit_cost' => $item->default_unit_cost,
                'category' => $item->category,
                'is_active' => $item->is_active,
            ]);

        return Inertia::render('admin/aid-items/index', [
            'items' => $items,
        ]);
    }

    public function store(StoreAidItemRequest $request): RedirectResponse
    {
        $this->aidItemService->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Aid item created successfully.')]);

        return back();
    }

    public function update(UpdateAidItemRequest $request, AidItem $aidItem): RedirectResponse
    {
        $this->aidItemService->update($aidItem, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Aid item updated successfully.')]);

        return back();
    }
}
