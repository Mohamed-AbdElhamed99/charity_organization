<?php

namespace Tests\Feature\Admin;

use App\Models\NewsCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsCategoryControllerTest extends TestCase
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
            'name_ar' => 'فئة الأخبار',
            'name_en' => 'News Category',
            'is_active' => true,
        ], $overrides);
    }

    public function test_guests_cannot_access_news_categories_index(): void
    {
        $this->get(route('admin.news-categories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_news_categories_index(): void
    {
        $user = $this->createAuthorizedUser();
        NewsCategory::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('admin.news-categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/news-categories/news-categories-index')
                ->has('newsCategories.data', 3)
            );
    }

    public function test_news_categories_index_honors_search(): void
    {
        $user = $this->createAuthorizedUser();
        NewsCategory::factory()->create(['name_en' => 'Special Category']);
        NewsCategory::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('admin.news-categories.index', ['query' => 'Special']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('newsCategories.total', 1)
                ->where('search.query', 'Special')
            );
    }

    public function test_authorized_user_can_create_news_category(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.news-categories.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('news_categories', [
            'name_en' => 'News Category',
            'name_ar' => 'فئة الأخبار',
            'is_active' => true,
        ]);
    }

    public function test_user_without_permission_cannot_create_news_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.news-categories.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_authorized_user_can_update_news_category(): void
    {
        $user = $this->createAuthorizedUser();
        $category = NewsCategory::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.news-categories.update', $category), $this->validPayload([
                'name_en' => 'Updated Category',
            ]))
            ->assertRedirect();

        $this->assertSame('Updated Category', $category->fresh()->name_en);
    }

    public function test_authorized_user_can_soft_delete_news_category(): void
    {
        $user = $this->createAuthorizedUser();
        $category = NewsCategory::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.news-categories.destroy', $category))
            ->assertRedirect();

        $this->assertSoftDeleted('news_categories', ['id' => $category->id]);
    }

    public function test_authorized_user_can_restore_news_category(): void
    {
        $user = $this->createAuthorizedUser();
        $category = NewsCategory::factory()->create();
        $category->delete();

        $this->actingAs($user)
            ->post(route('admin.news-categories.restore', $category->id))
            ->assertRedirect();

        $this->assertNull($category->fresh()->deleted_at);
    }

    public function test_authorized_user_can_bulk_delete_news_categories(): void
    {
        $user = $this->createAuthorizedUser();
        $categories = NewsCategory::factory()->count(3)->create();

        $this->actingAs($user)
            ->post(route('admin.news-categories.bulk-destroy'), [
                'ids' => $categories->pluck('id')->all(),
            ])
            ->assertRedirect();

        foreach ($categories as $category) {
            $this->assertSoftDeleted('news_categories', ['id' => $category->id]);
        }
    }
}
