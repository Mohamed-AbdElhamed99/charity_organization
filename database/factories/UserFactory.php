<?php

namespace Database\Factories;

use App\Enums\UserGender;
use App\Enums\UserStatus;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        $gender = fake()->randomElement(UserGender::cases());
        $country = Country::inRandomOrder()->first();
        $state = $country
            ? State::where('country_id', $country->id)->inRandomOrder()->first()
            : null;

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->unique()->numerify('+1##########'),
            'status' => fake()->randomElement(UserStatus::cases()),
            'password' => static::$password ??= Hash::make('password'),
            'password_set_at' => now(),
            'national_id' => fake()->unique()->numerify('##############'),
            'job' => fake()->jobTitle(),
            'birthdate' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'bio' => fake()->sentence(),
            'social_links' => fake()->optional(0.3)->passthrough([
                'facebook' => fake()->url(),
                'twitter' => fake()->url(),
                'linkedin' => fake()->url(),
            ]),
            'gender' => $gender,
            'address' => fake()->optional(0.7)->address(),
            'country_id' => $country?->id,
            'state_id' => $state?->id,
            'provider' => null,
            'provider_id' => null,
            'provider_token' => null,
            'provider_refresh_token' => null,
            'remember_token' => Str::random(10),
        ];
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('super_admin');
        });
    }

    public function staff(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('staff');
        });
    }

    public function donor(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('donor');
            DonorProfileFactory::new()->for($user)->create();
        });
    }

    public function fieldWorker(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('field_worker');
        });
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
