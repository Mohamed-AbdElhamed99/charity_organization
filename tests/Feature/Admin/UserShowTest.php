<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_authorized_user_can_view_user_show_page_with_roles(): void
    {
        $viewer = $this->createAuthorizedUser();
        $target = User::factory()->create();
        $target->assignRole('staff');

        $this->actingAs($viewer)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/users-show')
                ->where('user.id', $target->id)
                ->where('user.email', $target->email)
                ->where('user.roles', ['staff'])
                ->has('user.permissions')
            );
    }

    public function test_user_without_permission_cannot_view_user_show(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('admin.users.show', $target))
            ->assertForbidden();
    }

    public function test_user_show_includes_all_assigned_roles(): void
    {
        $viewer = $this->createAuthorizedUser();
        $target = User::factory()->create();

        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);
        $target->syncRoles(['staff', 'donor']);

        $this->actingAs($viewer)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('user.roles', 2)
                ->where('user.roles', fn ($roles) => collect($roles)->contains('staff')
                    && collect($roles)->contains('donor'))
            );
    }
}
