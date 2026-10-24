<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignAppeal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CampaignAppeal>
 */
class CampaignAppealFactory extends Factory
{
    protected $model = CampaignAppeal::class;

    public function definition(): array
    {
        $titleEn = fake()->unique()->words(3, true);

        return [
            'campaign_id' => Campaign::factory()->active(),
            'slug' => Str::slug($titleEn).'-'.fake()->unique()->numerify('###'),
            'title_en' => ucfirst($titleEn),
            'title_ar' => 'نداء '.$titleEn,
            'subtitle_en' => fake()->sentence(),
            'subtitle_ar' => fake()->sentence(),
            'unit_amount_cents' => 1500,
            'unit_label_en' => 'box',
            'unit_label_ar' => 'صندوق',
            'unit_label_plural_en' => 'boxes',
            'unit_label_plural_ar' => 'صناديق',
            'quantity_options' => [1, 3, 5],
            'allow_custom_amount' => true,
            'cta_label_en' => 'Donate a Food Box',
            'cta_label_ar' => 'تبرع بصندوق طعام',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
