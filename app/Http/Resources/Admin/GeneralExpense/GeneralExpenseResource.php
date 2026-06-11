<?php

namespace App\Http\Resources\Admin\GeneralExpense;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'name' => $this->name,
            'amount' => (float) $this->amount,
            'expense_date' => $this->expense_date?->format('Y-m-d'),
            'vendor_name' => $this->vendor_name,
            'is_recurring' => $this->is_recurring,
            'notes' => $this->notes,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
            'created_at' => $this->created_at?->toDateString(),
            'transaction' => $this->whenLoaded('transaction', function () {
                return [
                    'id' => $this->transaction->id,
                    'transaction_date' => $this->transaction->transaction_date?->format('Y-m-d'),
                    'net_amount' => (float) $this->transaction->net_amount,
                    'description' => $this->transaction->description,
                    'reference_number' => $this->transaction->reference_number,
                    'is_reconciled' => $this->transaction->is_reconciled,
                    'account_id' => $this->transaction->account_id,
                    'account_name' => $this->transaction->account?->name,
                    'payment_method_id' => $this->transaction->payment_method_id,
                    'payment_method_name' => $this->transaction->paymentMethod?->name,
                    'currency_symbol' => $this->transaction->currency?->symbol,
                ];
            }),
        ];
    }
}
