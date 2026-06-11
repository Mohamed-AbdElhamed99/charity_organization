<?php

namespace Tests\Feature\Admin;

use App\Enums\DonorType;
use App\Models\DonorProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DonorProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function createDonorUser(): User
    {
        $donor = User::factory()->create();
        $donor->assignRole('donor');

        return $donor;
    }

    public function test_authorized_user_can_view_donor_profiles_index(): void
    {
        $user = $this->createAuthorizedUser();
        DonorProfile::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.donor-profiles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/donor-profiles/donor-profiles-index')
                ->has('donorProfiles.data', 2)
            );
    }

    public function test_authorized_user_can_create_donor_profile(): void
    {
        $user = $this->createAuthorizedUser();
        $donor = $this->createDonorUser();

        $this->actingAs($user)
            ->post(route('admin.donor-profiles.store'), [
                'user_id' => $donor->id,
                'type' => DonorType::Individual->value,
                'address' => '123 Main St',
                'notes' => 'VIP donor',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('donor_profiles', [
            'user_id' => $donor->id,
            'type' => DonorType::Individual->value,
        ]);
    }

    public function test_authorized_user_can_view_donor_profile_show(): void
    {
        $user = $this->createAuthorizedUser();
        $profile = DonorProfile::factory()->individual()->create();

        $this->actingAs($user)
            ->get(route('admin.donor-profiles.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/donor-profiles/donor-profiles-show')
                ->where('donorProfile.id', $profile->id)
                ->has('donorProfile.user')
            );
    }

    public function test_user_without_permission_cannot_view_donor_profiles(): void
    {
        $user = User::factory()->create();
        $profile = DonorProfile::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.donor-profiles.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.donor-profiles.show', $profile))
            ->assertForbidden();
    }
}
