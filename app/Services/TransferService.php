<?php

namespace App\Services;

use App\Contracts\Services\TransactionServiceInterface;
use App\Contracts\Services\TransferServiceInterface;
use App\DTOs\CreateTransactionDTO;
use App\DTOs\CreateTransferDTO;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Enums\TransferRecipientType;
use App\Models\BankAccount;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated Prefer creating transfer-type transactions via TransactionService.
 */
class TransferService implements TransferServiceInterface
{
    public function __construct(
        private readonly TransactionServiceInterface $transactionService,
    ) {}

    public function getPaginatedTransfers(array $filters): LengthAwarePaginator
    {
        $recipientType = $filters['recipient_type'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $campaignId = $filters['campaign_id'] ?? null;
        $query = $filters['query'] ?? null;

        return Transfer::query()
            ->with(['transaction.account', 'transaction.currency', 'campaign', 'recipient', 'creator'])
            ->when($recipientType === 'user', fn ($builder) => $builder->where('recipient_type', User::class))
            ->when($recipientType === 'beneficiary', fn ($builder) => $builder->toBeneficiaries())
            ->when($recipientType === 'other', fn ($builder) => $builder->whereNull('recipient_type')->whereNotNull('recipient_label'))
            ->when($dateFrom && $dateTo, fn ($builder) => $builder->inDateRange($dateFrom, $dateTo))
            ->when($dateFrom && ! $dateTo, fn ($builder) => $builder->where('transfer_date', '>=', $dateFrom))
            ->when($dateTo && ! $dateFrom, fn ($builder) => $builder->where('transfer_date', '<=', $dateTo))
            ->when($campaignId, fn ($builder) => $builder->forCampaign((int) $campaignId))
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('recipient_label', 'like', "%{$query}%")
                        ->orWhere('purpose', 'like', "%{$query}%");
                });
            })
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createTransfer(CreateTransferDTO $dto): Transfer
    {
        return DB::transaction(function () use ($dto) {
            $account = $this->resolveAccount($dto->accountId);
            $amount = round($dto->amount, 2);

            $recipientKind = match ($dto->recipientType) {
                TransferRecipientType::User => 'user',
                TransferRecipientType::Beneficiary => 'beneficiary',
                default => 'other',
            };

            $transaction = $this->transactionService->createTransaction(new CreateTransactionDTO(
                accountId: $account->id,
                transactionType: TransactionType::Transfer,
                direction: TransactionDirection::Out,
                grossAmount: $amount,
                feeAmount: 0,
                transactionDate: $dto->transferDate,
                referenceNumber: $dto->referenceNumber,
                description: "Transfer to {$dto->recipientName}: {$dto->purpose}",
                notes: $dto->notes,
                paymentMethodId: $dto->paymentMethodId,
                createdBy: (int) Auth::id(),
                transfer: [
                    'recipient_kind' => $recipientKind,
                    'recipient_id' => $dto->userId ?? $dto->beneficiaryId,
                    'recipient_label' => $recipientKind === 'other' ? $dto->recipientName : null,
                    'recipient_phone' => $dto->recipientPhone,
                    'purpose' => $dto->purpose,
                    'campaign_id' => $dto->campaignId,
                    'transfer_date' => $dto->transferDate,
                    'notes' => $dto->notes,
                ],
            ));

            return $transaction->transfer->fresh(['transaction', 'campaign', 'recipient']);
        });
    }

    private function resolveAccount(?int $accountId): BankAccount
    {
        if ($accountId !== null) {
            return BankAccount::query()
                ->active()
                ->findOrFail($accountId);
        }

        return BankAccount::query()
            ->active()
            ->orderBy('id')
            ->firstOrFail();
    }
}
