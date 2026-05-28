<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds all Spatie roles and permissions.
 * Must run FIRST — users and all other seeders depend on roles existing.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    // ─── All system permissions ───────────────────────────────────────────────

    private const PERMISSIONS = [
        // Campaigns
        'view_campaigns',
        'create_campaigns',
        'edit_campaigns',
        'delete_campaigns',
        'publish_campaigns',

        // Beneficiaries — base CRUD
        'view_beneficiaries',
        'create_beneficiaries',
        'edit_beneficiaries',
        'delete_beneficiaries',

        // Beneficiaries — sensitive data (Layer 1 gate; Layer 2 is per-record grant)
        'view_beneficiary_details',

        // Assessments
        'conduct_assessments',
        'review_assessments',
        'approve_assessments',

        // Beneficiary access grants
        'manage_beneficiary_access',

        // Financial
        'view_transactions',
        'create_transactions',
        'edit_transactions',
        'delete_transactions',
        'view_donations',
        'create_donations',
        'view_expenses',
        'create_expenses',
        'view_transfers',
        'create_transfers',
        'view_reports',

        // CMS
        'view_news',
        'create_news',
        'edit_news',
        'delete_news',
        'manage_cms',

        // Admin
        'manage_users',
        'manage_roles',
        'manage_settings',
        'view_contact_submissions',
    ];

    // ─── Role → Permission mapping ────────────────────────────────────────────

    private const ROLES = [
        'super_admin' => '*', // all permissions

        'staff' => [
            'view_campaigns', 'create_campaigns', 'edit_campaigns',
            'view_beneficiaries', 'create_beneficiaries', 'edit_beneficiaries',
            'view_beneficiary_details',
            'conduct_assessments',
            'view_transactions', 'create_transactions',
            'view_donations', 'create_donations',
            'view_expenses', 'create_expenses',
            'view_transfers', 'create_transfers',
            'view_reports',
            'view_news', 'create_news', 'edit_news',
            'view_contact_submissions',
        ],

        'field_worker' => [
            'view_beneficiaries', 'create_beneficiaries',
            'view_beneficiary_details',
            'conduct_assessments',
            'view_campaigns',
        ],

        'donor' => [
            'view_campaigns',
            'view_news',
            'create_donations',
        ],
    ];

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $permissions = collect(static::PERMISSIONS)
            ->mapWithKeys(fn (string $name) => [
                $name => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']),
            ]);

        // Create roles and assign permissions
        foreach (static::ROLES as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($rolePermissions === '*') {
                $role->syncPermissions($permissions->values());
            } else {
                $role->syncPermissions(
                    $permissions->only($rolePermissions)->values()
                );
            }
        }

        $this->command->info('✅ Roles and permissions seeded.');
    }
}
