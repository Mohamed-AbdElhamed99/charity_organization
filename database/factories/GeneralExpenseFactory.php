<?php

namespace Database\Factories;

use App\Models\GeneralExpense;
use App\Models\GeneralExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneralExpense>
 */
class GeneralExpenseFactory extends Factory
{
    protected $model = GeneralExpense::class;

    private const VENDORS = [
        'Zoom', 'Google Workspace', 'Zoho CRM', 'Gusto', 'Aplos',
        'Microsoft 365', 'Slack', 'Dropbox', 'Mailchimp', 'QuickBooks',
        'Adobe', 'Canva', 'AWS', 'Cloudflare', 'Stripe',
    ];

    public function definition(): array
    {
        $vendor = fake()->randomElement(static::VENDORS);

        return [
            'category_id' => GeneralExpenseCategory::inRandomOrder()->value('id'),
            'name' => $vendor.' '.fake()->randomElement(['Monthly Subscription', 'Annual Plan', 'Pro Plan', 'License']),
            'amount' => fake()->randomFloat(2, 10, 2_000),
            'expense_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'vendor_name' => $vendor,
            'is_recurring' => fake()->boolean(60),
            'created_by' => User::inRandomOrder()->value('id') ?? User::factory(),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (GeneralExpense $expense): void {
            if ($expense->transactions()->exists()) {
                return;
            }

            $transaction = TransactionFactory::new()->generalExpense()->create([
                'gross_amount' => $expense->amount,
                'net_amount' => $expense->amount,
                'transaction_date' => $expense->expense_date,
                'description' => $expense->name,
                'created_by' => $expense->created_by,
            ]);

            $expense->transactions()->attach($transaction->id);
        });
    }

    public function recurring(): static
    {
        return $this->state(fn () => ['is_recurring' => true]);
    }

    public function oneTime(): static
    {
        return $this->state(fn () => ['is_recurring' => false]);
    }
}
