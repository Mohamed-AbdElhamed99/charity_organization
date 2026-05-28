<?php

namespace Database\Factories;

use App\Enums\DonorType;
use App\Models\Country;
use App\Models\DonorProfile;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorProfile>
 */
class DonorProfileFactory extends Factory
{
    protected $model = DonorProfile::class;

    public function definition(): array
    {
        $type    = fake()->randomElement(DonorType::cases());
        $country = Country::inRandomOrder()->first();
        $state   = $country
            ? State::where('country_id', $country->id)->inRandomOrder()->first()
            : null;

        return [
            'user_id'           => User::factory(),
            'type'              => $type,
            'organization_name' => $type === DonorType::Organization
                ? fake()->company()
                : null,
            'address'           => fake()->optional(0.6)->streetAddress(),
            'country_id'        => $country?->id,
            'state_id'          => $state?->id,
            'notes'             => fake()->optional(0.2)->sentence(),
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function individual(): static
    {
        return $this->state(fn () => [
            'type'              => DonorType::Individual,
            'organization_name' => null,
        ]);
    }

    public function organization(): static
    {
        return $this->state(fn () => [
            'type'              => DonorType::Organization,
            'organization_name' => fake()->company(),
        ]);
    }
}
