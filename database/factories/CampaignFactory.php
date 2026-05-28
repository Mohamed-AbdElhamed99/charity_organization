<?php

namespace Database\Factories;

use App\Enums\CampaignRecurrence;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /** Realistic campaign titles inspired by requirements */
    private const TITLES_EN = [
        'Cancer Treatment Support',
        'House Renovation in Al-Qubeiba',
        'Kidney Center Press Conference',
        'Umbrella for Elderly in Saft Turab',
        'Ramadan Orphan Celebration',
        'University Conference Support',
        'Winter Clothing Drive',
        'Medical Equipment Fund',
        'Flood Emergency Relief',
        'Back to School Initiative',
        'Emergency Surgery Fund',
        'Water Well Project',
    ];

    public function definition(): array
    {
        $titleEn   = fake()->unique()->randomElement(static::TITLES_EN);
        $status    = fake()->randomElement(CampaignStatus::cases());
        $startDate = fake()->dateTimeBetween('-1 year', '+3 months');
        $endDate   = fake()->dateTimeBetween($startDate, '+6 months');
        $country   = Country::inRandomOrder()->first();
        $state     = $country
            ? State::where('country_id', $country->id)->inRandomOrder()->first()
            : null;

        return [
            'category_id'       => CampaignCategory::inRandomOrder()->value('id'),
            'slug'              => Str::slug($titleEn) . '-' . fake()->unique()->numerify('###'),
            'title_ar'          => fake()->sentence(4),
            'title_en'          => $titleEn,
            'excerpt_ar'        => fake()->optional(0.8)->sentence(),
            'excerpt_en'        => fake()->optional(0.8)->sentence(),
            'description_ar'    => fake()->optional(0.7)->paragraphs(3, true),
            'description_en'    => fake()->optional(0.7)->paragraphs(3, true),
            'start_date'        => $startDate->format('Y-m-d'),
            'end_date'          => $endDate->format('Y-m-d'),
            'address'           => fake()->optional(0.6)->address(),
            'country_id'        => $country?->id,
            'state_id'          => $state?->id,
            'lat'               => fake()->optional(0.4)->latitude(),
            'lng'               => fake()->optional(0.4)->longitude(),
            'budget'            => fake()->randomFloat(2, 500, 50_000),
            'donation_target'   => fake()->optional(0.7)->randomFloat(2, 1_000, 100_000),
            'status'            => $status,
            'is_public'         => $status->isPublishable() ? fake()->boolean(80) : false,
            'open_donation_form' => $status === CampaignStatus::Active ? fake()->boolean(70) : false,
            'is_repeated'       => fake()->randomElement(CampaignRecurrence::cases()),
            'repeat_until'      => fake()->optional(0.3)->dateTimeBetween('+1 month', '+1 year')?->format('Y-m-d'),
            'meta_title_ar'     => fake()->optional(0.4)->sentence(5),
            'meta_title_en'     => fake()->optional(0.4)->sentence(5),
            'meta_description_ar' => fake()->optional(0.4)->sentence(),
            'meta_description_en' => fake()->optional(0.4)->sentence(),
            'created_by'        => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function draft(): static
    {
        return $this->state(fn () => [
            'status'            => CampaignStatus::Draft,
            'is_public'         => false,
            'open_donation_form' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status'            => CampaignStatus::Active,
            'is_public'         => true,
            'open_donation_form' => true,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status'            => CampaignStatus::Completed,
            'is_public'         => true,
            'open_donation_form' => false,
        ]);
    }

    public function withoutTarget(): static
    {
        return $this->state(fn () => ['donation_target' => null]);
    }
}
