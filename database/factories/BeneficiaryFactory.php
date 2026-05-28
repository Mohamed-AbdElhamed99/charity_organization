<?php

namespace Database\Factories;

use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    private static int $codeSequence = 1;

    public function definition(): array
    {
        return [
            'type'       => fake()->randomElement(BeneficiaryType::cases()),
            'code'       => $this->generateCode(),
            'status'     => fake()->randomElement(BeneficiaryStatus::cases()),
            'notes'      => fake()->optional(0.3)->sentence(),
            'created_by' => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }

    private function generateCode(): string
    {
        $year = now()->year;
        $seq  = str_pad(static::$codeSequence++, 4, '0', STR_PAD_LEFT);

        return "BEN-{$year}-{$seq}";
    }

    // ─── States ──────────────────────────────────────────────────────────────

    public function individual(): static
    {
        return $this->state(fn () => ['type' => BeneficiaryType::Individual])
            ->afterCreating(fn (Beneficiary $b) =>
                BeneficiaryIndividualFactory::new()->for($b)->create()
            );
    }

    public function family(): static
    {
        return $this->state(fn () => ['type' => BeneficiaryType::Family])
            ->afterCreating(fn (Beneficiary $b) =>
                BeneficiaryFamilyFactory::new()->for($b)->create()
            );
    }

    public function organization(): static
    {
        return $this->state(fn () => ['type' => BeneficiaryType::Organization])
            ->afterCreating(fn (Beneficiary $b) =>
                BeneficiaryOrganizationFactory::new()->for($b)->create()
            );
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => BeneficiaryStatus::Active]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => BeneficiaryStatus::PendingAssessment]);
    }
}
