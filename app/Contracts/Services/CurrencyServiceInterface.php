<?php

namespace App\Contracts\Services;

use App\DTOs\CreateCurrencyDTO;
use App\DTOs\UpdateCurrencyDTO;
use App\Models\Currency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

interface CurrencyServiceInterface
{
    public function getPaginatedCurrencies(array $filters): LengthAwarePaginator;

    public function createCurrency(CreateCurrencyDTO $dto): Currency;

    public function updateCurrency(Currency $currency, UpdateCurrencyDTO $dto): Currency;

    /**
     * @throws RuntimeException when the currency is still referenced
     */
    public function deleteCurrency(Currency $currency): void;
}
