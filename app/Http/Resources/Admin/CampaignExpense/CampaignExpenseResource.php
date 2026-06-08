<?php

namespace App\Http\Resources\Admin\CampaignExpense;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'campaign_id' => $this->campaign_id,
            'campaign_name' => $this->campaign?->title_en,
            'item_id' => $this->item_id,
            'item_name' => $this->item?->name_en,
            'item_price' => (float) $this->item_price,
            'quantity' => (float) $this->quantity,
            'amount' => (float) $this->amount,
            'residual_quantity' => (float) $this->residual_quantity,
            'residual_amount' => (float) $this->residual_amount,
            'responsible_user_id' => $this->responsible_user_id,
            'responsible_user_name' => $this->responsibleUser?->name,
            'expense_date' => $this->expense_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
