<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\TransactionServiceInterface;
use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Enums\TransactionDirection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Transaction\CalculateBalanceRequest;
use App\Http\Requests\Admin\Transaction\StoreTransactionRequest;
use App\Http\Requests\Admin\Transaction\UpdateTransactionRequest;
use App\Http\Resources\Admin\Transaction\TransactionResource;
use App\Models\BankAccount;
use App\Models\Beneficiary;
use App\Models\Campaign;
use App\Models\Currency;
use App\Models\GeneralExpense;
use App\Models\Meeting;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionServiceInterface $transactionService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'type',
            'direction',
            'date_from',
            'date_to',
            'account_id',
            'campaign_id',
            'page',
            'per_page',
            'view',
            'month',
        ]);

        $view = $filters['view'] ?? 'list';

        if ($view === 'calendar') {
            $month = $this->resolveCalendarMonth(
                is_string($filters['month'] ?? null) ? $filters['month'] : null,
            );

            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

            $filters['view'] = 'calendar';
            $filters['month'] = $month;
            $filters['date_from'] = $start->toDateString();
            $filters['date_to'] = $start->copy()->endOfMonth()->toDateString();
        } else {
            $filters['view'] = 'list';
            unset($filters['month']);
        }

        $paginator = $this->transactionService->getPaginatedTransactions($filters);

        $transactions = $paginator->toArray();
        $transactions['data'] = TransactionResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/transactions/transactions-index', [
            'transactions' => $transactions,
            'accounts' => $this->accountOptions(),
            'currencies' => $this->currencyOptions(),
            'paymentMethods' => $this->paymentMethodOptions(),
            'transactionTypes' => $this->transactionTypeOptions(),
            'search' => $filters,
        ]);
    }

    private function resolveCalendarMonth(?string $month): string
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            $parsed = Carbon::createFromFormat('!Y-m', $month);

            if ($parsed !== false && $parsed->format('Y-m') === $month) {
                return $month;
            }
        }

        return now()->format('Y-m');
    }

    public function create(Request $request): Response
    {
        $defaultTypeCode = $request->query('type');
        $defaultTypeId = null;

        if (is_string($defaultTypeCode) && $defaultTypeCode !== '') {
            $defaultTypeId = TransactionType::query()->where('code', $defaultTypeCode)->value('id');
        }

        return Inertia::render('admin/transactions/transactions-create', [
            'accounts' => $this->accountOptions(),
            'currencies' => $this->currencyOptions(),
            'paymentMethods' => $this->paymentMethodOptions(),
            'campaigns' => $this->campaignOptions(),
            'users' => $this->userOptions(),
            'beneficiaries' => $this->beneficiaryOptions(),
            'meetings' => $this->meetingOptions(),
            'generalExpenses' => $this->generalExpenseOptions(),
            'transactionTypes' => $this->transactionTypeOptions(),
            'directions' => collect(TransactionDirection::cases())->map(fn (TransactionDirection $direction) => [
                'value' => $direction->value,
                'label' => ucfirst($direction->value),
            ]),
            'defaultTypeId' => $defaultTypeId,
        ]);
    }

    public function edit(Transaction $transaction): Response
    {
        $transaction->load([
            'account',
            'currency',
            'originalCurrency',
            'paymentMethod',
            'transactionType',
            'transfer.recipient',
            'beneficiaries',
            'campaigns',
            'meetings',
            'generalExpenses',
            'media',
        ]);

        return Inertia::render('admin/transactions/transactions-edit', [
            'transaction' => (new TransactionResource($transaction))->resolve(),
            'accounts' => $this->accountOptions(),
            'currencies' => $this->currencyOptions(),
            'paymentMethods' => $this->paymentMethodOptions(),
            'campaigns' => $this->campaignOptions(),
            'users' => $this->userOptions(),
            'beneficiaries' => $this->beneficiaryOptions(),
            'meetings' => $this->meetingOptions(),
            'generalExpenses' => $this->generalExpenseOptions(),
            'transactionTypes' => $this->transactionTypeOptions(),
            'directions' => collect(TransactionDirection::cases())->map(fn (TransactionDirection $direction) => [
                'value' => $direction->value,
                'label' => ucfirst($direction->value),
            ]),
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        $transaction->load([
            'account',
            'currency',
            'originalCurrency',
            'paymentMethod',
            'creator',
            'transactionType',
            'donation',
            'campaignExpense',
            'generalExpenses',
            'transfer.recipient',
            'beneficiaries',
            'campaigns',
            'meetings',
            'bankExpense',
            'media',
        ]);

        return Inertia::render('admin/transactions/transactions-show', [
            'transaction' => (new TransactionResource($transaction))->resolve(),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $transaction = $this->transactionService->createTransaction($this->toCreateDto($validated));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction created successfully.')]);

        return redirect()->route('admin.transactions.show', $transaction);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validated();

        $this->transactionService->updateTransaction($transaction, $this->toUpdateDto($validated));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction updated successfully.')]);

        return redirect()->route('admin.transactions.show', $transaction);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'type',
            'direction',
            'date_from',
            'date_to',
            'account_id',
            'campaign_id',
        ]);

        $transactions = $this->transactionService->getFilteredTransactions($filters);
        $filename = 'account-statement-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date', 'Balance', 'Expenses', 'Donations', 'Transfer', 'Details']);

            foreach ($transactions as $transaction) {
                $resource = (new TransactionResource($transaction))->resolve();

                fputcsv($handle, [
                    $resource['transaction_date'],
                    $resource['running_balance'],
                    $resource['export_expenses'],
                    $resource['export_donations'],
                    $resource['export_transfer'],
                    $resource['export_details'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function reverse(Transaction $transaction): RedirectResponse
    {
        $this->transactionService->reverseTransaction($transaction, Auth::id());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction reversed successfully.')]);

        return back();
    }

    public function calculateBalance(CalculateBalanceRequest $request): RedirectResponse
    {
        $month = $request->validated('month');

        try {
            $this->transactionService->calculateMonthBalances($month);
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __($exception->getMessage())]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Balances calculated successfully.'),
        ]);

        return redirect()->route('admin.transactions.index', [
            'view' => 'calendar',
            'month' => $month,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function toCreateDto(array $validated): CreateTransactionDTO
    {
        return new CreateTransactionDTO(
            accountId: (int) $validated['account_id'],
            transactionTypeId: (int) $validated['transaction_type_id'],
            direction: TransactionDirection::from($validated['direction']),
            grossAmount: (float) $validated['gross_amount'],
            feeAmount: (float) ($validated['fee_amount'] ?? 0),
            transactionDate: $validated['transaction_date'],
            referenceNumber: $validated['reference_number'] ?? null,
            description: $validated['description'] ?? null,
            notes: $validated['notes'] ?? null,
            paymentMethodId: isset($validated['payment_method_id']) ? (int) $validated['payment_method_id'] : null,
            createdBy: (int) Auth::id(),
            originalCurrencyId: isset($validated['original_currency_id']) ? (int) $validated['original_currency_id'] : null,
            originalAmount: isset($validated['original_amount']) ? (float) $validated['original_amount'] : null,
            exchangeRate: isset($validated['exchange_rate']) ? (float) $validated['exchange_rate'] : null,
            currencyEquivalents: $validated['currency_equivalents'] ?? null,
            balanceBefore: isset($validated['balance_before']) && $validated['balance_before'] !== ''
                ? (float) $validated['balance_before']
                : null,
            runningBalance: isset($validated['running_balance']) && $validated['running_balance'] !== ''
                ? (float) $validated['running_balance']
                : null,
            transfer: $validated['transfer'] ?? null,
            documents: $validated['documents'] ?? null,
            beneficiaryIds: array_map('intval', $validated['beneficiary_ids'] ?? []),
            campaignIds: array_map('intval', $validated['campaign_ids'] ?? []),
            meetingIds: array_map('intval', $validated['meeting_ids'] ?? []),
            generalExpenseIds: array_map('intval', $validated['general_expense_ids'] ?? []),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function toUpdateDto(array $validated): UpdateTransactionDTO
    {
        return new UpdateTransactionDTO(
            accountId: (int) $validated['account_id'],
            transactionTypeId: (int) $validated['transaction_type_id'],
            direction: TransactionDirection::from($validated['direction']),
            grossAmount: (float) $validated['gross_amount'],
            feeAmount: (float) ($validated['fee_amount'] ?? 0),
            transactionDate: $validated['transaction_date'],
            referenceNumber: $validated['reference_number'] ?? null,
            description: $validated['description'] ?? null,
            notes: $validated['notes'] ?? null,
            paymentMethodId: isset($validated['payment_method_id']) ? (int) $validated['payment_method_id'] : null,
            originalCurrencyId: isset($validated['original_currency_id']) ? (int) $validated['original_currency_id'] : null,
            originalAmount: isset($validated['original_amount']) ? (float) $validated['original_amount'] : null,
            exchangeRate: isset($validated['exchange_rate']) ? (float) $validated['exchange_rate'] : null,
            currencyEquivalents: $validated['currency_equivalents'] ?? null,
            balanceBefore: isset($validated['balance_before']) && $validated['balance_before'] !== ''
                ? (float) $validated['balance_before']
                : null,
            runningBalance: isset($validated['running_balance']) && $validated['running_balance'] !== ''
                ? (float) $validated['running_balance']
                : null,
            transfer: $validated['transfer'] ?? null,
            documents: $validated['documents'] ?? null,
            removeDocumentIds: isset($validated['remove_document_ids'])
                ? array_map('intval', $validated['remove_document_ids'])
                : null,
            beneficiaryIds: array_map('intval', $validated['beneficiary_ids'] ?? []),
            campaignIds: array_map('intval', $validated['campaign_ids'] ?? []),
            meetingIds: array_map('intval', $validated['meeting_ids'] ?? []),
            generalExpenseIds: array_map('intval', $validated['general_expense_ids'] ?? []),
        );
    }

    /**
     * @return Collection<int, array{id: int, name: string, code: string}>
     */
    private function transactionTypeOptions()
    {
        return TransactionType::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_system']);
    }

    /**
     * @return Collection<int, array{id: int, name: string, currency_id: int, opening_balance: string|float, current_balance: float}>
     */
    private function accountOptions()
    {
        return BankAccount::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'currency_id', 'opening_balance'])
            ->map(fn (BankAccount $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'currency_id' => $account->currency_id,
                'opening_balance' => $account->opening_balance,
                'current_balance' => round((float) $account->current_balance, 2),
            ]);
    }

    /**
     * @return Collection<int, array{id: int, code: string, symbol: string, name: string}>
     */
    private function currencyOptions()
    {
        return Currency::query()->active()->orderBy('code')->get(['id', 'code', 'symbol', 'name']);
    }

    /**
     * @return Collection<int, array{id: int, name: string, code: string}>
     */
    private function paymentMethodOptions()
    {
        return PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name', 'code']);
    }

    /**
     * @return Collection<int, array{id: int, title_en: string, title_ar: string}>
     */
    private function campaignOptions()
    {
        return Campaign::query()->orderBy('title_en')->get(['id', 'title_ar', 'title_en']);
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    private function userOptions()
    {
        return User::query()->orderBy('name')->limit(200)->get(['id', 'name']);
    }

    /**
     * @return Collection<int, array{id: int, display_name: string}>
     */
    private function beneficiaryOptions()
    {
        return Beneficiary::query()
            ->active()
            ->limit(200)
            ->get()
            ->map(fn (Beneficiary $beneficiary) => [
                'id' => $beneficiary->id,
                'display_name' => $beneficiary->display_name,
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function meetingOptions()
    {
        return Meeting::query()
            ->orderByDesc('meeting_date')
            ->limit(200)
            ->get(['id', 'meeting_number', 'title'])
            ->map(fn (Meeting $meeting) => [
                'id' => $meeting->id,
                'label' => trim(($meeting->meeting_number ? "{$meeting->meeting_number} — " : '').($meeting->title ?? '')),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    private function generalExpenseOptions()
    {
        return GeneralExpense::query()
            ->orderByDesc('expense_date')
            ->limit(200)
            ->get(['id', 'name', 'expense_date'])
            ->map(fn (GeneralExpense $expense) => [
                'id' => $expense->id,
                'name' => $expense->expense_date
                    ? "{$expense->name} ({$expense->expense_date->toDateString()})"
                    : $expense->name,
            ])
            ->values();
    }
}
