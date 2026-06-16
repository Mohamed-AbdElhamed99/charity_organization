<?php

namespace Database\Factories;

use App\Models\AidItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AidItem>
 */
class AidItemFactory extends Factory
{
    protected $model = AidItem::class;

    public function definition(): array
    {
        return [
            'name' => [
                'en' => fake()->words(2, true),
                'ar' => fake()->words(2, true),
            ],
            'unit' => [
                'en' => fake()->randomElement(['box', 'piece', 'session']),
                'ar' => fake()->randomElement(['صندوق', 'قطعة', 'جلسة']),
            ],
            'default_unit_cost' => fake()->numberBetween(100, 25000),
            'category' => fake()->optional()->word(),
            'is_active' => true,
        ];
    }
}
