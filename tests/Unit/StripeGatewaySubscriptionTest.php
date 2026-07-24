<?php

namespace Tests\Unit;

use App\Enums\RecurrenceFrequency;
use App\Services\Payments\StripeGateway;
use Mockery;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeGatewaySubscriptionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_create_subscription_uses_payment_method_types_not_automatic_payment_methods(): void
    {
        config(['services.stripe.donation_product_id' => 'prod_test']);

        /** @var array<string, mixed>|null $captured */
        $captured = null;

        $subscriptions = Mockery::mock();
        $subscriptions->shouldReceive('create')
            ->once()
            ->withArgs(function (array $params) use (&$captured): bool {
                $captured = $params;

                return true;
            })
            ->andReturn((object) [
                'id' => 'sub_test',
                'billing_cycle_anchor' => 1_234_567_890,
                'latest_invoice' => (object) [
                    'confirmation_secret' => (object) [
                        'client_secret' => 'pi_test_secret_abc',
                    ],
                ],
            ]);

        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('subscriptions')->andReturn($subscriptions);
        $client->shouldReceive('__get')->with('subscriptions')->andReturn($subscriptions);

        $gateway = new StripeGateway($client);
        $result = $gateway->createSubscription(
            'cus_test',
            5000,
            'usd',
            RecurrenceFrequency::Monthly,
            ['donation_subscription_id' => '1'],
        );

        $this->assertSame('sub_test', $result->subscriptionId);
        $this->assertSame('pi_test_secret_abc', $result->clientSecret);
        $this->assertIsArray($captured);
        $this->assertArrayHasKey('payment_settings', $captured);
        $this->assertArrayNotHasKey('automatic_payment_methods', $captured['payment_settings']);
        $this->assertSame(['card', 'link'], $captured['payment_settings']['payment_method_types']);
        $this->assertSame('on_subscription', $captured['payment_settings']['save_default_payment_method']);
    }
}
