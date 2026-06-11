<?php

namespace App\Contracts\Services;

use App\DTOs\CreatePaymentMethodDTO;
use App\DTOs\UpdatePaymentMethodDTO;
use App\Models\PaymentMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentMethodServiceInterface
{
    public function getPaginatedPaymentMethods(array $filters): LengthAwarePaginator;

    public function createPaymentMethod(CreatePaymentMethodDTO $dto): PaymentMethod;

    public function updatePaymentMethod(PaymentMethod $paymentMethod, UpdatePaymentMethodDTO $dto): PaymentMethod;

    /**
     * @return 'deleted'|'deactivated'
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod): string;

    public function restorePaymentMethod(int|string $id): PaymentMethod;

    /**
     * @return array{deleted: int, deactivated: int}
     */
    public function bulkDelete(array $ids): array;
}
