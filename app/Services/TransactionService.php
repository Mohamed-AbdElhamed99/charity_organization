<?php

namespace App\Services;

use App\Contracts\Services\TransactionServiceInterface;
use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\CampaignExpense;
use App\Models\GeneralExpense;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService implements TransactionServiceInterface
{
    public function getPaginatedTransactions(array $filters): LengthAwarePaginator
    {
        return $this->buildFilteredQuery($filters)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function getFilteredTransactions(array $filters): Collection
    {
        return $this->buildFilteredQuery($filters)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    public function createTransaction(CreateTransactionDTO $dto): Transaction
    {
        return DB::transaction(function () use ($dto) {
            $account = Account::query()
                ->lockForUpdate()
                ->findOrFail($dto->accountId);

            $grossAmount = round($dto->grossAmount, 2);
            $feeAmount = round($dto->feeAmount, 2);
            $netAmount = round($grossAmount - $feeAmount, 2);

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => $dto->transactionType,
                'direction' => $dto->direction,
                'currency_id' => $dto->currencyId,
                'gross_amount' => $grossAmount,
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'transaction_date' => $dto->transactionDate,
                'reference_number' => $dto->referenceNumber,
                'description' => $dto->description,
                'notes' => $dto->notes,
                'payment_method_id' => $dto->paymentMethodId,
                'created_by' => $dto->createdBy,
            ]);

            $this->applyRunningBalance($account, $transaction);

            return $transaction->fresh(['account', 'currency', 'paymentMethod', 'creator']);
        });
    }

    public function updateTransaction(Transaction $transaction, UpdateTransactionDTO $dto): Transaction
    {
        return DB::transaction(function () use ($transaction, $dto) {
            $previousAccountId = $transaction->account_id;

            $grossAmount = round($dto->grossAmount, 2);
            $feeAmount = round($dto->feeAmount, 2);
            $netAmount = round($grossAmount - $feeAmount, 2);

            $transaction->update([
                'account_id' => $dto->accountId,
                'transaction_type' => $dto->transactionType,
                'direction' => $dto->direction,
                'currency_id' => $dto->currencyId,
                'gross_amount' => $grossAmount,
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'transaction_date' => $dto->transactionDate,
                'reference_number' => $dto->referenceNumber,
                'description' => $dto->description,
                'notes' => $dto->notes,
                'payment_method_id' => $dto->paymentMethodId,
            ]);

            $this->recalculateAccountBalances(
                Account::query()->lockForUpdate()->findOrFail($dto->accountId),
            );

            if ($previousAccountId !== $dto->accountId) {
                $this->recalculateAccountBalances(
                    Account::query()->lockForUpdate()->findOrFail($previousAccountId),
                );
            }

            return $transaction->fresh(['account', 'currency', 'paymentMethod', 'creator']);
        });
    }

    public function createForExpense(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $account = Account::query()
                ->lockForUpdate()
                ->findOrFail($data['account_id']);

            $amount = round((float) $data['amount'], 2);
            $userId = Auth::id();

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => TransactionType::CampaignExpense,
                'direction' => TransactionDirection::Out,
                'currency_id' => $account->currency_id,
                'gross_amount' => $amount,
                'fee_amount' => 0,
                'net_amount' => $amount,
                'transaction_date' => $data['expense_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'] ?? 'Campaign expense',
                'notes' => $data['notes'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'created_by' => $userId,
            ]);

            $this->applyRunningBalance($account, $transaction);

            $quantity = round((float) $data['quantity'], 3);

            CampaignExpense::create([
                'transaction_id' => $transaction->id,
                'campaign_id' => $data['campaign_id'],
                'item_id' => $data['item_id'],
                'item_price' => round((float) $data['item_price'], 2),
                'quantity' => $quantity,
                'amount' => $amount,
                'residual_quantity' => $quantity,
                'residual_amount' => $amount,
                'responsible_user_id' => $data['responsible_user_id'],
                'expense_date' => $data['expense_date'],
                'notes' => $data['expense_notes'] ?? null,
            ]);

            return $transaction->fresh(['account', 'currency', 'campaignExpense']);
        });
    }

    public function createForGeneralExpense(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $account = Account::query()
                ->lockForUpdate()
                ->findOrFail($data['account_id']);

            $amount = round((float) $data['amount'], 2);
            $userId = Auth::id();

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => TransactionType::GeneralExpense,
                'direction' => TransactionDirection::Out,
                'currency_id' => $account->currency_id,
                'gross_amount' => $amount,
                'fee_amount' => 0,
                'net_amount' => $amount,
                'transaction_date' => $data['expense_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'] ?? $data['name'],
                'notes' => $data['transaction_notes'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'created_by' => $userId,
            ]);

            $this->applyRunningBalance($account, $transaction);

            GeneralExpense::create([
                'transaction_id' => $transaction->id,
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'amount' => $amount,
                'expense_date' => $data['expense_date'],
                'vendor_name' => $data['vendor_name'] ?? null,
                'is_recurring' => $data['is_recurring'] ?? false,
                'created_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            return $transaction->fresh(['account', 'currency', 'paymentMethod', 'generalExpense']);
        });
    }

    public function createForTransfer(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $account = Account::query()
                ->lockForUpdate()
                ->findOrFail($data['account_id']);

            $amount = round((float) $data['amount'], 2);
            $userId = Auth::id();

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => TransactionType::Transfer,
                'direction' => TransactionDirection::Out,
                'currency_id' => $account->currency_id,
                'gross_amount' => $amount,
                'fee_amount' => 0,
                'net_amount' => $amount,
                'transaction_date' => $data['transaction_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'created_by' => $userId,
            ]);

            $this->applyRunningBalance($account, $transaction);

            return $transaction->fresh(['account', 'currency']);
        });
    }

    public function reverseTransaction(Transaction $transaction, int $userId): Transaction
    {
        if ($transaction->transaction_type === TransactionType::Adjustment) {
            throw new InvalidArgumentException('Adjustment transactions cannot be reversed.');
        }

        return DB::transaction(function () use ($transaction, $userId) {
            $account = Account::query()
                ->lockForUpdate()
                ->findOrFail($transaction->account_id);

            $oppositeDirection = $transaction->direction === TransactionDirection::In
                ? TransactionDirection::Out
                : TransactionDirection::In;

            $reversal = Transaction::create([
                'account_id' => $transaction->account_id,
                'transaction_type' => TransactionType::Adjustment,
                'direction' => $oppositeDirection,
                'currency_id' => $transaction->currency_id,
                'gross_amount' => $transaction->gross_amount,
                'fee_amount' => $transaction->fee_amount,
                'net_amount' => $transaction->net_amount,
                'transaction_date' => now()->toDateString(),
                'reference_number' => $transaction->reference_number,
                'description' => "Reversal of transaction #{$transaction->id}",
                'notes' => "Compensating entry for transaction #{$transaction->id}: {$transaction->description}",
                'payment_method_id' => $transaction->payment_method_id,
                'created_by' => $userId,
            ]);

            $this->applyRunningBalance($account, $reversal);

            return $reversal->fresh(['account', 'currency']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        $type = $filters['type'] ?? null;
        $direction = $filters['direction'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $accountId = $filters['account_id'] ?? null;
        $campaignId = $filters['campaign_id'] ?? null;

        return Transaction::query()
            ->with(['account', 'currency', 'paymentMethod', 'creator'])
            ->when($type, fn ($query) => $query->where('transaction_type', $type))
            ->when($direction, fn ($query) => $query->where('direction', $direction))
            ->when($dateFrom && $dateTo, fn ($query) => $query->inDateRange($dateFrom, $dateTo))
            ->when($dateFrom && ! $dateTo, fn ($query) => $query->where('transaction_date', '>=', $dateFrom))
            ->when($dateTo && ! $dateFrom, fn ($query) => $query->where('transaction_date', '<=', $dateTo))
            ->when($accountId, fn ($query) => $query->forAccount((int) $accountId))
            ->when($campaignId, function ($query) use ($campaignId) {
                $query->where(function ($builder) use ($campaignId) {
                    $builder
                        ->whereHas('campaignExpense', fn ($q) => $q->where('campaign_id', $campaignId))
                        ->orWhereHas('transfer', fn ($q) => $q->where('campaign_id', $campaignId))
                        ->orWhereHas('donation', fn ($q) => $q->where('campaign_id', $campaignId));
                });
            });
    }

    private function applyRunningBalance(Account $account, Transaction $transaction): void
    {
        $runningBalance = $this->computeRunningBalance(
            $account,
            $transaction->direction,
            (string) $transaction->net_amount,
            $transaction->id,
        );

        $transaction->update(['running_balance' => $runningBalance]);
    }

    private function recalculateAccountBalances(Account $account): void
    {
        $balance = (string) $account->opening_balance;

        $transactions = Transaction::query()
            ->where('account_id', $account->id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $balance = $transaction->direction === TransactionDirection::In
                ? bcadd($balance, (string) $transaction->net_amount, 2)
                : bcsub($balance, (string) $transaction->net_amount, 2);

            $transaction->update(['running_balance' => $balance]);
        }
    }

    private function computeRunningBalance(
        Account $account,
        TransactionDirection $direction,
        string $netAmount,
        ?int $excludeTransactionId = null,
    ): string {
        $lastBalance = Transaction::query()
            ->where('account_id', $account->id)
            ->when($excludeTransactionId, fn ($query) => $query->where('id', '!=', $excludeTransactionId))
            ->whereNotNull('running_balance')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('running_balance');

        $balance = (string) ($lastBalance ?? $account->opening_balance);

        return $direction === TransactionDirection::In
            ? bcadd($balance, $netAmount, 2)
            : bcsub($balance, $netAmount, 2);
    }
}
