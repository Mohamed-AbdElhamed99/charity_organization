<?php

namespace Tests\Feature\Admin;

use App\Models\CampaignCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampaignCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name_ar' => 'فئة الحملة',
            'name_en' => 'Campaign Category',
            'description' => 'Medical campaigns',
            'is_active' => true,
        ], $overrides);
    }

    public function test_guests_cannot_access_campaign_categories_index(): void
    {
        $this->get(route('admin.campaign-categories.index'))
            ->assertNotFound();
    }

    public function test_authorized_user_can_view_campaign_categories_index(): void
    {
        $user = $this->createAuthorizedUser();
        CampaignCategory::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('admin.campaign-categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/campaign-categories/campaign-categories-index')
                ->has('campaignCategories.data', 3)
            );
    }

    public function test_authorized_user_can_create_campaign_category(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.campaign-categories.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('campaign_categories', [
            'name_en' => 'Campaign Category',
            'description' => 'Medical campaigns',
        ]);
    }

    public function test_user_without_permission_cannot_create_campaign_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.campaign-categories.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_authorized_user_can_update_campaign_category(): void
    {
        $user = $this->createAuthorizedUser();
        $category = CampaignCategory::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.campaign-categories.update', $category), $this->validPayload([
                'name_en' => 'Updated Category',
            ]))
            ->assertRedirect();

        $this->assertSame('Updated Category', $category->fresh()->name_en);
    }

    public function test_authorized_user_can_soft_delete_campaign_category(): void
    {
        $user = $this->createAuthorizedUser();
        $category = CampaignCategory::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.campaign-categories.destroy', $category))
            ->assertRedirect();

        $this->assertSoftDeleted('campaign_categories', ['id' => $category->id]);
    }
}
