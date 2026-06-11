<?php

namespace App\Services;

use App\Contracts\Services\BeneficiaryServiceInterface;
use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamily;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BeneficiaryService implements BeneficiaryServiceInterface
{
    public function getPaginatedBeneficiaries(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $type = $filters['type'] ?? null;
        $status = $filters['status'] ?? null;
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        $allowedSorts = ['created_at', 'code', 'status', 'type'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        return Beneficiary::query()
            ->with([
                'individual:id,beneficiary_id,first_name,middle_name,last_name,phone',
                'family:id,beneficiary_id,household_name,phone',
                'organization:id,beneficiary_id,name,phone,contact_phone',
            ])
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('code', 'like', "%{$query}%")
                        ->orWhereHas('individual', function ($individual) use ($query) {
                            $individual->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('national_id', 'like', "%{$query}%");
                        })
                        ->orWhereHas('family', function ($family) use ($query) {
                            $family->where('household_name', 'like', "%{$query}%")
                                ->orWhere('national_id', 'like', "%{$query}%");
                        })
                        ->orWhereHas('organization', function ($organization) use ($query) {
                            $organization->where('name', 'like', "%{$query}%");
                        });
                });
            })
            ->when($type, function ($builder) use ($type) {
                $types = is_array($type) ? $type : [$type];
                $builder->whereIn('type', $types);
            })
            ->when($status, function ($builder) use ($status) {
                $statuses = is_array($status) ? $status : [$status];
                $builder->whereIn('status', $statuses);
            })
            ->orderBy($sort, $direction)
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createBeneficiary(array $data, User $creator): Beneficiary
    {
        return DB::transaction(function () use ($data, $creator) {
            $type = BeneficiaryType::from($data['type']);

            $beneficiary = Beneficiary::create([
                'type' => $type,
                'code' => $data['code'] ?? $this->generateCode(),
                'status' => BeneficiaryStatus::from($data['status'] ?? BeneficiaryStatus::PendingAssessment->value),
                'notes' => $data['notes'] ?? null,
                'created_by' => $creator->id,
            ]);

            $this->createDetailProfile($beneficiary, $type, $data);

            return $beneficiary->fresh([
                'individual.country',
                'individual.state',
                'family.country',
                'family.state',
                'family.members',
                'organization.country',
                'organization.state',
                'creator',
            ]);
        });
    }

    public function updateBeneficiary(Beneficiary $beneficiary, array $data): Beneficiary
    {
        return DB::transaction(function () use ($beneficiary, $data) {
            if (isset($data['type']) && BeneficiaryType::from($data['type']) !== $beneficiary->type) {
                throw new DomainException(__('Beneficiary type cannot be changed after creation.'));
            }

            $beneficiary->update([
                'status' => isset($data['status'])
                    ? BeneficiaryStatus::from($data['status'])
                    : $beneficiary->status,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $beneficiary->notes,
            ]);

            $this->updateDetailProfile($beneficiary, $data);

            return $beneficiary->fresh([
                'individual.country',
                'individual.state',
                'family.country',
                'family.state',
                'family.members',
                'organization.country',
                'organization.state',
                'creator',
            ]);
        });
    }

    public function updateStatus(Beneficiary $beneficiary, BeneficiaryStatus $status): Beneficiary
    {
        $beneficiary->update(['status' => $status]);

        return $beneficiary->fresh();
    }

    public function deleteBeneficiary(Beneficiary $beneficiary): void
    {
        $beneficiary->delete();
    }

    public function generateCode(): string
    {
        $year = now()->year;
        $prefix = "BEN-{$year}-";

        $lastCode = Beneficiary::withTrashed()
            ->where('code', 'like', "{$prefix}%")
            ->orderByDesc('code')
            ->value('code');

        $sequence = 1;

        if ($lastCode !== null && preg_match('/-(\d+)$/', $lastCode, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDetailProfile(Beneficiary $beneficiary, BeneficiaryType $type, array $data): void
    {
        match ($type) {
            BeneficiaryType::Individual => $beneficiary->individual()->create(
                $this->normalizeIndividualPayload($data['individual'] ?? [])
            ),
            BeneficiaryType::Family => $this->createFamilyProfile($beneficiary, $data['family'] ?? []),
            BeneficiaryType::Organization => $beneficiary->organization()->create(
                $this->normalizeOrganizationPayload($data['organization'] ?? [])
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateDetailProfile(Beneficiary $beneficiary, array $data): void
    {
        match ($beneficiary->type) {
            BeneficiaryType::Individual => $beneficiary->individual()->updateOrCreate(
                ['beneficiary_id' => $beneficiary->id],
                $this->normalizeIndividualPayload($data['individual'] ?? [])
            ),
            BeneficiaryType::Family => $this->updateFamilyProfile($beneficiary, $data['family'] ?? []),
            BeneficiaryType::Organization => $beneficiary->organization()->updateOrCreate(
                ['beneficiary_id' => $beneficiary->id],
                $this->normalizeOrganizationPayload($data['organization'] ?? [])
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createFamilyProfile(Beneficiary $beneficiary, array $payload): BeneficiaryFamily
    {
        $members = $payload['members'] ?? [];
        unset($payload['members']);

        $family = $beneficiary->family()->create(
            $this->normalizeFamilyPayload($payload, count($members))
        );

        foreach ($members as $member) {
            $family->members()->create($this->normalizeMemberPayload($member));
        }

        return $family;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateFamilyProfile(Beneficiary $beneficiary, array $payload): void
    {
        $members = $payload['members'] ?? null;
        unset($payload['members']);

        $family = $beneficiary->family()->updateOrCreate(
            ['beneficiary_id' => $beneficiary->id],
            $this->normalizeFamilyPayload(
                $payload,
                is_array($members) ? count($members) : ($beneficiary->family?->members()->count() ?? 1)
            )
        );

        if (is_array($members)) {
            $this->syncFamilyMembers($family, $members);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $members
     */
    private function syncFamilyMembers(BeneficiaryFamily $family, array $members): void
    {
        $incomingIds = collect($members)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $family->members()
            ->when($incomingIds !== [], fn ($query) => $query->whereNotIn('id', $incomingIds))
            ->when($incomingIds === [], fn ($query) => $query)
            ->delete();

        foreach ($members as $memberData) {
            $normalized = $this->normalizeMemberPayload($memberData);

            if (! empty($memberData['id'])) {
                $family->members()
                    ->where('id', $memberData['id'])
                    ->update($normalized);
            } else {
                $family->members()->create($normalized);
            }
        }

        $family->update(['total_members' => $family->members()->count()]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeIndividualPayload(array $payload): array
    {
        return $this->normalizeMoneyFields($payload, ['monthly_income']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeFamilyPayload(array $payload, int $memberCount): array
    {
        $payload['total_members'] = $payload['total_members'] ?? max(1, $memberCount);

        return $this->normalizeMoneyFields($payload, ['monthly_income', 'monthly_rent']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeMemberPayload(array $payload): array
    {
        return $this->normalizeMoneyFields($payload, ['monthly_income']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeOrganizationPayload(array $payload): array
    {
        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $moneyFields
     * @return array<string, mixed>
     */
    private function normalizeMoneyFields(array $payload, array $moneyFields): array
    {
        foreach ($moneyFields as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null && $payload[$field] !== '') {
                $payload[$field] = round((float) $payload[$field], 2);
            }
        }

        return $payload;
    }
}
