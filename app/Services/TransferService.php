<?php

namespace App\Services;

use App\Contracts\Services\TransactionServiceInterface;
use App\Contracts\Services\TransferServiceInterface;
use App\DTOs\CreateTransferDTO;
use App\Models\Account;
use App\Models\Transfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            ->with(['transaction.account', 'transaction.currency', 'campaign', 'beneficiary', 'user', 'creator'])
            ->when($recipientType, fn ($builder) => $builder->where('recipient_type', $recipientType))
            ->when($dateFrom && $dateTo, fn ($builder) => $builder->inDateRange($dateFrom, $dateTo))
            ->when($dateFrom && ! $dateTo, fn ($builder) => $builder->where('transfer_date', '>=', $dateFrom))
            ->when($dateTo && ! $dateFrom, fn ($builder) => $builder->where('transfer_date', '<=', $dateTo))
            ->when($campaignId, fn ($builder) => $builder->forCampaign((int) $campaignId))
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('recipient_name', 'like', "%{$query}%")
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

            $transaction = $this->transactionService->createForTransfer([
                'account_id' => $account->id,
                'amount' => $amount,
                'transaction_date' => $dto->transferDate,
                'description' => "Transfer to {$dto->recipientName}: {$dto->purpose}",
                'notes' => $dto->notes,
                'payment_method_id' => $dto->paymentMethodId,
                'reference_number' => $dto->referenceNumber,
            ]);

            return Transfer::create([
                'transaction_id' => $transaction->id,
                'campaign_id' => $dto->campaignId,
                'recipient_type' => $dto->recipientType,
                'recipient_name' => $dto->recipientName,
                'recipient_phone' => $dto->recipientPhone,
                'beneficiary_id' => $dto->beneficiaryId,
                'user_id' => $dto->userId,
                'amount' => $amount,
                'transfer_date' => $dto->transferDate,
                'purpose' => $dto->purpose,
                'notes' => $dto->notes,
                'created_by' => Auth::id(),
            ])->fresh(['transaction', 'campaign', 'beneficiary', 'user']);
        });
    }

    private function resolveAccount(?int $accountId): Account
    {
        if ($accountId !== null) {
            return Account::query()
                ->active()
                ->findOrFail($accountId);
        }

        return Account::query()
            ->active()
            ->orderBy('id')
            ->firstOrFail();
    }
}
