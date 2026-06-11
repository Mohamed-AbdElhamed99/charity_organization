<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentMethodControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FinancialFoundationSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_guests_cannot_access_payment_methods_index(): void
    {
        $this->get(route('admin.payment-methods.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_payment_methods_index(): void
    {
        $user = $this->createAuthorizedUser();
        PaymentMethod::factory()->count(2)->sequence(
            ['name' => 'Test Method A', 'code' => 'test_method_a'],
            ['name' => 'Test Method B', 'code' => 'test_method_b'],
        )->create();

        $this->actingAs($user)
            ->get(route('admin.payment-methods.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/payment-methods/payment-methods-index')
                ->has('paymentMethods.data', 10)
            );
    }

    public function test_authorized_user_can_create_payment_method(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.payment-methods.store'), [
                'name' => 'Venmo',
                'code' => 'venmo',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payment_methods', [
            'name' => 'Venmo',
            'code' => 'venmo',
        ]);
    }

    public function test_referenced_payment_method_is_deactivated_not_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $method = PaymentMethod::factory()->create([
            'name' => 'Referenced Method',
            'code' => 'referenced_method',
            'is_active' => true,
        ]);
        Transaction::factory()->generalExpense()->create([
            'payment_method_id' => $method->id,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.payment-methods.destroy', $method))
            ->assertRedirect();

        $method->refresh();
        $this->assertFalse($method->is_active);
        $this->assertNull($method->deleted_at);
    }

    public function test_unreferenced_payment_method_is_soft_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $method = PaymentMethod::factory()->create([
            'name' => 'Disposable Method',
            'code' => 'disposable_method',
        ]);

        $this->actingAs($user)
            ->delete(route('admin.payment-methods.destroy', $method))
            ->assertRedirect();

        $this->assertSoftDeleted('payment_methods', ['id' => $method->id]);
    }
}
