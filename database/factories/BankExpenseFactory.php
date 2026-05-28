<?php

namespace Database\Factories;

use App\Models\BankExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankExpense>
 */
class BankExpenseFactory extends Factory
{
    protected $model = BankExpense::class;

    public function definition(): array
    {
        return [
            'transaction_id' => TransactionFactory::new()->create(['transaction_type' => \App\Enums\TransactionType::BankTransfer, 'direction' => \App\Enums\TransactionDirection::Out])->id,
            'description'    => fake()->randomElement([
                'Monthly service fee', 'Wire transfer charge', 'Returned cheque fee',
                'International transaction fee', 'Account maintenance fee', 'Overdraft fee',
            ]),
            'amount'         => fake()->randomFloat(2, 5, 200),
            'expense_date'   => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'notes'          => fake()->optional(0.2)->sentence(),
            'created_by'     => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }
}
