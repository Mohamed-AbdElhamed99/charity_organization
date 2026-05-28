<?php

namespace Database\Factories;

use App\Models\ContactUs;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactUs>
 */
class ContactUsFactory extends Factory
{
    protected $model = ContactUs::class;

    public function definition(): array
    {
        $isReviewed = fake()->boolean(40);

        return [
            'fullname'     => fake()->name(),
            'email'        => fake()->safeEmail(),
            'phone'        => fake()->optional(0.7)->phoneNumber(),
            'subject'      => fake()->randomElement([
                'Donation Inquiry', 'Volunteer Application', 'Campaign Question',
                'General Inquiry', 'Partnership Proposal', 'Media Request', 'Technical Support',
            ]),
            'message'      => fake()->paragraphs(2, true),
            'is_reviewed'  => $isReviewed,
            'reviewed_by'  => $isReviewed
                ? User::inRandomOrder()->value('id')
                : null,
            'reviewed_at'  => $isReviewed
                ? fake()->dateTimeBetween('-3 months', 'now')
                : null,
            'review_notes' => $isReviewed
                ? fake()->optional(0.5)->sentence()
                : null,
        ];
    }

    public function unreviewed(): static
    {
        return $this->state(fn () => [
            'is_reviewed' => false,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function reviewed(): static
    {
        return $this->state(fn () => [
            'is_reviewed' => true,
            'reviewed_by' => User::inRandomOrder()->value('id') ?? User::factory(),
            'reviewed_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }
}
