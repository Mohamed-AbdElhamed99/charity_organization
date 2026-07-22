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

    public function test_account_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('account.login'));

        $response->assertOk();
    }

    public function test_a_donor_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->donor()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('account.profile.edit'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_staff_logging_in_via_account_login_are_redirected_to_admin(): void
    {
        $user = User::factory()->staff()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        $user = User::factory()->donor()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('login.store'), [
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
