<?php

namespace Tests\Unit;

use InvalidArgumentException;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeClientBindingTest extends TestCase
{
    public function test_it_resolves_stripe_client_with_secret_from_config(): void
    {
        config(['services.stripe.secret' => 'sk_test_binding_value']);
        $this->app->forgetInstance(StripeClient::class);

        $client = $this->app->make(StripeClient::class);

        $this->assertSame('sk_test_binding_value', $client->getApiKey());
    }

    public function test_it_throws_when_stripe_secret_is_missing(): void
    {
        config(['services.stripe.secret' => null]);
        $this->app->forgetInstance(StripeClient::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stripe secret key is not configured');

        $this->app->make(StripeClient::class);
    }
}
