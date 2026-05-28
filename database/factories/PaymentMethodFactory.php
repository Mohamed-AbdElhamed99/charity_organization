<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public const METHODS = [
        ['name' => 'Cash',          'code' => 'cash'],
        ['name' => 'Cheque',        'code' => 'cheque'],
        ['name' => 'Bank Transfer', 'code' => 'bank_transfer'],
        ['name' => 'Zelle',         'code' => 'zelle'],
        ['name' => 'Stripe',        'code' => 'stripe'],
        ['name' => 'Credit Card',   'code' => 'credit_card'],
        ['name' => 'PayPal',        'code' => 'paypal'],
        ['name' => 'Wire Transfer', 'code' => 'wire_transfer'],
    ];

    public function definition(): array
    {
        $method = fake()->unique()->randomElement(static::METHODS);

        return [
            'name'      => $method['name'],
            'code'      => $method['code'],
            'is_active' => true,
        ];
    }
}
