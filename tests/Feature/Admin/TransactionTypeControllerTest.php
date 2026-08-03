<?php

namespace Tests\Feature\Admin;

use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionTypeControllerTest extends TestCase
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

    public function test_guests_cannot_access_transaction_types_index(): void
    {
        $this->get(route('admin.transaction-types.index'))
            ->assertNotFound();
    }

    public function test_authorized_user_can_view_transaction_types_index(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->get(route('admin.transaction-types.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/transaction-types/transaction-types-index')
                ->has('transactionTypes.data', 6)
            );
    }

    public function test_authorized_user_can_create_transaction_type(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.transaction-types.store'), [
                'name' => 'Grant Income',
                'code' => 'grant_income',
                'description' => 'Incoming grants',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transaction_types', [
            'name' => 'Grant Income',
            'code' => 'grant_income',
            'is_system' => false,
        ]);
    }

    public function test_system_transaction_type_code_cannot_be_changed(): void
    {
        $user = $this->createAuthorizedUser();
        $type = TransactionType::query()->where('code', TransactionType::CODE_DONATION)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('admin.transaction-types.update', $type), [
                'name' => 'Donations Updated',
                'code' => 'changed_code',
                'description' => $type->description,
                'is_active' => true,
            ])
            ->assertRedirect();

        $type->refresh();
        $this->assertSame(TransactionType::CODE_DONATION, $type->code);
        $this->assertSame('Donations Updated', $type->name);
    }

    public function test_system_transaction_type_cannot_be_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $type = TransactionType::query()->where('code', TransactionType::CODE_DONATION)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('admin.transaction-types.destroy', $type))
            ->assertRedirect();

        $this->assertDatabaseHas('transaction_types', [
            'id' => $type->id,
            'deleted_at' => null,
        ]);
    }

    public function test_referenced_custom_type_is_deactivated_not_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $type = TransactionType::factory()->create([
            'name' => 'Referenced Type',
            'code' => 'referenced_type',
            'is_active' => true,
        ]);
        Transaction::factory()->create([
            'transaction_type_id' => $type->id,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.transaction-types.destroy', $type))
            ->assertRedirect();

        $type->refresh();
        $this->assertFalse($type->is_active);
        $this->assertNull($type->deleted_at);
    }

    public function test_unreferenced_custom_type_is_soft_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $type = TransactionType::factory()->create([
            'name' => 'Disposable Type',
            'code' => 'disposable_type',
        ]);

        $this->actingAs($user)
            ->delete(route('admin.transaction-types.destroy', $type))
            ->assertRedirect();

        $this->assertSoftDeleted('transaction_types', ['id' => $type->id]);
    }
}
