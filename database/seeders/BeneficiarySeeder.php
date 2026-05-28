<?php

namespace Database\Seeders;

use App\Enums\AidType;
use App\Enums\AssessmentStatus;
use App\Enums\BeneficiaryStatus;
use App\Enums\BeneficiaryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryAssessment;
use App\Models\BeneficiaryFamily;
use App\Models\BeneficiaryIndividual;
use App\Models\BeneficiaryUserAccess;
use App\Models\Campaign;
use App\Models\User;
use Database\Factories\BeneficiaryUserAccessFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds all beneficiary types with profiles, family members,
 * assessments, access control grants, and campaign linkage.
 *
 * Depends on: UserSeeder, GeoSeeder, CampaignSeeder.
 */
class BeneficiarySeeder extends Seeder
{
    public function run(): void
    {
        $fieldWorkers = User::role('field_worker')->get();
        $superAdmin   = User::role('super_admin')->first();
        $staffUsers   = User::role('staff')->get();
        $campaigns    = Campaign::active()->orWhere('status', 'completed')->get();

        // ─── Individual beneficiaries (adults + children) ─────────────────────

        // Children — orphan cases (most linked to campaigns)
        $children = Beneficiary::factory()
            ->count(20)
            ->individual()
            ->active()
            ->create(['created_by' => $fieldWorkers->random()->id]);

        // Update individual profiles to child subtype for half of them
        BeneficiaryIndividual::whereIn(
            'beneficiary_id',
            $children->take(12)->pluck('id')
        )->update(['subtype' => 'child']);

        // Adult individuals (medical cases, elderly)
        Beneficiary::factory()
            ->count(15)
            ->individual()
            ->active()
            ->create(['created_by' => $fieldWorkers->random()->id]);

        // Pending assessment individuals
        Beneficiary::factory()
            ->count(8)
            ->individual()
            ->pending()
            ->create(['created_by' => $fieldWorkers->random()->id]);

        // ─── Family beneficiaries ─────────────────────────────────────────────

        $families = collect();

        // Active families with members
        for ($i = 0; $i < 20; $i++) {
            $beneficiary = Beneficiary::factory()
                ->active()
                ->create([
                    'type'       => BeneficiaryType::Family,
                    'created_by' => $fieldWorkers->random()->id,
                ]);

            // Create family profile with 2–6 members
            $memberCount = rand(2, 6);
            $family = BeneficiaryFamily::factory()
                ->for($beneficiary)
                ->withMembers($memberCount)
                ->create();

            $families->push($beneficiary);
        }

        // Pending families
        Beneficiary::factory()
            ->count(5)
            ->family()
            ->pending()
            ->create(['created_by' => $fieldWorkers->random()->id]);

        // ─── Organization beneficiaries ───────────────────────────────────────

        Beneficiary::factory()
            ->count(5)
            ->organization()
            ->active()
            ->create(['created_by' => $staffUsers->random()->id]);

        // ─── Assessments ──────────────────────────────────────────────────────
        // Every beneficiary needs at least one assessment

        $allBeneficiaries = Beneficiary::all();

        foreach ($allBeneficiaries as $beneficiary) {
            $assessorId = $fieldWorkers->isNotEmpty()
                ? $fieldWorkers->random()->id
                : $superAdmin->id;

            // Determine assessment status based on beneficiary status
            $assessmentStatus = match($beneficiary->status) {
                BeneficiaryStatus::Active   => AssessmentStatus::Approved,
                BeneficiaryStatus::Inactive => AssessmentStatus::Rejected,
                default                     => AssessmentStatus::Pending,
            };

            BeneficiaryAssessment::factory()
                ->for($beneficiary)
                ->create([
                    'assessed_by' => $assessorId,
                    'status'      => $assessmentStatus,
                    'reviewed_by' => $assessmentStatus->isReviewed() ? $superAdmin->id : null,
                    'reviewed_at' => $assessmentStatus->isReviewed() ? now()->subDays(rand(1, 90)) : null,
                ]);

            // Some beneficiaries have follow-up assessments
            if (fake()->boolean(20) && $beneficiary->status === BeneficiaryStatus::Active) {
                BeneficiaryAssessment::factory()
                    ->for($beneficiary)
                    ->pending()
                    ->create(['assessed_by' => $assessorId]);
            }
        }

        // ─── Beneficiary User Access Grants ───────────────────────────────────
        // Super admin grants field workers access to specific beneficiaries

        $activeBeneficiaries = Beneficiary::active()->get();
        $accessibleUsers     = User::role(['staff', 'field_worker'])->get();

        foreach ($activeBeneficiaries->random(min(30, $activeBeneficiaries->count())) as $beneficiary) {
            // Grant 1–3 users access per beneficiary
            $grantedUsers = $accessibleUsers->random(rand(1, min(3, $accessibleUsers->count())));

            foreach ($grantedUsers as $user) {
                // Skip if grant already exists (unique constraint)
                $exists = BeneficiaryUserAccess::where([
                    'beneficiary_id' => $beneficiary->id,
                    'user_id'        => $user->id,
                ])->exists();

                if ($exists) {
                    continue;
                }

                // Field workers get limited fields; staff get more
                $allowedFields = $user->hasRole('field_worker')
                    ? ['first_name', 'last_name', 'phone', 'address', 'health_status']
                    : BeneficiaryUserAccessFactory::ALL_FIELDS;

                BeneficiaryUserAccess::create([
                    'beneficiary_id'     => $beneficiary->id,
                    'user_id'            => $user->id,
                    'granted_by'         => $superAdmin->id,
                    'allowed_fields'     => $allowedFields,
                    'expires_in_seconds' => fake()->boolean(60)
                        ? fake()->randomElement([604_800, 2_592_000, 7_776_000])
                        : null,
                    'granted_at'         => now()->subDays(rand(1, 30)),
                    'grant_reason'       => 'Assigned field case',
                ]);
            }
        }

        // ─── Campaign ↔ Beneficiary links (pivot with aid data) ──────────────

        if ($campaigns->isEmpty()) {
            $this->command->warn('⚠  No campaigns found — skipping campaign_beneficiaries pivot.');
        } else {
            $this->seedCampaignBeneficiaryPivot($activeBeneficiaries, $campaigns);
        }

        $this->command->info('✅ Beneficiaries seeded (' . Beneficiary::count() . ' total).');
        $this->command->info('   — Individuals: ' . Beneficiary::ofType(BeneficiaryType::Individual)->count());
        $this->command->info('   — Families:    ' . Beneficiary::ofType(BeneficiaryType::Family)->count());
        $this->command->info('   — Orgs:        ' . Beneficiary::ofType(BeneficiaryType::Organization)->count());
        $this->command->info('   — Assessments: ' . BeneficiaryAssessment::count());
        $this->command->info('   — Access grants: ' . BeneficiaryUserAccess::count());
    }

    /**
     * Populate campaign_beneficiaries pivot with realistic aid data.
     * Uses chunked attach() to avoid memory issues with large datasets.
     */
    private function seedCampaignBeneficiaryPivot(
        \Illuminate\Support\Collection $beneficiaries,
        \Illuminate\Support\Collection $campaigns
    ): void {
        $pivotRows = [];

        foreach ($campaigns->random(min(20, $campaigns->count())) as $campaign) {
            // Each campaign supports 1–8 beneficiaries
            $campaignBeneficiaries = $beneficiaries->random(rand(1, min(8, $beneficiaries->count())));

            foreach ($campaignBeneficiaries as $beneficiary) {
                // Avoid duplicates
                $key = "{$campaign->id}_{$beneficiary->id}";
                if (isset($pivotRows[$key])) {
                    continue;
                }

                $aidType   = fake()->randomElement(AidType::cases());
                $aidAmount = fake()->randomFloat(2, 50, 5_000);

                $pivotRows[$key] = [
                    'campaign_id'     => $campaign->id,
                    'beneficiary_id'  => $beneficiary->id,
                    'aid_amount'      => $aidAmount,
                    'aid_type'        => $aidType->value,
                    'aid_description' => fake()->optional(0.7)->sentence(),
                    'aid_date'        => fake()->dateTimeBetween(
                        $campaign->start_date ?? '-1 year',
                        $campaign->end_date ?? 'now'
                    )->format('Y-m-d'),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
        }

        // Bulk insert pivot rows in chunks for performance
        collect($pivotRows)
            ->values()
            ->chunk(100)
            ->each(fn ($chunk) => DB::table('campaign_beneficiaries')->insert($chunk->all()));

        $count = DB::table('campaign_beneficiaries')->count();
        $this->command->info("   — Campaign-beneficiary links: {$count}");
    }
}
