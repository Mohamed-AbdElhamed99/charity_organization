<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\BankAccount;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        $type = fake()->randomElement(AccountType::cases());

        return [
            'name' => match ($type) {
                AccountType::Bank => fake()->randomElement(['Chase Business Checking', 'Wells Fargo Savings', 'Bank of America Org Account', 'Citibank', 'HSBC Charity Account']),
                AccountType::Cash => 'Petty Cash - '.fake()->city(),
                AccountType::Digital => fake()->randomElement(['PayPal', 'Zelle', 'Venmo Business', 'Stripe Balance']),
            },
            'account_number' => $type === AccountType::Bank
                ? fake()->unique()->numerify('####-####-####-####')
                : null,
            'bank_name' => $type === AccountType::Bank
                ? fake()->randomElement(['Chase', 'Wells Fargo', 'Bank of America', 'Citibank', 'HSBC'])
                : null,
            'bank_branch' => $type === AccountType::Bank
                ? fake()->optional(0.5)->city()
                : null,
            'currency_id' => Currency::inRandomOrder()->value('id') ?? Currency::factory(),
            'type' => $type,
            'opening_balance' => fake()->randomFloat(2, 0, 50_000),
            'is_active' => true,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'type' => AccountType::Bank,
            'account_number' => fake()->unique()->numerify('####-####-####-####'),
            'bank_name' => fake()->randomElement(['Chase', 'Wells Fargo', 'Bank of America']),
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'type' => AccountType::Cash,
            'account_number' => null,
            'bank_name' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
