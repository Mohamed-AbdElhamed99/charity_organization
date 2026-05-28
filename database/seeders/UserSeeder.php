<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\DonorProfile;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds users across all roles.
 * Depends on: RolesAndPermissionsSeeder, GeoSeeder.
 *
 * Production: creates known admin accounts.
 * Development: also creates faker-generated test users.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached permissions to avoid stale cache issues
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $egypt = Country::where('iso2', 'EG')->first();
        $cairo = $egypt
            ? State::where('country_id', $egypt->id)->where('name', 'Cairo')->first()
            : null;

        // ─── Super Admin (always created) ────────────────────────────────────

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@newegypt.org'],
            [
                'name'              => 'Super Admin',
                'email_verified_at' => now(),
                'phone'             => '+10000000001',
                'password'          => Hash::make('password'),
                'address'           => 'New Egypt HQ',
                'country_id'        => $egypt?->id,
                'state_id'          => $cairo?->id,
            ]
        );
        $superAdmin->assignRole('super_admin');

        // ─── Known Staff Members (always created) ────────────────────────────

        $staffUsers = [
            ['name' => 'Mohamed Staff',    'email' => 'staff@newegypt.org',       'phone' => '+10000000002'],
            ['name' => 'Ahmed Field',      'email' => 'fieldworker@newegypt.org', 'phone' => '+10000000003'],
        ];

        foreach ($staffUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'email_verified_at' => now(),
                    'password'          => Hash::make('password'),
                    'country_id'        => $egypt?->id,
                    'state_id'          => $cairo?->id,
                ])
            );
            $user->assignRole(str_contains($data['email'], 'field') ? 'field_worker' : 'staff');
        }

        // ─── Dev / Test data only ────────────────────────────────────────────

        if (app()->environment(['local', 'testing'])) {
            // Additional staff
            User::factory()
                ->count(5)
                ->staff()
                ->create();

            // Field workers
            User::factory()
                ->count(10)
                ->fieldWorker()
                ->create();

            // Donors — factory state also creates DonorProfile
            User::factory()
                ->count(30)
                ->donor()
                ->create();

            // A few anonymous / unverified users
            User::factory()
                ->count(5)
                ->unverified()
                ->create();
        }

        $this->command->info('✅ Users seeded (' . User::count() . ' total).');
    }
}
