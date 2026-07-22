<?php

namespace App\Services;

use App\Contracts\Services\CampaignExpenseServiceInterface;
use App\Contracts\Services\TransactionServiceInterface;
use App\DTOs\CreateCampaignExpenseDTO;
use App\DTOs\UpdateCampaignExpenseDTO;
use App\Models\CampaignExpense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CampaignExpenseService implements CampaignExpenseServiceInterface
{
    public function __construct(
        private readonly TransactionServiceInterface $transactionService,
    ) {}

    public function getPaginatedExpenses(array $filters, ?int $campaignId = null): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        return CampaignExpense::query()
            ->with(['campaign', 'item', 'responsibleUser', 'transaction'])
            ->when($campaignId, fn ($builder) => $builder->forCampaign($campaignId))
            ->when($query, function ($builder) use ($query) {
                $builder->whereHas('item', function ($q) use ($query) {
                    $q->where('name_en', 'like', "%{$query}%")
                        ->orWhere('name_ar', 'like', "%{$query}%");
                });
            })
            ->when($dateFrom && $dateTo, fn ($builder) => $builder->inDateRange($dateFrom, $dateTo))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createExpense(CreateCampaignExpenseDTO $dto): CampaignExpense
    {
        $amount = round($dto->itemPrice * $dto->quantity, 2);

        $transaction = $this->transactionService->createForExpense([
            'account_id' => $dto->accountId,
            'campaign_id' => $dto->campaignId,
            'item_id' => $dto->itemId,
            'item_price' => $dto->itemPrice,
            'quantity' => $dto->quantity,
            'amount' => $amount,
            'expense_date' => $dto->expenseDate,
            'responsible_user_id' => $dto->responsibleUserId,
            'description' => $dto->description ?? 'Campaign expense',
            'expense_notes' => $dto->notes,
            'payment_method_id' => $dto->paymentMethodId,
            'reference_number' => $dto->referenceNumber,
            'original_currency_id' => $dto->originalCurrencyId,
            'original_amount' => $dto->originalAmount,
            'exchange_rate' => $dto->exchangeRate,
        ]);

        return $transaction->campaignExpense;
    }

    public function updateExpense(CampaignExpense $expense, UpdateCampaignExpenseDTO $dto): CampaignExpense
    {
        $expense->update([
            'notes' => $dto->notes,
            'residual_quantity' => $dto->residualQuantity,
            'residual_amount' => $dto->residualAmount,
        ]);

        return $expense->fresh(['campaign', 'item', 'responsibleUser', 'transaction']);
    }
}
