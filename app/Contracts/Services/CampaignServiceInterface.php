<?php

namespace App\Contracts\Services;

use App\DTOs\CreateCampaignDTO;
use App\DTOs\UpdateCampaignDTO;
use App\Models\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CampaignServiceInterface
{
    public function getPaginatedCampaigns(array $filters): LengthAwarePaginator;

    public function createCampaign(CreateCampaignDTO $dto): Campaign;

    public function updateCampaign(Campaign $campaign, UpdateCampaignDTO $dto): Campaign;

    public function deleteCampaign(Campaign $campaign): void;
}
