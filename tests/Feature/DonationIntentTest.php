<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Transaction;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class DonationIntentTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    private function createDonatableCampaign(): Campaign
    {
        return Campaign::factory()->create([
            'status' => 'active',
            'is_public' => true,
            'open_donation_form' => true,
        ]);
    }

    public function test_intent_validation_rejects_both_campaign_and_general(): void
    {
        $campaign = $this->createDonatableCampaign();

        $this->postJson(route('donations.intent'), [
            'campaign_id' => $campaign->id,
            'is_general' => true,
            'amount' => 5000,
            'donor_covers_fee' => false,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane@example.com',
        ])->assertUnprocessable();
    }

    public function test_intent_validation_rejects_neither_campaign_nor_general(): void
    {
        $this->postJson(route('donations.intent'), [
            'is_general' => false,
            'amount' => 5000,
            'donor_covers_fee' => false,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane@example.com',
        ])->assertUnprocessable();
    }

    public function test_intent_gross_up_when_donor_covers_fee(): void
    {
        $campaign = $this->createDonatableCampaign();
        $transactionCount = Transaction::count();

        $response = $this->postJson(route('donations.intent'), [
            'campaign_id' => $campaign->id,
            'is_general' => false,
            'amount' => 10000,
            'donor_covers_fee' => true,
            'first_name' => 'Jane',
            'last_name' => 'Donor',
            'email' => 'jane-covered@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('amount', 10000)
            ->assertJsonPath('chargeCents', 10330);

        $this->assertSame($transactionCount, Transaction::count());

        $donation = Donation::query()->where('stripe_payment_intent_id', $response->json('paymentIntentId'))->first();
        $this->assertNotNull($donation);
        $this->assertSame(DonationStatus::Pending, $donation->status);
        $this->assertNull($donation->transaction_id);
    }

    public function test_intent_without_fee_cover_uses_gift_as_charge(): void
    {
        $campaign = $this->createDonatableCampaign();

        $response = $this->postJson(route('donations.intent'), [
            'campaign_id' => $campaign->id,
            'is_general' => false,
            'amount' => 5000,
            'donor_covers_fee' => false,
            'first_name' => 'John',
            'last_name' => 'Donor',
            'email' => 'john@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('chargeCents', 5000);
    }
}
