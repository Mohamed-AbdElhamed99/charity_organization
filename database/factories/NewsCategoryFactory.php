<?php

namespace Database\Factories;

use App\Models\NewsCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsCategory>
 */
class NewsCategoryFactory extends Factory
{
    protected $model = NewsCategory::class;

    private const CATEGORIES = [
        ['name_ar' => 'أخبار المنظمة',    'name_en' => 'Organization News'],
        ['name_ar' => 'فعاليات',           'name_en' => 'Events'],
        ['name_ar' => 'قصص نجاح',          'name_en' => 'Success Stories'],
        ['name_ar' => 'إعلانات',           'name_en' => 'Announcements'],
        ['name_ar' => 'تقارير',            'name_en' => 'Reports'],
        ['name_ar' => 'شراكات',            'name_en' => 'Partnerships'],
    ];

    public function definition(): array
    {
        $category = fake()->unique()->randomElement(static::CATEGORIES);

        return [
            'name_ar'   => $category['name_ar'],
            'name_en'   => $category['name_en'],
            'is_active' => true,
        ];
    }
}
