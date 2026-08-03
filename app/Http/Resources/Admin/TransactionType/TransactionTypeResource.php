<?php

namespace App\Http\Resources\Admin\TransactionType;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system,
            'transactions_count' => $this->whenCounted('transactions'),
            'created_at' => $this->created_at?->toDateString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
