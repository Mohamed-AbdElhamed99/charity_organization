<?php

namespace Database\Factories;

use App\Models\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TransactionType>
 */
class TransactionTypeFactory extends Factory
{
    protected $model = TransactionType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'code' => Str::slug($name, '_'),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'is_system' => false,
        ];
    }

    public function system(string $code = TransactionType::CODE_DONATION): static
    {
        $definition = collect(TransactionType::systemDefinitions())
            ->firstWhere('code', $code);

        return $this->state(fn () => $definition ?? [
            'name' => 'Donation',
            'code' => TransactionType::CODE_DONATION,
            'description' => null,
            'is_active' => true,
            'is_system' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
