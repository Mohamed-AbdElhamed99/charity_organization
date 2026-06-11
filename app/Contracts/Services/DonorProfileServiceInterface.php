<?php

namespace App\Contracts\Services;

use App\DTOs\CreateDonorProfileDTO;
use App\DTOs\UpdateDonorProfileDTO;
use App\Models\DonorProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DonorProfileServiceInterface
{
    public function getPaginatedDonorProfiles(array $filters): LengthAwarePaginator;

    public function createDonorProfile(CreateDonorProfileDTO $dto): DonorProfile;

    public function updateDonorProfile(DonorProfile $donorProfile, UpdateDonorProfileDTO $dto): DonorProfile;

    public function deleteDonorProfile(DonorProfile $donorProfile): void;

    public function restoreDonorProfile(int|string $id): DonorProfile;

    /**
     * @return Collection<int, array{id: int, name: string, email: string}>
     */
    public function getAvailableDonorUsers(): Collection;
}
