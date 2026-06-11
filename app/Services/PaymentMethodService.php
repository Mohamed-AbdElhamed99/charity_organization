<?php

namespace App\Services;

use App\Contracts\Services\PaymentMethodServiceInterface;
use App\DTOs\CreatePaymentMethodDTO;
use App\DTOs\UpdatePaymentMethodDTO;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentMethodService implements PaymentMethodServiceInterface
{
    public function getPaginatedPaymentMethods(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;

        return PaymentMethod::query()
            ->withCount('transactions')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%");
                });
            })
            ->when($status, function ($builder) use ($status) {
                $statuses = is_array($status) ? $status : [$status];

                $builder->where(function ($q) use ($statuses) {
                    foreach ($statuses as $statusValue) {
                        match ($statusValue) {
                            'active' => $q->orWhere('is_active', true),
                            'inactive' => $q->orWhere('is_active', false),
                            default => null,
                        };
                    }
                });
            })
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createPaymentMethod(CreatePaymentMethodDTO $dto): PaymentMethod
    {
        return PaymentMethod::create([
            'name' => $dto->name,
            'code' => $dto->code,
            'is_active' => $dto->isActive,
        ]);
    }

    public function updatePaymentMethod(PaymentMethod $paymentMethod, UpdatePaymentMethodDTO $dto): PaymentMethod
    {
        $paymentMethod->update([
            'name' => $dto->name,
            'code' => $dto->code,
            'is_active' => $dto->isActive,
        ]);

        return $paymentMethod->fresh();
    }

    public function deletePaymentMethod(PaymentMethod $paymentMethod): string
    {
        if ($this->isReferenced($paymentMethod)) {
            $paymentMethod->update(['is_active' => false]);

            return 'deactivated';
        }

        $paymentMethod->delete();

        return 'deleted';
    }

    public function restorePaymentMethod(int|string $id): PaymentMethod
    {
        $paymentMethod = PaymentMethod::withTrashed()->findOrFail($id);
        $paymentMethod->restore();
        $paymentMethod->update(['is_active' => true]);

        return $paymentMethod;
    }

    public function bulkDelete(array $ids): array
    {
        $deleted = 0;
        $deactivated = 0;

        PaymentMethod::query()
            ->whereIn('id', $ids)
            ->each(function (PaymentMethod $paymentMethod) use (&$deleted, &$deactivated) {
                if ($this->deletePaymentMethod($paymentMethod) === 'deactivated') {
                    $deactivated++;
                } else {
                    $deleted++;
                }
            });

        return ['deleted' => $deleted, 'deactivated' => $deactivated];
    }

    private function isReferenced(PaymentMethod $paymentMethod): bool
    {
        return Transaction::query()
            ->where('payment_method_id', $paymentMethod->id)
            ->exists();
    }
}
