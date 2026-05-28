<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignExpense;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignExpense>
 */
class CampaignExpenseFactory extends Factory
{
    protected $model = CampaignExpense::class;

    public function definition(): array
    {
        $itemPrice = fake()->randomFloat(2, 5, 500);
        $quantity  = fake()->randomFloat(1, 1, 200);
        $amount    = round($itemPrice * $quantity, 2);

        // Residual: 0–50% of purchased quantity may remain
        $residualQty    = fake()->optional(0.4)->randomFloat(1, 0, $quantity * 0.5) ?? 0;
        $residualAmount = round($residualQty * $itemPrice, 2);

        return [
            'transaction_id'      => TransactionFactory::new()->campaignExpense()->create()->id,
            'campaign_id'         => Campaign::inRandomOrder()->value('id') ?? Campaign::factory(),
            'item_id'             => Item::inRandomOrder()->value('id') ?? Item::factory(),
            'item_price'          => $itemPrice,
            'quantity'            => $quantity,
            'amount'              => $amount,
            'residual_quantity'   => $residualQty,
            'residual_amount'     => $residualAmount,
            'responsible_user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'expense_date'        => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'notes'               => fake()->optional(0.3)->sentence(),
        ];
    }

    public function fullyDistributed(): static
    {
        return $this->state(fn () => [
            'residual_quantity' => 0,
            'residual_amount'   => 0,
        ]);
    }
}
