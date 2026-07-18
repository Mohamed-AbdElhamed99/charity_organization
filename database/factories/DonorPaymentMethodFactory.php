<?php

namespace Database\Factories;

use App\Models\DonorPaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorPaymentMethod>
 */
class DonorPaymentMethodFactory extends Factory
{
    protected $model = DonorPaymentMethod::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stripe_payment_method_id' => 'pm_test_'.fake()->unique()->numerify('##########'),
            'brand' => 'visa',
            'last4' => (string) fake()->numberBetween(1000, 9999),
            'exp_month' => fake()->numberBetween(1, 12),
            'exp_year' => (int) now()->addYears(2)->format('Y'),
            'is_default' => false,
        ];
    }

    public function default(): self
    {
        return $this->state(['is_default' => true]);
    }
}
