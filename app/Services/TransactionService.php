<?php

namespace App\Services;

use App\Contracts\Services\TransactionServiceInterface;
use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\BankAccount;
use App\Models\Beneficiary;
use App\Models\CampaignExpense;
use App\Models\GeneralExpense;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
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

            $direction = $dto->transactionType === TransactionType::Transfer
                ? TransactionDirection::Out
                : $dto->direction;

            $description = $dto->description;
            if ($dto->transactionType === TransactionType::Transfer && $dto->transfer !== null) {
                $description = $this->buildTransferDescription($dto->transfer, $description);
            }

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'transaction_type' => $dto->transactionType,
                'direction' => $direction,
                'currency_id' => $account->currency_id,
                'original_currency_id' => $fx['original_currency_id'],
                'gross_amount' => $fx['gross_amount'],
                'fee_amount' => $fx['fee_amount'],
                'net_amount' => $fx['net_amount'],
                'original_amount' => $fx['original_amount'],
                'exchange_rate' => $fx['exchange_rate'],
                'transaction_date' => $dto->transactionDate,
                'reference_number' => $dto->referenceNumber,
                'description' => $description ?? '',
                'notes' => $dto->notes,
                'payment_method_id' => $dto->paymentMethodId,
                'created_by' => $dto->createdBy,
            ]);

            $this->applyRunningBalance($account, $transaction);

            if ($dto->transactionType === TransactionType::Transfer && $dto->transfer !== null) {
                $this->syncTransferDetail($transaction, $dto->transfer, $fx['net_amount']);
            }

            $this->attachDocuments($transaction, $dto->documents ?? []);

            return $transaction->fresh([
                'account',
                'currency',
                'originalCurrency',
                'paymentMethod',
                'creator',
                'transfer.recipient',
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

            $direction = $dto->transactionType === TransactionType::Transfer
                ? TransactionDirection::Out
                : $dto->direction;

            $description = $dto->description;
            if ($dto->transactionType === TransactionType::Transfer && $dto->transfer !== null) {
                $description = $this->buildTransferDescription($dto->transfer, $description);
            }

            $transaction->update([
                'account_id' => $dto->accountId,
                'transaction_type' => $dto->transactionType,
                'direction' => $direction,
                'currency_id' => $account->currency_id,
                'original_currency_id' => $fx['original_currency_id'],
                'gross_amount' => $fx['gross_amount'],
                'fee_amount' => $fx['fee_amount'],
                'net_amount' => $fx['net_amount'],
                'original_amount' => $fx['original_amount'],
                'exchange_rate' => $fx['exchange_rate'],
                'transaction_date' => $dto->transactionDate,
                'reference_number' => $dto->referenceNumber,
                'description' => $description ?? '',
                'notes' => $dto->notes,
                'payment_method_id' => $dto->paymentMethodId,
            ]);

            if ($dto->transactionType === TransactionType::Transfer) {
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

            return $transaction->fresh([
                'account',
                'currency',
                'originalCurrency',
                'paymentMethod',
                'creator',
                'transfer.recipient',
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
                'transaction_type' => TransactionType::CampaignExpense,
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
                'transaction_type' => TransactionType::GeneralExpense,
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

            GeneralExpense::create([
                'transaction_id' => $transaction->id,
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'amount' => $fx['net_amount'],
                'expense_date' => $data['expense_date'],
                'vendor_name' => $data['vendor_name'] ?? null,
                'is_recurring' => $data['is_recurring'] ?? false,
                'created_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            return $transaction->fresh(['account', 'currency', 'originalCurrency', 'paymentMethod', 'generalExpense']);
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
                'transaction_type' => TransactionType::Transfer,
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
        if ($transaction->transaction_type === TransactionType::Adjustment) {
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
                'transaction_type' => TransactionType::Adjustment,
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

            if ($transaction->transaction_type === TransactionType::Transfer) {
                $transaction->transfer?->delete();
            }

            return $reversal->fresh(['account', 'currency', 'originalCurrency']);
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
            ->with(['account', 'currency', 'originalCurrency', 'paymentMethod', 'creator'])
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
            'campaign_id' => $transfer['campaign_id'] ?? null,
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

    private function applyRunningBalance(BankAccount $account, Transaction $transaction): void
    {
        $runningBalance = $this->computeRunningBalance(
            $account,
            $transaction->direction,
            (string) $transaction->net_amount,
            $transaction->id,
        );

        $transaction->update(['running_balance' => $runningBalance]);
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
            $balance = $transaction->direction === TransactionDirection::In
                ? bcadd($balance, (string) $transaction->net_amount, 2)
                : bcsub($balance, (string) $transaction->net_amount, 2);

            $transaction->update(['running_balance' => $balance]);
        }
    }

    private function computeRunningBalance(
        BankAccount $account,
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
