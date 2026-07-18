<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Enums\DonationStatus;
use App\Enums\DonationSubscriptionStatus;
use App\Models\Account;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\DonationSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DonationWebhookService;
use App\Services\Payments\StripeGateway;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Event;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);

        $this->gateway = new FakePaymentGateway;
        $this->gateway->actualFeeCents = 300;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    public function test_webhook_rejects_invalid_signature_via_controller(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_fake',
            'services.stripe.webhook_secret' => 'whsec_test',
        ]);
        $this->app->instance(PaymentGateway::class, new StripeGateway);

        $this->post(route('webhooks.stripe'), [], [
            'Stripe-Signature' => 'invalid',
        ])->assertStatus(400);
    }

    public function test_webhook_success_is_idempotent_and_credits_campaign_once(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'status' => 'active',
            'is_public' => true,
            'open_donation_form' => true,
            'collected_amount' => 0,
        ]);

        $donation = Donation::query()->create([
            'transaction_id' => null,
            'donor_id' => $user->id,
            'campaign_id' => $campaign->id,
            'is_general' => false,
            'amount' => 10000,
            'status' => DonationStatus::Pending,
            'stripe_status' => 'pending',
            'stripe_payment_intent_id' => 'pi_test_webhook_1',
            'donor_covers_fee' => false,
            'is_anonymous' => false,
        ]);

        $eventPayload = [
            'id' => 'evt_test_1',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_webhook_1',
                    'amount_received' => 10330,
                    'latest_charge' => 'ch_test_1',
                    'metadata' => [],
                ],
            ],
        ];

        $service = app(DonationWebhookService::class);
        $event = Event::constructFrom($eventPayload);

        $service->handle($event);
        $service->handle($event);

        $this->assertSame(1, Transaction::query()->where('transaction_type', 'donation')->count());
        $donation->refresh();
        $this->assertSame(DonationStatus::Succeeded, $donation->status);
        $this->assertNotNull($donation->transaction_id);

        $campaign->refresh();
        $this->assertSame(10000, $campaign->collected_amount);

        $transaction = $donation->transaction;
        $this->assertSame('103.30', (string) $transaction->gross_amount);
        $this->assertSame('3.00', (string) $transaction->fee_amount);
        $this->assertSame('100.30', (string) $transaction->net_amount);
    }

    public function test_failed_payment_marks_donation_failed_without_ledger_write(): void
    {
        $donation = Donation::factory()->pending()->create([
            'stripe_payment_intent_id' => 'pi_test_failed',
        ]);

        $eventPayload = [
            'id' => 'evt_test_failed',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_failed',
                ],
            ],
        ];

        app(DonationWebhookService::class)->handle(Event::constructFrom($eventPayload));

        $donation->refresh();
        $this->assertSame(DonationStatus::Failed, $donation->status);
        $this->assertNull($donation->transaction_id);
    }

    public function test_running_balance_updates_sequentially(): void
    {
        $account = Account::query()->where('name', 'Chase Business Checking')->firstOrFail();
        $user = User::factory()->create();

        $first = Donation::query()->create([
            'transaction_id' => null,
            'donor_id' => $user->id,
            'is_general' => true,
            'amount' => 5000,
            'status' => DonationStatus::Pending,
            'stripe_status' => 'pending',
            'stripe_payment_intent_id' => 'pi_seq_1',
            'donor_covers_fee' => false,
            'is_anonymous' => false,
        ]);

        $second = Donation::query()->create([
            'transaction_id' => null,
            'donor_id' => $user->id,
            'is_general' => true,
            'amount' => 3000,
            'status' => DonationStatus::Pending,
            'stripe_status' => 'pending',
            'stripe_payment_intent_id' => 'pi_seq_2',
            'donor_covers_fee' => false,
            'is_anonymous' => false,
        ]);

        $service = app(DonationWebhookService::class);

        $service->handle(Event::constructFrom([
            'id' => 'evt_seq_1',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_seq_1',
                    'amount_received' => 5000,
                    'latest_charge' => 'ch_seq_1',
                    'metadata' => [],
                ],
            ],
        ]));

        $this->gateway->actualFeeCents = 200;
        $service->handle(Event::constructFrom([
            'id' => 'evt_seq_2',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_seq_2',
                    'amount_received' => 3000,
                    'latest_charge' => 'ch_seq_2',
                    'metadata' => [],
                ],
            ],
        ]));

        $first->refresh();
        $second->refresh();

        $opening = (string) $account->opening_balance;
        $firstBalance = (string) $first->transaction->running_balance;
        $secondBalance = (string) $second->transaction->running_balance;

        $this->assertSame(
            bcadd(bcadd($opening, '47.00', 2), '28.00', 2),
            $secondBalance
        );
        $this->assertSame(bcadd($opening, '47.00', 2), $firstBalance);
    }

    public function test_invoice_paid_creates_donation_and_credits_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => 'active',
            'is_public' => true,
            'open_donation_form' => true,
            'collected_amount' => 0,
        ]);

        $subscription = DonationSubscription::factory()->forCampaign($campaign)->create([
            'amount_cents' => 7500,
            'stripe_subscription_id' => 'sub_invoice_test_1',
        ]);

        $eventPayload = [
            'id' => 'evt_invoice_paid_1',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'object' => 'invoice',
                    'id' => 'in_test_1',
                    'amount_paid' => 7500,
                    'parent' => [
                        'subscription_details' => [
                            'subscription' => 'sub_invoice_test_1',
                        ],
                    ],
                ],
            ],
        ];

        app(DonationWebhookService::class)->handle(Event::constructFrom($eventPayload));

        $donation = Donation::query()->where('donation_subscription_id', $subscription->id)->first();
        $this->assertNotNull($donation);
        $this->assertTrue($donation->is_recurring);
        $this->assertSame(DonationStatus::Succeeded, $donation->status);
        $this->assertSame('in_test_1', $donation->stripe_invoice_id);
        $this->assertNotNull($donation->transaction_id);

        $campaign->refresh();
        $this->assertSame(7500, $campaign->collected_amount);
    }

    public function test_invoice_paid_splits_one_invoice_into_multiple_donations_with_apportioned_fees(): void
    {
        $campaignOne = Campaign::factory()->create([
            'status' => 'active',
            'is_public' => true,
            'open_donation_form' => true,
            'collected_amount' => 0,
        ]);
        $campaignTwo = Campaign::factory()->create([
            'status' => 'active',
            'is_public' => true,
            'open_donation_form' => true,
            'collected_amount' => 0,
        ]);

        $subscription = DonationSubscription::factory()->create([
            'amount_cents' => 10000,
            'stripe_subscription_id' => 'sub_split_invoice_1',
        ]);
        $subscription->allocations()->delete();
        $subscription->allocations()->create(['campaign_id' => $campaignOne->id, 'is_general' => false, 'amount_cents' => 3000]);
        $subscription->allocations()->create(['campaign_id' => $campaignTwo->id, 'is_general' => false, 'amount_cents' => 4000]);
        $subscription->allocations()->create(['campaign_id' => null, 'is_general' => true, 'amount_cents' => 3000]);

        $this->gateway->actualFeeCents = 300;

        $eventPayload = [
            'id' => 'evt_invoice_split_1',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'object' => 'invoice',
                    'id' => 'in_split_test_1',
                    'amount_paid' => 10000,
                    'parent' => [
                        'subscription_details' => [
                            'subscription' => 'sub_split_invoice_1',
                        ],
                    ],
                ],
            ],
        ];

        app(DonationWebhookService::class)->handle(Event::constructFrom($eventPayload));

        $donations = Donation::query()->where('donation_subscription_id', $subscription->id)->get();
        $this->assertCount(3, $donations);
        $this->assertTrue($donations->every(fn (Donation $donation) => $donation->status === DonationStatus::Succeeded));
        $this->assertTrue($donations->every(fn (Donation $donation) => $donation->is_recurring));
        $this->assertTrue($donations->every(fn (Donation $donation) => $donation->stripe_invoice_id === 'in_split_test_1'));

        $totalFeeCents = $donations->sum(fn (Donation $donation) => (int) round($donation->transaction->fee_amount * 100));
        $this->assertSame(300, $totalFeeCents);

        $totalGrossCents = $donations->sum(fn (Donation $donation) => (int) round($donation->transaction->gross_amount * 100));
        $this->assertSame(10000, $totalGrossCents);

        $campaignOne->refresh();
        $campaignTwo->refresh();
        $this->assertSame(3000, $campaignOne->collected_amount);
        $this->assertSame(4000, $campaignTwo->collected_amount);
    }

    public function test_invoice_paid_is_idempotent_for_the_same_invoice(): void
    {
        $subscription = DonationSubscription::factory()->create([
            'amount_cents' => 4000,
            'stripe_subscription_id' => 'sub_invoice_test_2',
        ]);

        $eventPayload = [
            'id' => 'evt_invoice_paid_2',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'object' => 'invoice',
                    'id' => 'in_test_2',
                    'amount_paid' => 4000,
                    'parent' => [
                        'subscription_details' => [
                            'subscription' => 'sub_invoice_test_2',
                        ],
                    ],
                ],
            ],
        ];

        $service = app(DonationWebhookService::class);
        $service->handle(Event::constructFrom($eventPayload));

        // A duplicate delivery of the webhook uses a new event id but the same invoice.
        $eventPayload['id'] = 'evt_invoice_paid_2_retry';
        $service->handle(Event::constructFrom($eventPayload));

        $this->assertSame(
            1,
            Donation::query()->where('donation_subscription_id', $subscription->id)->count()
        );
    }

    public function test_invoice_payment_failed_marks_subscription_past_due(): void
    {
        $subscription = DonationSubscription::factory()->create([
            'stripe_subscription_id' => 'sub_failed_1',
        ]);

        $eventPayload = [
            'id' => 'evt_invoice_failed_1',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'object' => 'invoice',
                    'id' => 'in_failed_1',
                    'parent' => [
                        'subscription_details' => [
                            'subscription' => 'sub_failed_1',
                        ],
                    ],
                ],
            ],
        ];

        app(DonationWebhookService::class)->handle(Event::constructFrom($eventPayload));

        $subscription->refresh();
        $this->assertSame(DonationSubscriptionStatus::PastDue, $subscription->status);
    }

    public function test_subscription_deleted_marks_subscription_canceled(): void
    {
        $subscription = DonationSubscription::factory()->create([
            'stripe_subscription_id' => 'sub_deleted_1',
        ]);

        $eventPayload = [
            'id' => 'evt_subscription_deleted_1',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'object' => 'subscription',
                    'id' => 'sub_deleted_1',
                ],
            ],
        ];

        app(DonationWebhookService::class)->handle(Event::constructFrom($eventPayload));

        $subscription->refresh();
        $this->assertSame(DonationSubscriptionStatus::Canceled, $subscription->status);
    }
}
