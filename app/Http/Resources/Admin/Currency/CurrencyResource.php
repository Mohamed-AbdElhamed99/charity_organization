<?php

namespace App\Http\Resources\Admin\Currency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'accounts_count' => $this->whenCounted('accounts'),
            'transactions_count' => $this->whenCounted('transactions'),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
