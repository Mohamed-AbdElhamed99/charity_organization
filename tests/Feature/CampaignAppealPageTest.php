<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Campaign;
use App\Models\CampaignAppeal;
use App\Models\Donation;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class CampaignAppealPageTest extends TestCase
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

    private function createDonatableAppeal(array $appealOverrides = []): CampaignAppeal
    {
        $campaign = Campaign::factory()->active()->create();

        return CampaignAppeal::factory()->create(array_merge([
            'campaign_id' => $campaign->id,
            'slug' => 'ramadan-food-box',
            'gift_options' => [
                ['name_en' => 'Medium Turkey', 'name_ar' => 'ديك رومي متوسط', 'amount_cents' => 3000],
                ['name_en' => 'Halal Turkey', 'name_ar' => 'ديك رومي حلال', 'amount_cents' => 5000],
            ],
            'allow_custom_amount' => true,
            'is_active' => true,
        ], $appealOverrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function donorPayload(array $overrides = []): array
    {
        return array_merge([
            'is_general' => false,
            'donor_covers_fee' => false,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-appeal@example.com',
        ], $overrides);
    }

    public function test_public_give_page_renders_an_open_appeal(): void
    {
        config(['donations.zelle.email' => 'giver@newegyptgroup.com']);

        $appeal = $this->createDonatableAppeal([
            'subtitle_en' => 'Help a family enjoy a Thanksgiving meal.',
        ]);

        $this->get(route('appeals.show', $appeal->slug))
            ->assertOk()
            ->assertSee('property="og:description" content="Help a family enjoy a Thanksgiving meal."', false)
            ->assertSee('property="og:url" content="'.route('appeals.show', $appeal).'"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/appeals/appeal-show')
                ->where('appeal.slug', 'ramadan-food-box')
                ->where('appeal.gift_options.0.name', 'Medium Turkey')
                ->where('appeal.gift_options.0.amount_cents', 3000)
                ->where('appeal.gift_options.1.name', 'Halal Turkey')
                ->where('appeal.gift_options.1.amount_cents', 5000)
                ->where('organization.ein', '81-2019298')
                ->where('zelle.qrUrl', url('images/zelle-qr.png'))
                ->where('zelle.email', 'giver@newegyptgroup.com')
            );

        $this->assertFileExists(public_path('images/zelle-qr.png'));
    }

    public function test_give_page_is_hidden_when_the_appeal_or_campaign_is_closed(): void
    {
        $inactive = $this->createDonatableAppeal([
            'slug' => 'inactive-box',
            'is_active' => false,
        ]);

        $this->get(route('appeals.show', $inactive->slug))->assertNotFound();

        $closed = $this->createDonatableAppeal(['slug' => 'closed-campaign']);
        $closed->campaign->update(['open_donation_form' => false]);

        $this->get(route('appeals.show', $closed->slug))->assertNotFound();
    }

    public function test_campaign_story_lists_donatable_appeals(): void
    {
        $appeal = $this->createDonatableAppeal();
        CampaignAppeal::factory()->inactive()->create([
            'campaign_id' => $appeal->campaign_id,
            'slug' => 'hidden-blanket',
        ]);

        $this->get(route('campaigns.show', $appeal->campaign->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/campaigns/campaigns-show')
                ->has('appeals', 1)
                ->where('appeals.0.slug', 'ramadan-food-box')
            );
    }

    public function test_intent_stores_the_selected_gift_against_the_appeal_campaign(): void
    {
        $appeal = $this->createDonatableAppeal();

        $response = $this->postJson(route('donations.intent'), $this->donorPayload([
            'campaign_id' => $appeal->campaign_id,
            'appeal_id' => $appeal->id,
            'gift_option' => 1,
            'amount' => 5000,
        ]));

        $response->assertOk()
            ->assertJsonPath('amount', 5000);

        $donation = Donation::query()->where('stripe_payment_intent_id', $response->json('paymentIntentId'))->first();
        $this->assertNotNull($donation);
        $this->assertSame($appeal->id, $donation->appeal_id);
        $this->assertSame($appeal->campaign_id, $donation->campaign_id);
        $this->assertNull($donation->quantity);
        $this->assertSame(5000, $donation->unit_amount_cents);
        $this->assertSame('Halal Turkey', $donation->unit_label);
        $this->assertFalse($donation->is_general);
    }

    public function test_intent_rejects_a_total_that_does_not_match_the_selected_gift(): void
    {
        $appeal = $this->createDonatableAppeal();

        $this->postJson(route('donations.intent'), $this->donorPayload([
            'campaign_id' => $appeal->campaign_id,
            'appeal_id' => $appeal->id,
            'gift_option' => 0,
            'amount' => 5000,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_intent_accepts_a_custom_amount_when_the_appeal_allows_it(): void
    {
        $appeal = $this->createDonatableAppeal();

        $response = $this->postJson(route('donations.intent'), $this->donorPayload([
            'campaign_id' => $appeal->campaign_id,
            'appeal_id' => $appeal->id,
            'amount' => 2000,
            'email' => 'jane-custom@example.com',
        ]));

        $response->assertOk()->assertJsonPath('amount', 2000);

        $donation = Donation::query()->where('stripe_payment_intent_id', $response->json('paymentIntentId'))->first();
        $this->assertNotNull($donation);
        $this->assertSame($appeal->id, $donation->appeal_id);
        $this->assertNull($donation->quantity);
        $this->assertNull($donation->unit_amount_cents);
    }

    public function test_intent_rejects_a_custom_amount_when_the_appeal_requires_a_gift(): void
    {
        $appeal = $this->createDonatableAppeal([
            'slug' => 'blankets-only',
            'allow_custom_amount' => false,
        ]);

        $this->postJson(route('donations.intent'), $this->donorPayload([
            'campaign_id' => $appeal->campaign_id,
            'appeal_id' => $appeal->id,
            'amount' => 2000,
            'email' => 'jane-no-custom@example.com',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('gift_option');
    }

    public function test_intent_rejects_a_closed_appeal(): void
    {
        $appeal = $this->createDonatableAppeal([
            'slug' => 'closed-box',
            'is_active' => false,
        ]);

        $this->postJson(route('donations.intent'), $this->donorPayload([
            'campaign_id' => $appeal->campaign_id,
            'appeal_id' => $appeal->id,
            'gift_option' => 0,
            'amount' => 3000,
            'email' => 'jane-closed@example.com',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('appeal_id');
    }
}
