<?php

namespace App\Services;

use App\Models\AidItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AidItemService
{
    public function paginate(): LengthAwarePaginator
    {
        return AidItem::query()
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->paginate(20);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): AidItem
    {
        return AidItem::create([
            'name' => $payload['name'],
            'unit' => $payload['unit'] ?? null,
            'default_unit_cost' => $payload['default_unit_cost'] ?? null,
            'category' => $payload['category'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(AidItem $aidItem, array $payload): AidItem
    {
        $aidItem->update([
            'name' => $payload['name'],
            'unit' => $payload['unit'] ?? null,
            'default_unit_cost' => $payload['default_unit_cost'] ?? null,
            'category' => $payload['category'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? false),
        ]);

        return $aidItem->refresh();
    }
}
