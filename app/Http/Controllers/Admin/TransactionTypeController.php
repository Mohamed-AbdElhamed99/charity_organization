<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\TransactionTypeServiceInterface;
use App\DTOs\CreateTransactionTypeDTO;
use App\DTOs\UpdateTransactionTypeDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransactionType\BulkDestroyTransactionTypeRequest;
use App\Http\Requests\Admin\TransactionType\RestoreTransactionTypeRequest;
use App\Http\Requests\Admin\TransactionType\StoreTransactionTypeRequest;
use App\Http\Requests\Admin\TransactionType\UpdateTransactionTypeRequest;
use App\Http\Resources\Admin\TransactionType\TransactionTypeResource;
use App\Models\TransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class TransactionTypeController extends Controller
{
    public function __construct(
        private readonly TransactionTypeServiceInterface $transactionTypeService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'status', 'page', 'per_page']);
        $paginator = $this->transactionTypeService->getPaginatedTransactionTypes($filters);

        $transactionTypes = $paginator->toArray();
        $transactionTypes['data'] = TransactionTypeResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/transaction-types/transaction-types-index', [
            'transactionTypes' => $transactionTypes,
            'search' => $filters,
        ]);
    }

    public function store(StoreTransactionTypeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $transactionType = $this->transactionTypeService->createTransactionType(new CreateTransactionTypeDTO(
            name: $validated['name'],
            code: $validated['code'],
            description: $validated['description'] ?? null,
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction type created successfully.')]);
        Inertia::flash('transactionType', (new TransactionTypeResource($transactionType))->resolve());

        return back();
    }

    public function update(UpdateTransactionTypeRequest $request, TransactionType $transactionType): RedirectResponse
    {
        $validated = $request->validated();

        $transactionType = $this->transactionTypeService->updateTransactionType($transactionType, new UpdateTransactionTypeDTO(
            name: $validated['name'],
            code: $validated['code'],
            description: $validated['description'] ?? null,
            isActive: (bool) $validated['is_active'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction type updated successfully.')]);
        Inertia::flash('transactionType', (new TransactionTypeResource($transactionType))->resolve());

        return back();
    }

    public function destroy(TransactionType $transactionType): RedirectResponse
    {
        try {
            $result = $this->transactionTypeService->deleteTransactionType($transactionType);
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __($exception->getMessage())]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result === 'deactivated'
                ? __('Transaction type is in use and was deactivated instead of deleted.')
                : __('Transaction type deleted successfully.'),
        ]);

        return back();
    }

    public function bulkDestroy(BulkDestroyTransactionTypeRequest $request): RedirectResponse
    {
        $result = $this->transactionTypeService->bulkDelete($request->validated('ids'));

        $message = match (true) {
            $result['deleted'] > 0 && $result['deactivated'] > 0 => __(':deleted transaction type(s) deleted and :deactivated deactivated because they are in use.', [
                'deleted' => $result['deleted'],
                'deactivated' => $result['deactivated'],
            ]),
            $result['deactivated'] > 0 => __(':count transaction type(s) deactivated because they are in use.', [
                'count' => $result['deactivated'],
            ]),
            $result['blocked'] > 0 && $result['deleted'] === 0 && $result['deactivated'] === 0 => __('System transaction types cannot be deleted.'),
            $result['blocked'] > 0 => __('Some system transaction types were skipped. Remaining types were processed.'),
            default => __('Transaction types deleted successfully.'),
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function restore(RestoreTransactionTypeRequest $request, int|string $id): RedirectResponse
    {
        $this->transactionTypeService->restoreTransactionType($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction type restored successfully.')]);

        return back();
    }
}
