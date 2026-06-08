<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\TransactionServiceInterface;
use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Transaction\StoreTransactionRequest;
use App\Http\Requests\Admin\Transaction\UpdateTransactionRequest;
use App\Http\Resources\Admin\Transaction\TransactionResource;
use App\Models\Account;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionServiceInterface $transactionService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('view_transactions'), 403);

        $filters = $request->only([
            'type',
            'direction',
            'date_from',
            'date_to',
            'account_id',
            'campaign_id',
            'page',
            'per_page',
        ]);

        $paginator = $this->transactionService->getPaginatedTransactions($filters);

        $transactions = $paginator->toArray();
        $transactions['data'] = TransactionResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/transactions/transactions-index', [
            'transactions' => $transactions,
            'accounts' => Account::query()->active()->orderBy('name')->get(['id', 'name']),
            'currencies' => Currency::query()->active()->orderBy('code')->get(['id', 'code', 'symbol']),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('name')->get(['id', 'name', 'code']),
            'search' => $filters,
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        abort_unless(request()->user()?->can('view_transactions'), 403);

        $transaction->load([
            'account',
            'currency',
            'paymentMethod',
            'creator',
            'donation',
            'campaignExpense',
            'generalExpense',
            'transfer',
            'bankExpense',
        ]);

        return Inertia::render('admin/transactions/transactions-show', [
            'transaction' => (new TransactionResource($transaction))->resolve(),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->transactionService->createTransaction(new CreateTransactionDTO(
            accountId: (int) $validated['account_id'],
            transactionType: TransactionType::from($validated['transaction_type']),
            direction: TransactionDirection::from($validated['direction']),
            currencyId: (int) $validated['currency_id'],
            grossAmount: (float) $validated['gross_amount'],
            feeAmount: (float) ($validated['fee_amount'] ?? 0),
            transactionDate: $validated['transaction_date'],
            referenceNumber: $validated['reference_number'] ?? null,
            description: $validated['description'] ?? null,
            notes: $validated['notes'] ?? null,
            paymentMethodId: isset($validated['payment_method_id']) ? (int) $validated['payment_method_id'] : null,
            createdBy: Auth::id(),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction created successfully.')]);

        return back();
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validated();

        $this->transactionService->updateTransaction($transaction, new UpdateTransactionDTO(
            accountId: (int) $validated['account_id'],
            transactionType: TransactionType::from($validated['transaction_type']),
            direction: TransactionDirection::from($validated['direction']),
            currencyId: (int) $validated['currency_id'],
            grossAmount: (float) $validated['gross_amount'],
            feeAmount: (float) ($validated['fee_amount'] ?? 0),
            transactionDate: $validated['transaction_date'],
            referenceNumber: $validated['reference_number'] ?? null,
            description: $validated['description'] ?? null,
            notes: $validated['notes'] ?? null,
            paymentMethodId: isset($validated['payment_method_id']) ? (int) $validated['payment_method_id'] : null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction updated successfully.')]);

        return back();
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('view_transactions'), 403);

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
        abort_unless(request()->user()?->can('edit_transactions'), 403);

        $this->transactionService->reverseTransaction($transaction, Auth::id());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction reversed successfully.')]);

        return back();
    }
}
