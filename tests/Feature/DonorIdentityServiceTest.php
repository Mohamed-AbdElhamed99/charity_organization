<?php

namespace Tests\Feature;

use App\Models\DonorProfile;
use App\Models\User;
use App\Services\DonorIdentityService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorIdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_first_or_create_by_email_creates_a_new_donor_once(): void
    {
        $service = app(DonorIdentityService::class);

        $user = $service->firstOrCreateByEmail('Jane', 'Donor', 'jane-identity@example.com', '555-0100');

        $this->assertSame(1, User::query()->where('email', 'jane-identity@example.com')->count());
        $this->assertTrue($user->hasRole('donor'));
        $this->assertNotNull(DonorProfile::query()->where('user_id', $user->id)->first());
    }

    public function test_repeat_donation_with_same_email_reuses_the_existing_user_and_updates_phone(): void
    {
        $service = app(DonorIdentityService::class);

        $first = $service->firstOrCreateByEmail('Jane', 'Donor', 'jane-repeat@example.com', '555-0100');

        $second = $service->firstOrCreateByEmail('Jane', 'Donor', 'jane-repeat@example.com', '555-0200');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, User::query()->where('email', 'jane-repeat@example.com')->count());
        $this->assertSame('555-0200', $second->refresh()->phone);
    }

    public function test_repeat_donation_without_phone_keeps_previously_stored_phone(): void
    {
        $service = app(DonorIdentityService::class);

        $first = $service->firstOrCreateByEmail('Jane', 'Donor', 'jane-keep-phone@example.com', '555-0300');

        $second = $service->firstOrCreateByEmail('Jane', 'Donor', 'jane-keep-phone@example.com', null);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('555-0300', $second->refresh()->phone);
    }
}
