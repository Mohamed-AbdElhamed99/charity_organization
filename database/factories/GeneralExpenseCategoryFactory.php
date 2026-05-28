<?php

namespace Database\Factories;

use App\Models\GeneralExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneralExpenseCategory>
 */
class GeneralExpenseCategoryFactory extends Factory
{
    protected $model = GeneralExpenseCategory::class;

    private const CATEGORIES = [
        'Software & Subscriptions',
        'Office Supplies',
        'Utilities',
        'Salaries & Payroll',
        'Marketing & Outreach',
        'Travel & Transportation',
        'Legal & Compliance',
        'Insurance',
        'Telecommunication',
        'Banking Fees',
    ];

    public function definition(): array
    {
        return [
            'name'        => fake()->unique()->randomElement(static::CATEGORIES),
            'description' => fake()->optional(0.4)->sentence(),
            'is_active'   => true,
        ];
    }
}
