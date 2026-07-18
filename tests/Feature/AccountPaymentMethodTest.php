<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\DTOs\PaymentMethodData;
use App\Models\DonorPaymentMethod;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class AccountPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    public function test_a_donor_can_create_a_setup_intent(): void
    {
        $user = User::factory()->donor()->create();

        $response = $this->actingAs($user)->postJson(route('account.payment-methods.setup-intent'));

        $response->assertOk();
        $response->assertJsonStructure(['client_secret']);
    }

    public function test_a_payment_method_is_only_saved_after_confirmation(): void
    {
        $user = User::factory()->donor()->create();
        $this->gateway->paymentMethodsById['pm_test_confirmed'] = new PaymentMethodData(
            id: 'pm_test_confirmed',
            brand: 'visa',
            last4: '4242',
            expMonth: 12,
            expYear: 2030,
        );

        $this->assertSame(0, DonorPaymentMethod::query()->count());

        $response = $this->actingAs($user)->post(route('account.payment-methods.store'), [
            'payment_method_id' => 'pm_test_confirmed',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, DonorPaymentMethod::query()->where('user_id', $user->id)->count());

        $method = DonorPaymentMethod::query()->where('user_id', $user->id)->first();
        $this->assertSame('4242', $method->last4);
        $this->assertTrue($method->is_default);
    }

    public function test_a_donor_can_remove_their_own_payment_method(): void
    {
        $user = User::factory()->donor()->create();
        $method = DonorPaymentMethod::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('account.payment-methods.destroy', $method));

        $response->assertRedirect();
        $this->assertSame(0, DonorPaymentMethod::query()->where('id', $method->id)->count());
        $this->assertContains($method->stripe_payment_method_id, $this->gateway->detachedPaymentMethods);
    }

    public function test_a_donor_cannot_remove_another_donors_payment_method(): void
    {
        $owner = User::factory()->donor()->create();
        $intruder = User::factory()->donor()->create();
        $method = DonorPaymentMethod::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->delete(route('account.payment-methods.destroy', $method));

        $response->assertForbidden();
        $this->assertSame(1, DonorPaymentMethod::query()->where('id', $method->id)->count());
    }

    public function test_a_donor_can_set_a_payment_method_as_default(): void
    {
        $user = User::factory()->donor()->create();
        $first = DonorPaymentMethod::factory()->for($user)->default()->create();
        $second = DonorPaymentMethod::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('account.payment-methods.default', $second));

        $response->assertRedirect();
        $this->assertTrue($second->refresh()->is_default);
        $this->assertFalse($first->refresh()->is_default);
    }
}
