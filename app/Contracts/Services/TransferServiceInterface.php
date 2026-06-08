<?php

namespace App\Contracts\Services;

use App\DTOs\CreateTransferDTO;
use App\Models\Transfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransferServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedTransfers(array $filters): LengthAwarePaginator;

    public function createTransfer(CreateTransferDTO $dto): Transfer;
}
