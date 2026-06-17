<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_inertia_auth_props_include_user_roles_and_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->where('auth.user.id', $user->id)
                ->has('auth.roles')
                ->has('auth.permissions')
            );

        $page = $response->viewData('page');
        $roles = data_get($page, 'props.auth.roles');
        $permissions = data_get($page, 'props.auth.permissions');

        $this->assertIsArray($roles);
        $this->assertIsArray($permissions);
        $this->assertContains('staff', $roles);
        $this->assertContains('view_donations', $permissions);
    }

    public function test_backend_403_responses_render_the_forbidden_inertia_page(): void
    {
        Route::middleware(['web', 'auth'])->get('/_test/forbidden', function () {
            abort(403);
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/_test/forbidden')
            ->assertStatus(403)
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/errors/forbidden')
            );
    }
}
