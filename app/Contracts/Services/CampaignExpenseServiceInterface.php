<?php

namespace App\Contracts\Services;

use App\DTOs\CreateCampaignExpenseDTO;
use App\DTOs\UpdateCampaignExpenseDTO;
use App\Models\CampaignExpense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CampaignExpenseServiceInterface
{
    public function getPaginatedExpenses(array $filters, ?int $campaignId = null): LengthAwarePaginator;

    public function createExpense(CreateCampaignExpenseDTO $dto): CampaignExpense;

    public function updateExpense(CampaignExpense $expense, UpdateCampaignExpenseDTO $dto): CampaignExpense;
}
