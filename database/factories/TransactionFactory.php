<?php

namespace Database\Factories;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Transaction;
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
        $type = fake()->randomElement(TransactionType::cases());
        $direction = $type->isIncome()
            ? TransactionDirection::In
            : TransactionDirection::Out;

        $gross = fake()->randomFloat(2, 10, 10_000);
        // Only donation/Stripe has fees; others have 0
        $fee = $type === TransactionType::Donation
            ? round($gross * fake()->randomFloat(4, 0.022, 0.035) + 0.30, 2)
            : 0;
        $net = round($gross - $fee, 2);

        return [
            'account_id' => BankAccount::inRandomOrder()->value('id') ?? BankAccount::factory(),
            'transaction_type' => $type,
            'direction' => $direction,
            'currency_id' => Currency::inRandomOrder()->value('id') ?? Currency::factory(),
            'gross_amount' => $gross,
            'fee_amount' => $fee,
            'net_amount' => $net,
            'running_balance' => null, // computed by service on real insert
            'transaction_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'reference_number' => fake()->optional(0.5)->bothify('REF-#####-????'),
            'description' => fake()->sentence(6),
            'notes' => fake()->optional(0.3)->sentence(),
            'payment_method_id' => PaymentMethod::inRandomOrder()->value('id'),
            'created_by' => User::inRandomOrder()->value('id') ?? User::factory(),
            'is_reconciled' => fake()->boolean(30),
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function donation(): static
    {
        return $this->state(fn () => [
            'transaction_type' => TransactionType::Donation,
            'direction' => TransactionDirection::In,
        ]);
    }

    public function campaignExpense(): static
    {
        return $this->state(fn () => [
            'transaction_type' => TransactionType::CampaignExpense,
            'direction' => TransactionDirection::Out,
            'fee_amount' => 0,
        ]);
    }

    public function generalExpense(): static
    {
        return $this->state(fn () => [
            'transaction_type' => TransactionType::GeneralExpense,
            'direction' => TransactionDirection::Out,
            'fee_amount' => 0,
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn () => [
            'transaction_type' => TransactionType::Transfer,
            'direction' => TransactionDirection::Out,
            'fee_amount' => 0,
        ]);
    }

    public function reconciled(): static
    {
        return $this->state(fn () => ['is_reconciled' => true]);
    }
}
