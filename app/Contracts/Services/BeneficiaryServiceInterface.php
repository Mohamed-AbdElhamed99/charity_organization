<?php

namespace App\Contracts\Services;

use App\Enums\BeneficiaryStatus;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BeneficiaryServiceInterface
{
    public function getPaginatedBeneficiaries(array $filters): LengthAwarePaginator;

    public function createBeneficiary(array $data, User $creator): Beneficiary;

    public function updateBeneficiary(Beneficiary $beneficiary, array $data): Beneficiary;

    public function updateStatus(Beneficiary $beneficiary, BeneficiaryStatus $status): Beneficiary;

    public function deleteBeneficiary(Beneficiary $beneficiary): void;

    public function generateCode(): string;
}
