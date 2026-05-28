<?php

namespace Database\Factories;

use App\Models\CampaignCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignCategory>
 */
class CampaignCategoryFactory extends Factory
{
    protected $model = CampaignCategory::class;

    /** Realistic charity campaign categories — bilingual */
    private const CATEGORIES = [
        ['name_ar' => 'طبي',           'name_en' => 'Medical'],
        ['name_ar' => 'إسكان',          'name_en' => 'Housing'],
        ['name_ar' => 'موسمي',          'name_en' => 'Seasonal'],
        ['name_ar' => 'تعليمي',         'name_en' => 'Education'],
        ['name_ar' => 'حدث مجتمعي',     'name_en' => 'Community Event'],
        ['name_ar' => 'طارئ',           'name_en' => 'Emergency'],
        ['name_ar' => 'دعم الأيتام',    'name_en' => 'Orphan Support'],
        ['name_ar' => 'أمن غذائي',      'name_en' => 'Food Security'],
        ['name_ar' => 'مؤتمرات',        'name_en' => 'Conferences'],
        ['name_ar' => 'رعاية المسنين',  'name_en' => 'Elderly Care'],
    ];

    public function definition(): array
    {
        $category = fake()->unique()->randomElement(static::CATEGORIES);

        return [
            'name_ar'     => $category['name_ar'],
            'name_en'     => $category['name_en'],
            'description' => fake()->optional(0.5)->sentence(),
            'is_active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
