<?php

namespace App\Services;

use App\Contracts\Services\CurrencyServiceInterface;
use App\DTOs\CreateCurrencyDTO;
use App\DTOs\UpdateCurrencyDTO;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CurrencyService implements CurrencyServiceInterface
{
    public function getPaginatedCurrencies(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $status = $filters['status'] ?? null;

        return Currency::query()
            ->withCount(['accounts', 'transactions'])
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%")
                        ->orWhere('symbol', 'like', "%{$query}%");
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
            ->orderBy('code')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createCurrency(CreateCurrencyDTO $dto): Currency
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->isDefault) {
                Currency::query()->where('is_default', true)->update(['is_default' => false]);
            }

            return Currency::create([
                'code' => strtoupper($dto->code),
                'name' => $dto->name,
                'symbol' => $dto->symbol,
                'is_active' => $dto->isActive,
                'is_default' => $dto->isDefault,
            ]);
        });
    }

    public function updateCurrency(Currency $currency, UpdateCurrencyDTO $dto): Currency
    {
        return DB::transaction(function () use ($currency, $dto) {
            if ($dto->isDefault) {
                Currency::query()
                    ->where('is_default', true)
                    ->whereKeyNot($currency->id)
                    ->update(['is_default' => false]);
            }

            $currency->update([
                'code' => strtoupper($dto->code),
                'name' => $dto->name,
                'symbol' => $dto->symbol,
                'is_active' => $dto->isActive,
                'is_default' => $dto->isDefault,
            ]);

            return $currency->fresh();
        });
    }

    public function deleteCurrency(Currency $currency): void
    {
        if ($this->isReferenced($currency)) {
            throw new RuntimeException(__('Currency is in use and cannot be deleted.'));
        }

        $currency->delete();
    }

    private function isReferenced(Currency $currency): bool
    {
        if (BankAccount::query()->where('currency_id', $currency->id)->exists()) {
            return true;
        }

        return Transaction::query()
            ->where(function ($query) use ($currency) {
                $query->where('currency_id', $currency->id)
                    ->orWhere('original_currency_id', $currency->id);
            })
            ->exists();
    }
}
