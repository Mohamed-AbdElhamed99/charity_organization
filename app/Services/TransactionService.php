<?php

namespace App\Services;

use App\Contracts\Services\TransactionServiceInterface;
use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Enums\TransactionDirection;
use App\Models\BankAccount;
use App\Models\Beneficiary;
use App\Models\CampaignExpense;
use App\Models\GeneralExpense;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService implements TransactionServiceInterface
{
    public function getPaginatedTransactions(array $filters): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery($filters);

        if (($filters['view'] ?? null) === 'calendar') {
            $query->orderBy('transaction_date')->orderBy('id');
        } else {
            $query->orderByDesc('transaction_date')->orderByDesc('id');
        }

        return $query
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
            $account = BankAccount::query()
                ->lockForUpdate()
                ->findOrFail($dto->accountId);

            $fx = $this->resolveFxAmounts(
                $account,
                $dto->grossAmount,
                $dto->feeAmount,
                $dto->originalCurrencyId,
                $dto->originalAmount,
                $dto->exchangeRate,
            );

            $transactionType = TransactionType::query()->findOrFail($dto->transactionTypeId);
            $isTransfer = $transactionType->isTransfer();

            $direction = $isTransfer
                ? TransactionDirection::Out
                : $dto->direction;

            $description = $dto->description;
            if ($isTransfer && $dto->transfer !== null) {
                $description = $this->buildTransferDescription($dto->transfer, $description);
            }

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type_id' => $transactionType->id,
                'direction' => $direction,
                'currency_id' => $account->currency_id,
                'original_currency_id' => $fx['original_currency_id'],
                'gross_amount' => $fx['gross_amount'],
                'fee_amount' => $fx['fee_amount'],
                'net_amount' => $fx['net_amount'],
                'original_amount' => $fx['original_amount'],
                'exchange_rate' => $fx['exchange_rate'],
                'currency_equivalents' => $this->normalizeCurrencyEquivalents($dto->currencyEquivalents),
                'transaction_date' => $dto->transactionDate,
                'reference_number' => $dto->referenceNumber,
                'description' => $description ?? '',
                'notes' => $dto->notes,
                'payment_method_id' => $dto->paymentMethodId,
                'created_by' => $dto->createdBy,
            ]);

            $this->applyBalances(
                $account,
                $transaction,
                $dto->balanceBefore,
                $dto->runningBalance,
            );

            if ($isTransfer && $dto->transfer !== null) {
                $this->syncTransferDetail($transaction, $dto->transfer, $fx['net_amount']);
            }

            $this->syncTransactionLinks(
                $transaction,
                $dto->beneficiaryIds,
                $dto->campaignIds,
                $dto->meetingIds,
                $dto->generalExpenseIds,
            );

            $this->attachDocuments($transaction, $dto->documents ?? []);

            return $transaction->fresh([
                'account',
                'currency',
                'originalCurrency',
                'paymentMethod',
                'creator',
                'transactionType',
                'transfer.recipient',
                'beneficiaries',
                'campaigns',
                'meetings',
                'generalExpenses',
                'media',
            ]);
        });
    }

    public function updateTransaction(Transaction $transaction, UpdateTransactionDTO $dto): Transaction
    {
        return DB::transaction(function () use ($transaction, $dto) {
            $previousAccountId = $transaction->account_id;

            $account = BankAccount::query()
                ->lockForUpdate()
                ->findOrFail($dto->accountId);

            $fx = $this->resolveFxAmounts(
                $account,
                $dto->grossAmount,
                $dto->feeAmount,
                $dto->originalCurrencyId,
                $dto->originalAmount,
                $dto->exchangeRate,
            );

            $transactionType = TransactionType::query()->findOrFail($dto->transactionTypeId);
            $isTransfer = $transactionType->isTransfer();

            $direction = $isTransfer
                ? TransactionDirection::Out
                : $dto->direction;

            $description = $dto->description;
            if ($isTransfer && $dto->transfer !== null) {
                $description = $this->buildTransferDescription($dto->transfer, $description);
            }

            $transaction->update([
                'account_id' => $dto->accountId,
                'transaction_type_id' => $transactionType->id,
                'direction' => $direction,
                'currency_id' => $account->currency_id,
                'original_currency_id' => $fx['original_currency_id'],
                'gross_amount' => $fx['gross_amount'],
                'fee_amount' => $fx['fee_amount'],
                'net_amount' => $fx['net_amount'],
                'original_amount' => $fx['original_amount'],
                'exchange_rate' => $fx['exchange_rate'],
                'currency_equivalents' => $this->normalizeCurrencyEquivalents($dto->currencyEquivalents),
                'transaction_date' => $dto->transactionDate,
                'reference_number' => $dto->referenceNumber,
                'description' => $description ?? '',
                'notes' => $dto->notes,
                'payment_method_id' => $dto->paymentMethodId,
            ]);

            if ($isTransfer) {
                if ($dto->transfer !== null) {
                    $this->syncTransferDetail($transaction, $dto->transfer, $fx['net_amount']);
                }
            } elseif ($transaction->transfer) {
                $transaction->transfer->delete();
            }

            $this->removeDocuments($transaction, $dto->removeDocumentIds ?? []);
            $this->attachDocuments($transaction, $dto->documents ?? []);

            $this->recalculateAccountBalances($account);

            if ($previousAccountId !== $dto->accountId) {
                $this->recalculateAccountBalances(
                    BankAccount::query()->lockForUpdate()->findOrFail($previousAccountId),
                );
            }

            if ($dto->balanceBefore !== null || $dto->runningBalance !== null) {
                $this->applyBalanceOverrides(
                    $transaction->fresh(),
                    $dto->balanceBefore,
                    $dto->runningBalance,
                );
            }

            $this->syncTransactionLinks(
                $transaction,
                $dto->beneficiaryIds,
                $dto->campaignIds,
                $dto->meetingIds,
                $dto->generalExpenseIds,
            );

            return $transaction->fresh([
                'account',
                'currency',
                'originalCurrency',
                'paymentMethod',
                'creator',
                'transactionType',
                'transfer.recipient',
                'beneficiaries',
                'campaigns',
                'meetings',
                'generalExpenses',
                'media',
            ]);
        });
    }

    public function createForExpense(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $account = BankAccount::query()
                ->lockForUpdate()
                ->findOrFail($data['account_id']);

            $fx = $this->resolveFxAmounts(
                $account,
                (float) $data['amount'],
                0,
                isset($data['original_currency_id']) ? (int) $data['original_currency_id'] : null,
                isset($data['original_amount']) ? (float) $data['original_amount'] : null,
                isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
            );

            $userId = Auth::id();

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type_id' => TransactionType::idForCode(TransactionType::CODE_CAMPAIGN_EXPENSE),
                'direction' => TransactionDirection::Out,
                'currency_id' => $account->currency_id,
                'original_currency_id' => $fx['original_currency_id'],
                'gross_amount' => $fx['gross_amount'],
                'fee_amount' => 0,
                'net_amount' => $fx['net_amount'],
                'original_amount' => $fx['original_amount'],
                'exchange_rate' => $fx['exchange_rate'],
                'transaction_date' => $data['expense_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'] ?? 'Campaign expense',
                'notes' => $data['notes'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'created_by' => $userId,
            ]);

            $this->applyRunningBalance($account, $transaction);

            $quantity = round((float) $data['quantity'], 3);
            $ledgerAmount = $fx['net_amount'];

            CampaignExpense::create([
                'transaction_id' => $transaction->id,
                'campaign_id' => $data['campaign_id'],
                'item_id' => $data['item_id'],
                'item_price' => round((float) $data['item_price'], 2),
                'quantity' => $quantity,
                'amount' => $ledgerAmount,
                'residual_quantity' => $quantity,
                'residual_amount' => $ledgerAmount,
                'responsible_user_id' => $data['responsible_user_id'],
                'expense_date' => $data['expense_date'],
                'notes' => $data['expense_notes'] ?? null,
            ]);

            return $transaction->fresh(['account', 'currency', 'originalCurrency', 'campaignExpense']);
        });
    }

    public function createForGeneralExpense(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $account = BankAccount::query()
                ->lockForUpdate()
                ->findOrFail($data['account_id']);

            $fx = $this->resolveFxAmounts(
                $account,
                (float) $data['amount'],
                0,
                isset($data['original_currency_id']) ? (int) $data['original_currency_id'] : null,
                isset($data['original_amount']) ? (float) $data['original_amount'] : null,
                isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
            );

            $userId = Auth::id();

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type_id' => TransactionType::idForCode(TransactionType::CODE_GENERAL_EXPENSE),
                'direction' => TransactionDirection::Out,
                'currency_id' => $account->currency_id,
                'original_currency_id' => $fx['original_currency_id'],
                'gross_amount' => $fx['gross_amount'],
                'fee_amount' => 0,
                'net_amount' => $fx['net_amount'],
                'original_amount' => $fx['original_amount'],
                'exchange_rate' => $fx['exchange_rate'],
                'transaction_date' => $data['expense_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'] ?? $data['name'],
                'notes' => $data['transaction_notes'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'created_by' => $userId,
            ]);

            $this->applyRunningBalance($account, $transaction);

            $expense = GeneralExpense::create([
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'amount' => $fx['net_amount'],
                'expense_date' => $data['expense_date'],
                'vendor_name' => $data['vendor_name'] ?? null,
                'is_recurring' => $data['is_recurring'] ?? false,
                'created_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            $transaction->generalExpenses()->attach($expense->id);

            return $transaction->fresh([
                'account',
                'currency',
                'originalCurrency',
                'paymentMethod',
                'generalExpenses',
            ]);
        });
    }

    public function createForTransfer(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $account = BankAccount::query()
                ->lockForUpdate()
                ->findOrFail($data['account_id']);

            $fx = $this->resolveFxAmounts(
                $account,
                (float) $data['amount'],
                0,
                isset($data['original_currency_id']) ? (int) $data['original_currency_id'] : null,
                isset($data['original_amount']) ? (float) $data['original_amount'] : null,
                isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
            );

            $userId = Auth::id();

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type_id' => TransactionType::idForCode(TransactionType::CODE_TRANSFER),
                'direction' => TransactionDirection::Out,
                'currency_id' => $account->currency_id,
                'original_currency_id' => $fx['original_currency_id'],
                'gross_amount' => $fx['gross_amount'],
                'fee_amount' => 0,
                'net_amount' => $fx['net_amount'],
                'original_amount' => $fx['original_amount'],
                'exchange_rate' => $fx['exchange_rate'],
                'transaction_date' => $data['transaction_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'created_by' => $userId,
            ]);

            $this->applyRunningBalance($account, $transaction);

            return $transaction->fresh(['account', 'currency', 'originalCurrency']);
        });
    }

    public function reverseTransaction(Transaction $transaction, int $userId): Transaction
    {
        $transaction->loadMissing('transactionType');

        if ($transaction->transactionType?->isAdjustment()) {
            throw new InvalidArgumentException('Adjustment transactions cannot be reversed.');
        }

        return DB::transaction(function () use ($transaction, $userId) {
            $account = BankAccount::query()
                ->lockForUpdate()
                ->findOrFail($transaction->account_id);

            $oppositeDirection = $transaction->direction === TransactionDirection::In
                ? TransactionDirection::Out
                : TransactionDirection::In;

            $reversal = Transaction::create([
                'account_id' => $transaction->account_id,
                'transaction_type_id' => TransactionType::idForCode(TransactionType::CODE_ADJUSTMENT),
                'direction' => $oppositeDirection,
                'currency_id' => $transaction->currency_id,
                'original_currency_id' => $transaction->original_currency_id,
                'gross_amount' => $transaction->gross_amount,
                'fee_amount' => $transaction->fee_amount,
                'net_amount' => $transaction->net_amount,
                'original_amount' => $transaction->original_amount,
                'exchange_rate' => $transaction->exchange_rate,
                'transaction_date' => now()->toDateString(),
                'reference_number' => $transaction->reference_number,
                'description' => "Reversal of transaction #{$transaction->id}",
                'notes' => "Compensating entry for transaction #{$transaction->id}: {$transaction->description}",
                'payment_method_id' => $transaction->payment_method_id,
                'created_by' => $userId,
            ]);

            $this->applyRunningBalance($account, $reversal);

            if ($transaction->transactionType?->isTransfer()) {
                $transaction->transfer?->delete();
            }

            return $reversal->fresh(['account', 'currency', 'originalCurrency', 'transactionType']);
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
            ->with(['account', 'currency', 'originalCurrency', 'paymentMethod', 'creator', 'transactionType'])
            ->when($type, function ($query) use ($type) {
                if (is_numeric($type)) {
                    $query->where('transaction_type_id', (int) $type);

                    return;
                }

                $query->ofType((string) $type);
            })
            ->when($direction, fn ($query) => $query->where('direction', $direction))
            ->when($dateFrom && $dateTo, fn ($query) => $query->inDateRange($dateFrom, $dateTo))
            ->when($dateFrom && ! $dateTo, fn ($query) => $query->where('transaction_date', '>=', $dateFrom))
            ->when($dateTo && ! $dateFrom, fn ($query) => $query->where('transaction_date', '<=', $dateTo))
            ->when($accountId, fn ($query) => $query->forAccount((int) $accountId))
            ->when($campaignId, function ($query) use ($campaignId) {
                $query->where(function ($builder) use ($campaignId) {
                    $builder
                        ->whereHas('campaignExpense', fn ($q) => $q->where('campaign_id', $campaignId))
                        ->orWhereHas('campaigns', fn ($q) => $q->where('campaigns.id', $campaignId))
                        ->orWhereHas('donation', fn ($q) => $q->where('campaign_id', $campaignId));
                });
            });
    }

    /**
     * @return array{
     *     original_currency_id: int|null,
     *     original_amount: float|null,
     *     exchange_rate: float|null,
     *     gross_amount: float,
     *     fee_amount: float,
     *     net_amount: float
     * }
     */
    /**
     * @param  array<int, array{currency_code?: mixed, equivalent?: mixed}>|null  $rows
     * @return array<int, array{currency_code: string, equivalent: float}>|null
     */
    private function normalizeCurrencyEquivalents(?array $rows): ?array
    {
        if ($rows === null || $rows === []) {
            return null;
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = strtoupper(trim((string) ($row['currency_code'] ?? '')));
            $equivalent = $row['equivalent'] ?? null;

            if ($code === '' || ! is_numeric($equivalent) || (float) $equivalent <= 0) {
                continue;
            }

            $normalized[$code] = [
                'currency_code' => $code,
                'equivalent' => round((float) $equivalent, 8),
            ];
        }

        if ($normalized === []) {
            return null;
        }

        return array_values($normalized);
    }

    private function resolveFxAmounts(
        BankAccount $account,
        float $grossAmount,
        float $feeAmount,
        ?int $originalCurrencyId,
        ?float $originalAmount,
        ?float $exchangeRate,
    ): array {
        $accountCurrencyId = (int) $account->currency_id;
        $feeAmount = round($feeAmount, 2);

        if ($originalCurrencyId === null || $originalCurrencyId === $accountCurrencyId) {
            $gross = round($originalAmount ?? $grossAmount, 2);

            return [
                'original_currency_id' => $accountCurrencyId,
                'original_amount' => $gross,
                'exchange_rate' => 1.0,
                'gross_amount' => $gross,
                'fee_amount' => $feeAmount,
                'net_amount' => round($gross - $feeAmount, 2),
            ];
        }

        $original = round($originalAmount ?? $grossAmount, 2);
        $rate = round($exchangeRate ?? 1, 8);
        $convertedGross = round($original * $rate, 2);

        return [
            'original_currency_id' => $originalCurrencyId,
            'original_amount' => $original,
            'exchange_rate' => $rate,
            'gross_amount' => $convertedGross,
            'fee_amount' => $feeAmount,
            'net_amount' => round($convertedGross - $feeAmount, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $transfer
     */
    private function syncTransferDetail(Transaction $transaction, array $transfer, float $ledgerAmount): Transfer
    {
        [$recipientType, $recipientId, $recipientLabel] = $this->resolveRecipient($transfer);

        $payload = [
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'recipient_label' => $recipientLabel,
            'recipient_phone' => $transfer['recipient_phone'] ?? null,
            'amount' => $ledgerAmount,
            'transfer_date' => $transfer['transfer_date'] ?? $transaction->transaction_date?->toDateString(),
            'purpose' => $transfer['purpose'],
            'notes' => $transfer['notes'] ?? $transaction->notes,
            'created_by' => $transaction->created_by,
        ];

        $existing = $transaction->transfer()->withTrashed()->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->update($payload);

            return $existing->fresh(['recipient']);
        }

        return Transfer::create([
            'transaction_id' => $transaction->id,
            ...$payload,
        ])->fresh(['recipient']);
    }

    /**
     * @param  array<string, mixed>  $transfer
     * @return array{0: ?string, 1: ?int, 2: ?string}
     */
    private function resolveRecipient(array $transfer): array
    {
        $kind = $transfer['recipient_kind'] ?? null;

        return match ($kind) {
            'user' => [User::class, (int) $transfer['recipient_id'], null],
            'beneficiary' => [Beneficiary::class, (int) $transfer['recipient_id'], null],
            default => [null, null, $transfer['recipient_label'] ?? null],
        };
    }

    /**
     * @param  array<int, int>  $beneficiaryIds
     * @param  array<int, int>  $campaignIds
     * @param  array<int, int>  $meetingIds
     * @param  array<int, int>  $generalExpenseIds
     */
    private function syncTransactionLinks(
        Transaction $transaction,
        array $beneficiaryIds,
        array $campaignIds,
        array $meetingIds,
        array $generalExpenseIds,
    ): void {
        $transaction->beneficiaries()->sync(array_values(array_unique(array_map('intval', $beneficiaryIds))));
        $transaction->campaigns()->sync(array_values(array_unique(array_map('intval', $campaignIds))));
        $transaction->meetings()->sync(array_values(array_unique(array_map('intval', $meetingIds))));
        $transaction->generalExpenses()->sync(array_values(array_unique(array_map('intval', $generalExpenseIds))));
    }

    /**
     * @param  array<string, mixed>  $transfer
     */
    private function buildTransferDescription(array $transfer, ?string $fallback): string
    {
        $purpose = $transfer['purpose'] ?? 'Transfer';
        $kind = $transfer['recipient_kind'] ?? 'other';

        $name = match ($kind) {
            'user' => User::query()->find($transfer['recipient_id'] ?? null)?->name,
            'beneficiary' => Beneficiary::query()->find($transfer['recipient_id'] ?? null)?->display_name
                ?? Beneficiary::query()->find($transfer['recipient_id'] ?? null)?->displayName,
            default => $transfer['recipient_label'] ?? 'recipient',
        };

        return $fallback ?: "Transfer to {$name}: {$purpose}";
    }

    /**
     * @param  array<int, UploadedFile>  $documents
     */
    private function attachDocuments(Transaction $transaction, array $documents): void
    {
        foreach ($documents as $document) {
            if ($document instanceof UploadedFile) {
                $transaction->addMedia($document)->toMediaCollection('receipts');
            }
        }
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function removeDocuments(Transaction $transaction, array $ids): void
    {
        foreach ($ids as $id) {
            $media = $transaction->media()->where('id', $id)->first();
            $media?->delete();
        }
    }

    private function applyBalances(
        BankAccount $account,
        Transaction $transaction,
        ?float $balanceBefore,
        ?float $runningBalance,
    ): void {
        $computedBefore = $this->resolveBalanceBefore($account, $transaction->id);
        $before = $balanceBefore !== null
            ? round($balanceBefore, 2)
            : (float) $computedBefore;

        $after = $runningBalance !== null
            ? round($runningBalance, 2)
            : (float) ($transaction->direction === TransactionDirection::In
                ? bcadd((string) $before, (string) $transaction->net_amount, 2)
                : bcsub((string) $before, (string) $transaction->net_amount, 2));

        $transaction->update([
            'balance_before' => $before,
            'running_balance' => $after,
        ]);
    }

    private function applyBalanceOverrides(
        Transaction $transaction,
        ?float $balanceBefore,
        ?float $runningBalance,
    ): void {
        $attributes = [];

        if ($balanceBefore !== null) {
            $attributes['balance_before'] = round($balanceBefore, 2);
        }

        if ($runningBalance !== null) {
            $attributes['running_balance'] = round($runningBalance, 2);
        }

        if ($attributes !== []) {
            $transaction->update($attributes);
        }
    }

    private function applyRunningBalance(BankAccount $account, Transaction $transaction): void
    {
        $this->applyBalances($account, $transaction, null, null);
    }

    public function calculateMonthBalances(string $month): void
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        DB::transaction(function () use ($start, $end) {
            $transactions = Transaction::query()
                ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('account_id')
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->groupBy('account_id');

            foreach ($transactions as $accountTransactions) {
                /** @var Collection<int, Transaction> $accountTransactions */
                $first = $accountTransactions->first();

                if ($first === null) {
                    continue;
                }

                if ($first->running_balance === null) {
                    $date = $first->transaction_date?->toDateString() ?? (string) $first->transaction_date;

                    throw new InvalidArgumentException(
                        "Set {$date} balance to calculate based on it",
                    );
                }

                $balance = (string) $first->running_balance;

                foreach ($accountTransactions->slice(1) as $transaction) {
                    $before = $balance;
                    $balance = $transaction->direction === TransactionDirection::In
                        ? bcadd($balance, (string) $transaction->net_amount, 2)
                        : bcsub($balance, (string) $transaction->net_amount, 2);

                    $transaction->update([
                        'balance_before' => $before,
                        'running_balance' => $balance,
                    ]);
                }
            }
        });
    }

    private function recalculateAccountBalances(BankAccount $account): void
    {
        $balance = (string) $account->opening_balance;

        $transactions = Transaction::query()
            ->where('account_id', $account->id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $before = $balance;
            $balance = $transaction->direction === TransactionDirection::In
                ? bcadd($balance, (string) $transaction->net_amount, 2)
                : bcsub($balance, (string) $transaction->net_amount, 2);

            $transaction->update([
                'balance_before' => $before,
                'running_balance' => $balance,
            ]);
        }
    }

    private function resolveBalanceBefore(BankAccount $account, ?int $excludeTransactionId = null): string
    {
        $lastBalance = Transaction::query()
            ->where('account_id', $account->id)
            ->when($excludeTransactionId, fn ($query) => $query->where('id', '!=', $excludeTransactionId))
            ->whereNotNull('running_balance')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->value('running_balance');

        return (string) ($lastBalance ?? $account->opening_balance);
    }

    private function computeRunningBalance(
        BankAccount $account,
        TransactionDirection $direction,
        string $netAmount,
        ?int $excludeTransactionId = null,
    ): string {
        $balance = $this->resolveBalanceBefore($account, $excludeTransactionId);

        return $direction === TransactionDirection::In
            ? bcadd($balance, $netAmount, 2)
            : bcsub($balance, $netAmount, 2);
    }
}
