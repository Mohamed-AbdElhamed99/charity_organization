<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_guests_cannot_access_roles_index(): void
    {
        $this->get(route('admin.roles.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_roles_index(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/roles/roles-index')
                ->has('roles.data')
                ->has('permissions')
                ->has('permissionGroups')
            );
    }

    public function test_authorized_user_can_create_role(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.roles.store'), [
                'name' => 'content_editor',
                'permissions' => ['view_news', 'create_news'],
            ])
            ->assertRedirect();

        $role = Role::query()->where('name', 'content_editor')->first();

        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('view_news'));
        $this->assertTrue($role->hasPermissionTo('create_news'));
    }

    public function test_user_without_permission_cannot_create_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.roles.store'), [
                'name' => 'content_editor',
                'permissions' => ['view_news'],
            ])
            ->assertForbidden();
    }

    public function test_authorized_user_can_update_non_system_role(): void
    {
        $user = $this->createAuthorizedUser();
        $role = Role::create(['name' => 'custom_role', 'guard_name' => 'web']);
        $role->syncPermissions(['view_news']);

        $this->actingAs($user)
            ->patch(route('admin.roles.update', $role), [
                'name' => 'custom_role_updated',
                'permissions' => ['view_news', 'edit_news'],
            ])
            ->assertRedirect();

        $role->refresh();

        $this->assertSame('custom_role_updated', $role->name);
        $this->assertTrue($role->hasPermissionTo('edit_news'));
    }

    public function test_super_admin_role_cannot_be_updated(): void
    {
        $user = $this->createAuthorizedUser();
        $role = Role::query()->where('name', 'super_admin')->firstOrFail();

        $this->actingAs($user)
            ->patch(route('admin.roles.update', $role), [
                'name' => 'renamed_admin',
                'permissions' => ['view_news'],
            ])
            ->assertForbidden();
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $role = Role::query()->where('name', 'staff')->firstOrFail();

        $this->actingAs($user)
            ->delete(route('admin.roles.destroy', $role))
            ->assertStatus(403);
    }

    public function test_custom_role_can_be_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $role = Role::create(['name' => 'temporary_role', 'guard_name' => 'web']);

        $this->actingAs($user)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect();

        $this->assertNull(Role::query()->find($role->id));
    }
}
