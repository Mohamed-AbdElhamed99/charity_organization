<?php

namespace Database\Factories;

use App\Models\AidItem;
use App\Models\BeneficiarySupport;
use App\Models\BeneficiarySupportItem;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiarySupportItem>
 */
class BeneficiarySupportItemFactory extends Factory
{
    protected $model = BeneficiarySupportItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitCost = fake()->numberBetween(100, 20000);

        return [
            'beneficiary_support_id' => BeneficiarySupport::factory(),
            'aid_item_id' => AidItem::factory(),
            'item_name_snapshot' => fake()->words(2, true),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'currency_id' => Currency::factory(),
            'campaign_expense_id' => null,
        ];
    }
}
