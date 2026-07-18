<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_donor_can_update_their_name_and_phone(): void
    {
        $user = User::factory()->donor()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->patch(route('account.profile.update'), [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => $user->email,
            'phone' => '555-9999',
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('555-9999', $user->phone);
    }

    public function test_changing_email_resets_verification_and_sends_a_new_notification(): void
    {
        Notification::fake();

        $user = User::factory()->donor()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->patch(route('account.profile.update'), [
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'new@example.com',
        ]);

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_updating_password_sets_password_set_at(): void
    {
        $user = User::factory()->donor()->create();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
            'current_password' => 'password',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-password', $user->password));
        $this->assertNotNull($user->password_set_at);
    }
}
