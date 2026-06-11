<?php

namespace App\Services;

use App\Contracts\Services\DonorProfileServiceInterface;
use App\DTOs\CreateDonorProfileDTO;
use App\DTOs\UpdateDonorProfileDTO;
use App\Enums\DonorType;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DonorProfileService implements DonorProfileServiceInterface
{
    public function getPaginatedDonorProfiles(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $type = $filters['type'] ?? null;

        return DonorProfile::query()
            ->with(['user', 'country', 'state'])
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('organization_name', 'like', "%{$query}%")
                        ->orWhere('address', 'like', "%{$query}%")
                        ->orWhereHas('user', function ($uq) use ($query) {
                            $uq->where('name', 'like', "%{$query}%")
                                ->orWhere('email', 'like', "%{$query}%");
                        });
                });
            })
            ->when($type, function ($builder) use ($type) {
                $types = is_array($type) ? $type : [$type];
                $builder->whereIn('type', $types);
            })
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createDonorProfile(CreateDonorProfileDTO $dto): DonorProfile
    {
        return DonorProfile::create([
            'user_id' => $dto->userId,
            'type' => DonorType::from($dto->type),
            'organization_name' => $dto->organizationName,
            'address' => $dto->address,
            'country_id' => $dto->countryId,
            'state_id' => $dto->stateId,
            'notes' => $dto->notes,
        ]);
    }

    public function updateDonorProfile(DonorProfile $donorProfile, UpdateDonorProfileDTO $dto): DonorProfile
    {
        $donorProfile->update([
            'type' => DonorType::from($dto->type),
            'organization_name' => $dto->organizationName,
            'address' => $dto->address,
            'country_id' => $dto->countryId,
            'state_id' => $dto->stateId,
            'notes' => $dto->notes,
        ]);

        return $donorProfile->fresh(['user', 'country', 'state']);
    }

    public function deleteDonorProfile(DonorProfile $donorProfile): void
    {
        $donorProfile->delete();
    }

    public function restoreDonorProfile(int|string $id): DonorProfile
    {
        $donorProfile = DonorProfile::withTrashed()->findOrFail($id);
        $donorProfile->restore();

        return $donorProfile->load(['user', 'country', 'state']);
    }

    public function getAvailableDonorUsers(): Collection
    {
        return User::query()
            ->role('donor')
            ->whereDoesntHave('donorProfile')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }
}
