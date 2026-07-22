<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\GeneralExpenseServiceInterface;
use App\DTOs\CreateGeneralExpenseDTO;
use App\DTOs\UpdateGeneralExpenseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GeneralExpense\DestroyGeneralExpenseRequest;
use App\Http\Requests\Admin\GeneralExpense\StoreGeneralExpenseRequest;
use App\Http\Requests\Admin\GeneralExpense\UpdateGeneralExpenseRequest;
use App\Http\Resources\Admin\GeneralExpense\GeneralExpenseResource;
use App\Models\BankAccount;
use App\Models\GeneralExpense;
use App\Models\GeneralExpenseCategory;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GeneralExpenseController extends Controller
{
    public function __construct(
        private readonly GeneralExpenseServiceInterface $generalExpenseService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'category_id', 'date_from', 'date_to', 'page', 'per_page']);
        $paginator = $this->generalExpenseService->getPaginatedExpenses($filters);

        $expenses = $paginator->toArray();
        $expenses['data'] = GeneralExpenseResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/general-expenses/general-expenses-index', [
            'expenses' => $expenses,
            'categories' => GeneralExpenseCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'accounts' => BankAccount::query()->active()->orderBy('name')->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'search' => $filters,
        ]);
    }

    public function store(StoreGeneralExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->generalExpenseService->createExpense(new CreateGeneralExpenseDTO(
            accountId: (int) $validated['account_id'],
            name: $validated['name'],
            amount: (float) $validated['amount'],
            expenseDate: $validated['expense_date'],
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            paymentMethodId: isset($validated['payment_method_id']) ? (int) $validated['payment_method_id'] : null,
            vendorName: $validated['vendor_name'] ?? null,
            isRecurring: (bool) $validated['is_recurring'],
            description: $validated['description'] ?? null,
            notes: $validated['notes'] ?? null,
            referenceNumber: $validated['reference_number'] ?? null,
            originalCurrencyId: isset($validated['original_currency_id']) ? (int) $validated['original_currency_id'] : null,
            originalAmount: isset($validated['original_amount']) ? (float) $validated['original_amount'] : null,
            exchangeRate: isset($validated['exchange_rate']) ? (float) $validated['exchange_rate'] : null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General expense recorded successfully.')]);

        return back();
    }

    public function update(UpdateGeneralExpenseRequest $request, GeneralExpense $generalExpense): RedirectResponse
    {
        $validated = $request->validated();

        $this->generalExpenseService->updateExpense($generalExpense, new UpdateGeneralExpenseDTO(
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            name: $validated['name'],
            vendorName: $validated['vendor_name'] ?? null,
            isRecurring: (bool) $validated['is_recurring'],
            notes: $validated['notes'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General expense updated successfully.')]);

        return back();
    }

    public function destroy(DestroyGeneralExpenseRequest $request, GeneralExpense $generalExpense): RedirectResponse
    {
        $this->generalExpenseService->reverseExpense($generalExpense, Auth::id());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General expense reversed successfully.')]);

        return back();
    }
}
