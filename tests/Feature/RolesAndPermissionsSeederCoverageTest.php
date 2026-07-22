<?php

namespace Tests\Feature;

use App\Enums\ModulePermission;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class RolesAndPermissionsSeederCoverageTest extends TestCase
{
    public function test_all_backend_authorization_permissions_exist_in_module_permission_enum(): void
    {
        $seededPermissions = $this->seededPermissions();
        $usedPermissions = $this->permissionsUsedInBackendAuthorization();

        $missing = array_values(array_diff($usedPermissions, $seededPermissions));

        $this->assertSame(
            [],
            $missing,
            'Missing permissions in App\Enums\ModulePermission: '.implode(', ', $missing)
        );
    }

    public function test_all_sidebar_permissions_exist_in_module_permission_enum(): void
    {
        $seededPermissions = $this->seededPermissions();
        $sidebarPermissions = $this->permissionsUsedInSidebar();

        $missing = array_values(array_diff($sidebarPermissions, $seededPermissions));

        $this->assertSame(
            [],
            $missing,
            'Missing sidebar permissions in App\Enums\ModulePermission: '.implode(', ', $missing)
        );
    }

    /**
     * @return list<string>
     */
    private function seededPermissions(): array
    {
        $permissions = collect(ModulePermission::cases())
            ->flatMap(fn (ModulePermission $module) => $module->allPermissions())
            ->unique()
            ->values()
            ->all();

        sort($permissions);

        return $permissions;
    }

    /**
     * @return list<string>
     */
    private function permissionsUsedInBackendAuthorization(): array
    {
        $permissions = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path())
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($fileInfo->getPathname());
            if (! is_string($content)) {
                continue;
            }

            if (preg_match_all("/can\\('([a-z]+_[a-z_]+)'\\)/", $content, $matches) !== false) {
                foreach ($matches[1] as $permission) {
                    $permissions[$permission] = true;
                }
            }
        }

        $unique = array_keys($permissions);
        sort($unique);

        return $unique;
    }

    /**
     * @return list<string>
     */
    private function permissionsUsedInSidebar(): array
    {
        $sidebarPath = resource_path('js/components/layout/data/sidebar-data.ts');
        $content = file_get_contents($sidebarPath);

        if (! is_string($content)) {
            return [];
        }

        $permissions = [];
        if (preg_match_all("/permission:\\s*'([a-z]+_[a-z_]+)'/", $content, $matches) !== false) {
            foreach ($matches[1] as $permission) {
                $permissions[$permission] = true;
            }
        }

        $unique = array_keys($permissions);
        sort($unique);

        return $unique;
    }
}
