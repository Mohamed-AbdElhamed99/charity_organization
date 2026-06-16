<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\BeneficiarySupport;
use App\Models\BeneficiaryUserAccess;
use App\Models\Campaign;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BeneficiarySupportReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        Currency::factory()->default()->create(['code' => 'USD']);
    }

    public function test_campaign_report_aggregates_and_export_rows_match_table_rows(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $campaign = Campaign::factory()->active()->create();
        $beneficiaryA = Beneficiary::factory()->individual()->active()->create(['created_by' => $admin->id]);
        $beneficiaryB = Beneficiary::factory()->family()->active()->create(['created_by' => $admin->id]);

        $supportA = BeneficiarySupport::factory()->create([
            'campaign_id' => $campaign->id,
            'beneficiary_id' => $beneficiaryA->id,
            'created_by' => $admin->id,
            'status' => 'delivered',
        ]);
        $supportA->items()->createMany([
            [
                'item_name_snapshot' => 'Food Box',
                'quantity' => 2,
                'unit_cost' => 1000,
                'total_cost' => 2000,
                'currency_id' => Currency::query()->value('id'),
            ],
            [
                'item_name_snapshot' => 'Winter Jacket',
                'quantity' => 1,
                'unit_cost' => 5000,
                'total_cost' => 5000,
                'currency_id' => Currency::query()->value('id'),
            ],
        ]);

        $supportB = BeneficiarySupport::factory()->create([
            'campaign_id' => $campaign->id,
            'beneficiary_id' => $beneficiaryB->id,
            'created_by' => $admin->id,
            'status' => 'delivered',
        ]);
        $supportB->items()->create([
            'item_name_snapshot' => 'Food Box',
            'quantity' => 1,
            'unit_cost' => 1000,
            'total_cost' => 1000,
            'currency_id' => Currency::query()->value('id'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.campaigns.beneficiary-report', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/campaign-beneficiary-report')
                ->where('summary.distinct_beneficiaries', 2)
                ->where('summary.support_events', 2)
                ->where('summary.total_items', 4)
                ->where('summary.total_cost', 8000)
                ->has('rows.data', 2)
            );

        $response = $this->actingAs($admin)
            ->get(route('admin.campaigns.beneficiary-report', [
                'campaign' => $campaign->id,
                'format' => 'csv',
            ]))
            ->assertOk();

        $csv = $response->streamedContent();
        $lines = array_values(array_filter(array_map('trim', explode("\n", $csv))));
        $this->assertCount(3, $lines); // heading + 2 table rows
    }

    public function test_beneficiary_report_groups_by_campaign_and_shows_grand_total(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $beneficiary = Beneficiary::factory()->individual()->active()->create(['created_by' => $admin->id]);
        BeneficiaryUserAccess::factory()->permanent()->allFields()->create([
            'beneficiary_id' => $beneficiary->id,
            'user_id' => $staff->id,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $campaignOne = Campaign::factory()->active()->create();
        $campaignTwo = Campaign::factory()->active()->create();

        $supportOne = BeneficiarySupport::factory()->create([
            'campaign_id' => $campaignOne->id,
            'beneficiary_id' => $beneficiary->id,
            'created_by' => $admin->id,
        ]);
        $supportOne->items()->create([
            'item_name_snapshot' => 'Food Box',
            'quantity' => 2,
            'unit_cost' => 1200,
            'total_cost' => 2400,
            'currency_id' => Currency::query()->value('id'),
        ]);

        $supportTwo = BeneficiarySupport::factory()->create([
            'campaign_id' => $campaignTwo->id,
            'beneficiary_id' => $beneficiary->id,
            'created_by' => $admin->id,
        ]);
        $supportTwo->items()->create([
            'item_name_snapshot' => 'Medical Session',
            'quantity' => 1,
            'unit_cost' => 3500,
            'total_cost' => 3500,
            'currency_id' => Currency::query()->value('id'),
        ]);

        $this->actingAs($staff)
            ->get(route('admin.beneficiaries.support-report', $beneficiary))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/beneficiary-support-report')
                ->where('totals.grand_total_cost', 5900)
                ->where('totals.total_items', 3)
                ->where('totals.campaigns_count', 2)
                ->has('grouped', 2)
            );
    }

    public function test_export_requires_export_beneficiary_reports_permission(): void
    {
        $fieldWorker = User::factory()->create();
        $fieldWorker->assignRole('field_worker');

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $campaign = Campaign::factory()->active()->create();
        $beneficiary = Beneficiary::factory()->individual()->active()->create(['created_by' => $admin->id]);
        BeneficiaryUserAccess::factory()->permanent()->allFields()->create([
            'beneficiary_id' => $beneficiary->id,
            'user_id' => $fieldWorker->id,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        BeneficiarySupport::factory()->create([
            'campaign_id' => $campaign->id,
            'beneficiary_id' => $beneficiary->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($fieldWorker)
            ->get(route('admin.beneficiaries.support-report', [
                'beneficiary' => $beneficiary->id,
                'format' => 'csv',
            ]))
            ->assertForbidden();
    }
}
