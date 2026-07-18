<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_donor_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->donor()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('account.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('account.donations.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        $user = User::factory()->donor()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('account.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_logged_in_donor_can_logout(): void
    {
        $user = User::factory()->donor()->create();

        $response = $this->actingAs($user)->post(route('account.logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
