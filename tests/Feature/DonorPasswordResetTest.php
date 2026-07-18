<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class DonorPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_donor_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->donor()->create();

        $response = $this->post(route('account.password.email'), ['email' => $user->email]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_a_donor_can_reset_their_password_with_a_valid_token(): void
    {
        $user = User::factory()->donor()->create(['password_set_at' => null]);

        $token = Password::broker('users')->createToken($user);

        $response = $this->post(route('account.password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('account.login'));
        $this->assertNotNull($user->refresh()->password_set_at);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }
}
