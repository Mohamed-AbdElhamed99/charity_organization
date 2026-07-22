<?php

namespace App\Console\Commands;

use App\Enums\ModulePermission;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Synchronizes the `permissions` table with the canonical set of permissions
 * generated from `App\Enums\ModulePermission` (backed by `config/permissions.php`).
 *
 * Obsolete permissions (no longer produced by the enum) are purged and
 * missing ones are created, while every role's existing permission
 * assignments are preserved for any permission name that still exists
 * after the sync — so renaming/removing an ability in the enum/config and
 * running this command is enough to keep the database, and every role's
 * access, consistent with the code.
 */
#[Signature('permissions:sync')]
#[Description('Sync the permissions table with App\Enums\ModulePermission, preserving existing role assignments')]
class SyncPermissionsCommand extends Command
{
    public function handle(): int
    {
        $this->info('Synchronizing permissions with App\Enums\ModulePermission...');

        try {
            DB::transaction(function (): void {
                $roleBackup = $this->backupRolePermissions();

                $validPermissions = $this->validPermissionNames();

                $purgedCount = $this->purgeObsoletePermissions($validPermissions);
                $this->info("{$purgedCount} obsolete permission(s) purged.");

                $createdCount = $this->createMissingPermissions($validPermissions);
                $this->info("{$createdCount} new permission(s) created.");

                $this->restoreRolePermissions($roleBackup, $validPermissions);
                $this->info('Role permission assignments restored.');
            });
        } catch (Throwable $e) {
            $this->error('Permission sync aborted, all changes rolled back: '.$e->getMessage());

            return self::FAILURE;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('Permissions successfully synchronized with role state preserved!');

        return self::SUCCESS;
    }

    /**
     * Capture every role's current permission assignments before making any
     * changes, so they can be restored once the permissions table has been
     * rebuilt from the config.
     *
     * @return array<string, list<string>> role name => list of permission names
     */
    private function backupRolePermissions(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->get()
            ->mapWithKeys(fn (Role $role) => [
                $role->name => $role->permissions->pluck('name')->all(),
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    private function validPermissionNames(): array
    {
        return collect(ModulePermission::cases())
            ->flatMap(fn (ModulePermission $module) => $module->allPermissions())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Delete permissions that exist in the database but are no longer
     * produced by the enum/config. Spatie's `role_has_permissions` and
     * `model_has_permissions` pivot rows cascade-delete automatically.
     *
     * @param  list<string>  $validPermissions
     */
    private function purgeObsoletePermissions(array $validPermissions): int
    {
        $obsolete = Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $validPermissions)
            ->get();

        $count = $obsolete->count();

        if ($count > 0) {
            Permission::query()
                ->whereIn('id', $obsolete->pluck('id'))
                ->delete();
        }

        return $count;
    }

    /**
     * @param  list<string>  $validPermissions
     */
    private function createMissingPermissions(array $validPermissions): int
    {
        $existing = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $validPermissions)
            ->pluck('name')
            ->all();

        $missing = array_values(array_diff($validPermissions, $existing));

        foreach ($missing as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        return count($missing);
    }

    /**
     * Re-assign each role's original permissions, filtered down to the
     * names that are still valid after the sync. The super_admin role always
     * receives every valid permission so new abilities are granted by default.
     *
     * @param  array<string, list<string>>  $roleBackup
     * @param  list<string>  $validPermissions
     */
    private function restoreRolePermissions(array $roleBackup, array $validPermissions): void
    {
        foreach ($roleBackup as $roleName => $originalPermissions) {
            if ($roleName === 'super_admin') {
                continue;
            }

            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            $stillValid = array_values(array_intersect($originalPermissions, $validPermissions));

            $role->syncPermissions($stillValid);
        }

        $superAdmin = Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->first();

        if ($superAdmin !== null) {
            $superAdmin->syncPermissions($validPermissions);
        }
    }
}
