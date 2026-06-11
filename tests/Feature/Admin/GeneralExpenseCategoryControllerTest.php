<?php

namespace Tests\Feature\Admin;

use App\Models\GeneralExpense;
use App\Models\GeneralExpenseCategory;
use App\Models\User;
use Database\Seeders\FinancialFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GeneralExpenseCategoryControllerTest extends TestCase
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

    public function test_authorized_user_can_view_categories_index(): void
    {
        $user = $this->createAuthorizedUser();
        GeneralExpenseCategory::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.general-expense-categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/general-expense-categories/general-expense-categories-index')
                ->has('categories.data', 2)
            );
    }

    public function test_authorized_user_can_create_category(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.general-expense-categories.store'), [
                'name' => 'Software',
                'description' => 'SaaS subscriptions',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('general_expense_categories', [
            'name' => 'Software',
        ]);
    }

    public function test_referenced_category_is_deactivated_not_deleted(): void
    {
        $user = $this->createAuthorizedUser();
        $category = GeneralExpenseCategory::factory()->create(['is_active' => true]);
        GeneralExpense::factory()->create(['category_id' => $category->id]);

        $this->actingAs($user)
            ->delete(route('admin.general-expense-categories.destroy', $category))
            ->assertRedirect();

        $category->refresh();
        $this->assertFalse($category->is_active);
        $this->assertNull($category->deleted_at);
    }
}
