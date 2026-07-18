<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRouteGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_donor_gets_a_404_not_a_forbidden_page(): void
    {
        $donor = User::factory()->donor()->create();

        $this->actingAs($donor)->get('/admin/dashboard')->assertNotFound();
    }

    public function test_a_guest_gets_a_404_not_a_login_redirect(): void
    {
        $this->get('/admin/dashboard')->assertNotFound();
    }

    public function test_a_super_admin_can_access_the_admin_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_staff_can_access_the_admin_dashboard(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/admin/dashboard')->assertOk();
    }

    public function test_a_field_worker_can_access_the_admin_dashboard(): void
    {
        $fieldWorker = User::factory()->fieldWorker()->create();

        $this->actingAs($fieldWorker)->get('/admin/dashboard')->assertOk();
    }
}
