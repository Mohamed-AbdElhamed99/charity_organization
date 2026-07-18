<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DonorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_donor_can_register(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post(route('account.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-register@example.com',
            'phone' => '555-0100',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('account.donations.index'));

        $user = User::query()->where('email', 'jane-register@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('donor'));
        $this->assertNotNull($user->password_set_at);
        $this->assertAuthenticatedAs($user);

        Event::assertDispatched(Registered::class);
    }

    public function test_registering_with_an_unclaimed_guest_email_sends_a_reset_link_instead_of_creating_a_duplicate(): void
    {
        $guest = User::factory()->create([
            'email' => 'guest-donor@example.com',
            'password' => Hash::make(str()->random(32)),
            'password_set_at' => null,
        ]);
        $guest->assignRole('donor');

        $response = $this->post(route('account.register'), [
            'first_name' => 'Guest',
            'last_name' => 'Donor',
            'email' => 'guest-donor@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertSame(1, User::query()->where('email', 'guest-donor@example.com')->count());
        $this->assertGuest();
    }

    public function test_registering_with_an_already_claimed_email_returns_a_validation_error(): void
    {
        $existing = User::factory()->create([
            'email' => 'claimed-donor@example.com',
            'password_set_at' => now(),
        ]);
        $existing->assignRole('donor');

        $response = $this->post(route('account.register'), [
            'first_name' => 'Someone',
            'last_name' => 'Else',
            'email' => 'claimed-donor@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::query()->where('email', 'claimed-donor@example.com')->count());
    }
}
