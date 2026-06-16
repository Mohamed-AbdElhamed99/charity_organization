<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class DonationsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);

        $this->withoutVite();
        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
    }

    private function createDonatableCampaign(array $attributes = []): Campaign
    {
        return Campaign::factory()->create(array_merge([
            'status' => 'active',
            'is_public' => true,
            'open_donation_form' => true,
        ], $attributes));
    }

    public function test_donations_index_lists_only_donatable_campaigns(): void
    {
        $donatable = $this->createDonatableCampaign(['title_en' => 'Open Campaign']);
        $closed = Campaign::factory()->create([
            'status' => 'active',
            'is_public' => true,
            'open_donation_form' => false,
            'title_en' => 'Closed Campaign',
        ]);

        $this->get(route('donations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/donations/donations-index')
                ->has('campaigns.data', 1)
                ->where('campaigns.data.0.id', $donatable->id)
                ->has('feeConfig')
                ->has('categories')
                ->has('filters')
            );
    }

    public function test_donations_index_filters_by_category_and_search(): void
    {
        $category = CampaignCategory::factory()->create();
        $otherCategory = CampaignCategory::factory()->create();

        $match = $this->createDonatableCampaign([
            'title_en' => 'Winter Relief Drive',
            'category_id' => $category->id,
        ]);
        $this->createDonatableCampaign([
            'title_en' => 'Summer School',
            'category_id' => $otherCategory->id,
        ]);

        $this->get(route('donations.index', ['category' => $category->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 1)
                ->where('campaigns.data.0.id', $match->id)
                ->where('filters.category', (string) $category->id)
            );

        $this->get(route('donations.index', ['search' => 'Winter']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 1)
                ->where('campaigns.data.0.id', $match->id)
                ->where('filters.search', 'Winter')
            );
    }

    public function test_donate_pages_include_fee_config_and_countries(): void
    {
        $campaign = $this->createDonatableCampaign();

        $this->get(route('donate.general'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/donations/donate')
                ->has('feeConfig')
                ->has('countries')
                ->where('feeConfig.percent', (float) config('services.stripe.fee_percent'))
            );

        $this->get(route('campaigns.donate', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('feeConfig')
                ->has('countries')
                ->where('campaign.id', $campaign->id)
            );
    }

    public function test_donation_status_endpoint_returns_expected_payload(): void
    {
        $campaign = $this->createDonatableCampaign();
        $donation = Donation::factory()->pending()->create([
            'campaign_id' => $campaign->id,
            'stripe_payment_intent_id' => 'pi_test_status',
            'amount' => 5000,
        ]);

        $this->getJson(route('donations.status', 'pi_test_status'))
            ->assertOk()
            ->assertJson([
                'status' => $donation->status?->value,
                'amount_cents' => 5000,
            ]);
    }

    public function test_donate_page_includes_csrf_meta_token(): void
    {
        $this->get(route('donate.general'))
            ->assertOk()
            ->assertSee('name="csrf-token"', false);
    }
}
