<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    private const CURRENCIES = [
        ['code' => 'USD', 'name' => 'US Dollar',       'symbol' => '$'],
        ['code' => 'EGP', 'name' => 'Egyptian Pound',  'symbol' => 'ج.م'],
        ['code' => 'EUR', 'name' => 'Euro',             'symbol' => '€'],
        ['code' => 'GBP', 'name' => 'British Pound',   'symbol' => '£'],
        ['code' => 'SAR', 'name' => 'Saudi Riyal',     'symbol' => 'ر.س'],
        ['code' => 'AED', 'name' => 'UAE Dirham',      'symbol' => 'د.إ'],
        ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CA$'],
    ];

    public function definition(): array
    {
        $currency = fake()->unique()->randomElement(static::CURRENCIES);

        return [
            'code'       => $currency['code'],
            'name'       => $currency['name'],
            'symbol'     => $currency['symbol'],
            'is_default' => false,
            'is_active'  => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true, 'is_active' => true]);
    }
}
