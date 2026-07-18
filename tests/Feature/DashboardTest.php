<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_get_a_404_instead_of_a_login_redirect(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertNotFound();
    }

    public function test_authenticated_staff_can_visit_the_dashboard(): void
    {
        $user = User::factory()->staff()->create();
        $this->actingAs($user);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_admin_root_redirects_to_dashboard(): void
    {
        $user = User::factory()->staff()->create();
        $this->actingAs($user);

        $response = $this->get('/admin');

        $response->assertRedirect('/admin/dashboard');
    }
}
