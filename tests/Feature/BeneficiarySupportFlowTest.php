<?php

namespace Tests\Feature;

use App\Models\AidItem;
use App\Models\Beneficiary;
use App\Models\BeneficiarySupport;
use App\Models\BeneficiaryUserAccess;
use App\Models\Campaign;
use App\Models\CampaignExpense;
use App\Models\Currency;
use App\Models\ReportAccessLog;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BeneficiarySupportFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        Currency::factory()->default()->create(['code' => 'USD']);
    }

    public function test_recording_support_creates_support_and_items_without_creating_ledger_rows(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $campaign = Campaign::factory()->active()->create();
        $beneficiary = Beneficiary::factory()->individual()->active()->create(['created_by' => $staff->id]);
        $aidItem = AidItem::factory()->create(['default_unit_cost' => 1500]);

        $beforeTransactions = Transaction::count();

        $this->actingAs($staff)
            ->post(route('admin.beneficiary-supports.store'), [
                'campaign_id' => $campaign->id,
                'beneficiary_id' => $beneficiary->id,
                'supported_at' => now()->toDateString(),
                'status' => 'delivered',
                'items' => [
                    [
                        'aid_item_id' => $aidItem->id,
                        'item_name_snapshot' => 'Food Box',
                        'quantity' => 2,
                        'unit_cost' => 1500,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.campaigns.beneficiary-report', $campaign));

        $support = BeneficiarySupport::query()->first();
        $this->assertNotNull($support);
        $this->assertSame($campaign->id, $support->campaign_id);
        $this->assertSame($beneficiary->id, $support->beneficiary_id);
        $this->assertSame(3000, $support->items()->first()->total_cost);
        $this->assertSame($beforeTransactions, Transaction::count());
    }

    public function test_campaign_expense_must_belong_to_selected_campaign(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $campaign = Campaign::factory()->active()->create();
        $otherCampaign = Campaign::factory()->active()->create();
        $beneficiary = Beneficiary::factory()->individual()->active()->create(['created_by' => $staff->id]);
        $aidItem = AidItem::factory()->create();
        $otherCampaignExpense = CampaignExpense::factory()->create(['campaign_id' => $otherCampaign->id]);

        $this->actingAs($staff)
            ->post(route('admin.beneficiary-supports.store'), [
                'campaign_id' => $campaign->id,
                'beneficiary_id' => $beneficiary->id,
                'supported_at' => now()->toDateString(),
                'status' => 'delivered',
                'items' => [
                    [
                        'aid_item_id' => $aidItem->id,
                        'item_name_snapshot' => 'Line Item',
                        'quantity' => 1,
                        'unit_cost' => 1000,
                        'campaign_expense_id' => $otherCampaignExpense->id,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items.0.campaign_expense_id');
    }

    public function test_user_without_beneficiary_grant_sees_reference_code_in_campaign_report(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('staff');

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $campaign = Campaign::factory()->active()->create();
        $beneficiary = Beneficiary::factory()->individual()->active()->create(['created_by' => $admin->id]);

        $support = BeneficiarySupport::factory()->create([
            'campaign_id' => $campaign->id,
            'beneficiary_id' => $beneficiary->id,
            'created_by' => $admin->id,
        ]);
        $support->items()->create([
            'item_name_snapshot' => 'Food Box',
            'quantity' => 1,
            'unit_cost' => 5000,
            'total_cost' => 5000,
            'currency_id' => Currency::query()->value('id'),
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.campaigns.beneficiary-report', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/campaign-beneficiary-report')
                ->where('rows.data.0.beneficiary_name', $beneficiary->code)
            );
    }

    public function test_per_beneficiary_report_requires_identity_access_and_exports_are_audited(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('staff');

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $campaign = Campaign::factory()->active()->create();
        $beneficiary = Beneficiary::factory()->individual()->active()->create(['created_by' => $admin->id]);
        $support = BeneficiarySupport::factory()->create([
            'campaign_id' => $campaign->id,
            'beneficiary_id' => $beneficiary->id,
            'created_by' => $admin->id,
        ]);
        $support->items()->create([
            'item_name_snapshot' => 'Medical Session',
            'quantity' => 3,
            'unit_cost' => 2000,
            'total_cost' => 6000,
            'currency_id' => Currency::query()->value('id'),
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.beneficiaries.support-report', $beneficiary))
            ->assertForbidden();

        BeneficiaryUserAccess::factory()->permanent()->allFields()->create([
            'beneficiary_id' => $beneficiary->id,
            'user_id' => $viewer->id,
            'granted_by' => $admin->id,
            'granted_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.beneficiaries.support-report', $beneficiary))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('admin.beneficiaries.support-report', [
                'beneficiary' => $beneficiary->id,
                'format' => 'csv',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('report_access_logs', [
            'report_key' => 'beneficiary_support_report',
            'scope_type' => 'beneficiary',
            'scope_id' => $beneficiary->id,
            'action' => 'export',
            'user_id' => $viewer->id,
        ]);
        $this->assertGreaterThan(0, ReportAccessLog::count());
    }
}
