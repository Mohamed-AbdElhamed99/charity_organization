<?php

namespace App\Services;

use App\Enums\SupportStatus;
use App\Models\BeneficiarySupport;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BeneficiarySupportService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createSupport(array $payload, User $actor): BeneficiarySupport
    {
        return DB::transaction(function () use ($payload, $actor) {
            $currencyId = Currency::default()?->id
                ?? Currency::query()->active()->value('id')
                ?? Currency::query()->value('id');

            $support = BeneficiarySupport::create([
                'beneficiary_id' => (int) $payload['beneficiary_id'],
                'campaign_id' => (int) $payload['campaign_id'],
                'supported_at' => $payload['supported_at'],
                'status' => $payload['status'] ?? SupportStatus::Delivered->value,
                'notes' => $payload['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($payload['items'] as $item) {
                $support->items()->create([
                    'aid_item_id' => $item['aid_item_id'] ?? null,
                    'item_name_snapshot' => $item['item_name_snapshot'],
                    'quantity' => (int) $item['quantity'],
                    'unit_cost' => (int) $item['unit_cost'],
                    'currency_id' => $currencyId,
                    'campaign_expense_id' => $item['campaign_expense_id'] ?? null,
                ]);
            }

            return $support->fresh([
                'beneficiary.individual',
                'beneficiary.family',
                'beneficiary.organization',
                'campaign',
                'creator',
                'items.aidItem',
                'items.campaignExpense',
            ]);
        });
    }
}
