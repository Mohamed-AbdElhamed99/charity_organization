<?php

namespace Tests\Unit;

use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class GrossUpFeeTest extends TestCase
{
    public function test_gross_up_covers_fee_for_standard_amount(): void
    {
        config([
            'services.stripe.fee_percent' => 2.9,
            'services.stripe.fee_fixed_cents' => 30,
            'services.stripe.fee_buffer_cents' => 0,
        ]);

        $gateway = new FakePaymentGateway;

        $chargeCents = $gateway->grossUpForFee(10000, 'USD');

        $this->assertSame(10330, $chargeCents);
    }
}
