<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question_ar' => fake()->sentence(8),
            'question_en' => fake()->sentence(8),
            'answer_ar' => fake()->paragraphs(2, true),
            'answer_en' => fake()->paragraphs(2, true),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_published' => fake()->boolean(70),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
        ]);
    }
}
