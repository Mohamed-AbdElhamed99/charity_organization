<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Master orchestrator.
 *
 * Seeder execution order (dependency chain):
 *
 *   1. RolesAndPermissionsSeeder   — Spatie roles & permissions (no dependencies)
 *   2. GeoSeeder                   — Countries & states (no dependencies)
 *   3. FinancialFoundationSeeder   — Currencies, accounts, payment methods (needs geo for country refs)
 *   4. UserSeeder                  — Users with roles + donor profiles (needs roles, geo)
 *   5. CampaignSeeder              — Categories + campaigns (needs users, geo)
 *   6. BeneficiarySeeder           — All beneficiary types + assessments + access grants + pivot (needs users, campaigns, geo)
 *   7. FinancialSeeder             — Donations, expenses, transfers, bank entries (needs users, campaigns, beneficiaries, accounts)
 *   8. CmsSeeder                   — News, about, sliders, contact us (needs users)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Starting New Egypt database seeding...');
        $this->command->newLine();

        $this->call([
            RolesAndPermissionsSeeder::class,   // 1 — Spatie roles/permissions
            GeoSeeder::class,                   // 2 — Countries & states
            FinancialFoundationSeeder::class,   // 3 — Currencies, accounts, payment methods
            UserSeeder::class,                  // 4 — Users + donor profiles
            CampaignSeeder::class,              // 5 — Campaign categories + campaigns
            BeneficiarySeeder::class,           // 6 — Beneficiaries + all sub-entities
            FinancialSeeder::class,             // 7 — Full financial dataset
            CmsSeeder::class,                   // 8 — CMS content
        ]);

        $this->command->newLine();
        $this->command->info('✅ New Egypt database seeding complete.');
    }
}
