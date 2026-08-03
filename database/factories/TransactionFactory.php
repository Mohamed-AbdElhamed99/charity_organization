<?php

namespace Database\Factories;

use App\Enums\TransactionDirection;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = $this->resolveType();
        $direction = $type->isIncome()
            ? TransactionDirection::In
            : TransactionDirection::Out;

        $gross = fake()->randomFloat(2, 10, 10_000);
        $fee = $type->isDonation()
            ? round($gross * fake()->randomFloat(4, 0.022, 0.035) + 0.30, 2)
            : 0;
        $net = round($gross - $fee, 2);

        return [
            'account_id' => BankAccount::inRandomOrder()->value('id') ?? BankAccount::factory(),
            'transaction_type_id' => $type->id,
            'direction' => $direction,
            'currency_id' => Currency::inRandomOrder()->value('id') ?? Currency::factory(),
            'gross_amount' => $gross,
            'fee_amount' => $fee,
            'net_amount' => $net,
            'running_balance' => null,
            'transaction_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'reference_number' => fake()->optional(0.5)->bothify('REF-#####-????'),
            'description' => fake()->sentence(6),
            'notes' => fake()->optional(0.3)->sentence(),
            'payment_method_id' => PaymentMethod::inRandomOrder()->value('id'),
            'created_by' => User::inRandomOrder()->value('id') ?? User::factory(),
            'is_reconciled' => fake()->boolean(30),
        ];
    }

    public function donation(): static
    {
        $this->ensureSystemTypes();

        return $this->state(fn () => [
            'transaction_type_id' => TransactionType::idForCode(TransactionType::CODE_DONATION),
            'direction' => TransactionDirection::In,
        ]);
    }

    public function campaignExpense(): static
    {
        $this->ensureSystemTypes();

        return $this->state(fn () => [
            'transaction_type_id' => TransactionType::idForCode(TransactionType::CODE_CAMPAIGN_EXPENSE),
            'direction' => TransactionDirection::Out,
            'fee_amount' => 0,
        ]);
    }

    public function generalExpense(): static
    {
        $this->ensureSystemTypes();

        return $this->state(fn () => [
            'transaction_type_id' => TransactionType::idForCode(TransactionType::CODE_GENERAL_EXPENSE),
            'direction' => TransactionDirection::Out,
            'fee_amount' => 0,
        ]);
    }

    public function transfer(): static
    {
        $this->ensureSystemTypes();

        return $this->state(fn () => [
            'transaction_type_id' => TransactionType::idForCode(TransactionType::CODE_TRANSFER),
            'direction' => TransactionDirection::Out,
            'fee_amount' => 0,
        ]);
    }

    public function reconciled(): static
    {
        return $this->state(fn () => ['is_reconciled' => true]);
    }

    private function resolveType(): TransactionType
    {
        $this->ensureSystemTypes();

        return TransactionType::query()->inRandomOrder()->firstOrFail();
    }

    private function ensureSystemTypes(): void
    {
        foreach (TransactionType::systemDefinitions() as $definition) {
            TransactionType::query()->firstOrCreate(
                ['code' => $definition['code']],
                $definition,
            );
        }
    }
}
