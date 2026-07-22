<?php

namespace Tests\Feature;

use App\Enums\ModulePermission;
use App\Models\User;
use App\Support\AuthenticatedHome;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SuperAdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_perform_any_ability_even_without_direct_permission_rows(): void
    {
        $user = User::factory()->superAdmin()->create();

        $role = Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->firstOrFail();
        $role->syncPermissions([]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->assertTrue($user->fresh()->can('access_dashboard'));
        $this->assertTrue($user->fresh()->can(ModulePermission::USERS->permission('view')));
        $this->assertSame('/admin', AuthenticatedHome::url($user->fresh()));
    }

    public function test_permissions_sync_grants_every_permission_to_super_admin(): void
    {
        $role = Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->firstOrFail();
        $role->syncPermissions([]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->artisan('permissions:sync')->assertSuccessful();

        $role->refresh();
        $allPermissionNames = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $assigned = $role->permissions->pluck('name')->sort()->values()->all();

        $this->assertSame($allPermissionNames, $assigned);
    }

    public function test_non_super_admin_does_not_bypass_permission_checks(): void
    {
        $user = User::factory()->donor()->create();

        $this->assertFalse($user->can('access_dashboard'));
        $this->assertSame(route('account.profile.edit'), AuthenticatedHome::url($user));
    }
}
