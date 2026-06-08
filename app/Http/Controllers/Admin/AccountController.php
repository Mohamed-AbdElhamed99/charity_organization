<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\AccountServiceInterface;
use App\DTOs\CreateAccountDTO;
use App\DTOs\UpdateAccountDTO;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Account\BulkDestroyAccountRequest;
use App\Http\Requests\Admin\Account\RestoreAccountRequest;
use App\Http\Requests\Admin\Account\StoreAccountRequest;
use App\Http\Requests\Admin\Account\UpdateAccountRequest;
use App\Http\Resources\Admin\Account\AccountResource;
use App\Models\Account;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountServiceInterface $accountService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'status', 'type', 'page', 'per_page']);
        $paginator = $this->accountService->getPaginatedAccounts($filters);

        $accounts = $paginator->toArray();
        $accounts['data'] = AccountResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/accounts/accounts-index', [
            'accounts' => $accounts,
            'currencies' => Currency::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'symbol', 'name']),
            'accountTypes' => collect(AccountType::cases())->map(fn (AccountType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
            'search' => $filters,
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->accountService->createAccount(new CreateAccountDTO(
            name: $validated['name'],
            accountNumber: $validated['account_number'] ?? null,
            bankName: $validated['bank_name'] ?? null,
            bankBranch: $validated['bank_branch'] ?? null,
            currencyId: (int) $validated['currency_id'],
            type: AccountType::from($validated['type']),
            openingBalance: (float) $validated['opening_balance'],
            isActive: (bool) $validated['is_active'],
            notes: $validated['notes'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account created successfully.')]);

        return back();
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $validated = $request->validated();

        $this->accountService->updateAccount($account, new UpdateAccountDTO(
            name: $validated['name'],
            accountNumber: $validated['account_number'] ?? null,
            bankName: $validated['bank_name'] ?? null,
            bankBranch: $validated['bank_branch'] ?? null,
            currencyId: (int) $validated['currency_id'],
            type: AccountType::from($validated['type']),
            openingBalance: (float) $validated['opening_balance'],
            isActive: (bool) $validated['is_active'],
            notes: $validated['notes'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account updated successfully.')]);

        return back();
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->accountService->deleteAccount($account);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account deleted successfully.')]);

        return back();
    }

    public function bulkDestroy(BulkDestroyAccountRequest $request): RedirectResponse
    {
        $this->accountService->bulkDelete($request->validated('ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Accounts deleted successfully.')]);

        return back();
    }

    public function restore(RestoreAccountRequest $request, int|string $id): RedirectResponse
    {
        $this->accountService->restoreAccount($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account restored successfully.')]);

        return back();
    }
}
