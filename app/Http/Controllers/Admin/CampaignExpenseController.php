<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\CampaignExpenseServiceInterface;
use App\DTOs\CreateCampaignExpenseDTO;
use App\DTOs\UpdateCampaignExpenseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CampaignExpense\StoreCampaignExpenseRequest;
use App\Http\Requests\Admin\CampaignExpense\UpdateCampaignExpenseRequest;
use App\Http\Resources\Admin\CampaignExpense\CampaignExpenseResource;
use App\Models\BankAccount;
use App\Models\Campaign;
use App\Models\CampaignExpense;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignExpenseController extends Controller
{
    public function __construct(
        private readonly CampaignExpenseServiceInterface $campaignExpenseService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'date_from', 'date_to', 'page', 'per_page']);
        $paginator = $this->campaignExpenseService->getPaginatedExpenses($filters);

        $expenses = $paginator->toArray();
        $expenses['data'] = CampaignExpenseResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/campaign-expenses/campaign-expenses-index', [
            'expenses' => $expenses,
            'campaigns' => Campaign::query()->orderBy('title_en')->get(['id', 'title_ar', 'title_en']),
            'items' => Item::query()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_ar', 'name_en']),
            'accounts' => BankAccount::query()->active()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'search' => $filters,
        ]);
    }

    public function campaignIndex(Request $request, Campaign $campaign): Response
    {
        $filters = $request->only(['query', 'date_from', 'date_to', 'page', 'per_page']);
        $paginator = $this->campaignExpenseService->getPaginatedExpenses($filters, $campaign->id);

        $expenses = $paginator->toArray();
        $expenses['data'] = CampaignExpenseResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/campaigns/expenses/campaign-expenses-index', [
            'campaign' => ['id' => $campaign->id, 'title_en' => $campaign->title_en, 'title_ar' => $campaign->title_ar],
            'expenses' => $expenses,
            'items' => Item::query()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_ar', 'name_en']),
            'accounts' => BankAccount::query()->active()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'search' => $filters,
        ]);
    }

    public function store(StoreCampaignExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->campaignExpenseService->createExpense(new CreateCampaignExpenseDTO(
            campaignId: $validated['campaign_id'],
            accountId: $validated['account_id'],
            itemId: $validated['item_id'],
            itemPrice: (float) $validated['item_price'],
            quantity: (float) $validated['quantity'],
            expenseDate: $validated['expense_date'],
            responsibleUserId: $validated['responsible_user_id'],
            description: $validated['description'] ?? null,
            notes: $validated['notes'] ?? null,
            paymentMethodId: $validated['payment_method_id'] ?? null,
            referenceNumber: $validated['reference_number'] ?? null,
            originalCurrencyId: isset($validated['original_currency_id']) ? (int) $validated['original_currency_id'] : null,
            originalAmount: isset($validated['original_amount']) ? (float) $validated['original_amount'] : null,
            exchangeRate: isset($validated['exchange_rate']) ? (float) $validated['exchange_rate'] : null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign expense recorded successfully.')]);

        return back();
    }

    public function update(UpdateCampaignExpenseRequest $request, CampaignExpense $expense): RedirectResponse
    {
        $validated = $request->validated();

        $this->campaignExpenseService->updateExpense($expense, new UpdateCampaignExpenseDTO(
            notes: $validated['notes'] ?? null,
            residualQuantity: (float) $validated['residual_quantity'],
            residualAmount: (float) $validated['residual_amount'],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign expense updated successfully.')]);

        return back();
    }
}
