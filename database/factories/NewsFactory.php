<?php

namespace Database\Factories;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        $titleEn = fake()->sentence(6);

        return [
            'category_id'          => NewsCategory::inRandomOrder()->value('id'),
            'slug'                 => Str::slug($titleEn) . '-' . fake()->unique()->numerify('###'),
            'title_ar'             => fake()->sentence(6),
            'title_en'             => $titleEn,
            'subtitle_ar'          => fake()->optional(0.5)->sentence(4),
            'subtitle_en'          => fake()->optional(0.5)->sentence(4),
            'excerpt_ar'           => fake()->optional(0.9)->paragraph(),
            'excerpt_en'           => fake()->optional(0.9)->paragraph(),
            'body_ar'              => fake()->optional(0.8)->paragraphs(5, true),
            'body_en'              => fake()->optional(0.8)->paragraphs(5, true),
            'video_url'            => fake()->optional(0.2)->url(),
            'published_at'         => fake()->optional(0.8)->dateTimeBetween('-2 years', 'now')?->format('Y-m-d'),
            'is_active'            => fake()->boolean(85),
            'is_private'           => fake()->boolean(15),
            'meta_title_ar'        => fake()->optional(0.4)->sentence(5),
            'meta_title_en'        => fake()->optional(0.4)->sentence(5),
            'meta_description_ar'  => fake()->optional(0.4)->sentence(),
            'meta_description_en'  => fake()->optional(0.4)->sentence(),
            'created_by'           => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function published(): static
    {
        return $this->state(fn () => [
            'is_active'    => true,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_active'    => false,
            'published_at' => null,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn () => ['is_private' => true]);
    }
}
