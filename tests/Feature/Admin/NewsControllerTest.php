<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsControllerTest extends TestCase
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
    private function validNewsPayload(NewsCategory $category, array $overrides = []): array
    {
        return array_merge([
            'title_ar' => 'عنوان الخبر',
            'title_en' => 'News Title',
            'slug' => 'news-title',
            'category_id' => $category->id,
            'subtitle_ar' => 'عنوان فرعي',
            'subtitle_en' => 'Subtitle',
            'excerpt_ar' => 'مقتطف',
            'excerpt_en' => 'Excerpt',
            'body_ar' => 'المحتوى',
            'body_en' => 'Body content',
            'video_url' => null,
            'published_at' => now()->format('Y-m-d'),
            'is_active' => true,
            'is_private' => false,
            'meta_title_ar' => null,
            'meta_title_en' => null,
            'meta_description_ar' => null,
            'meta_description_en' => null,
        ], $overrides);
    }

    public function test_guests_cannot_access_news_index(): void
    {
        $this->get(route('admin.news.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_news_index(): void
    {
        $user = $this->createAuthorizedUser();
        News::factory()->count(3)->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.news.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/news/news-index')
                ->has('news.data', 3)
                ->has('categories')
            );
    }

    public function test_news_index_honors_search_and_pagination(): void
    {
        $user = $this->createAuthorizedUser();
        News::factory()->create([
            'created_by' => $user->id,
            'title_en' => 'Special Alpha Article',
        ]);
        News::factory()->count(14)->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.news.index', [
                'query' => 'Alpha',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/news/news-index')
                ->where('news.total', 1)
                ->where('news.per_page', 10)
                ->where('search.query', 'Alpha')
            );
    }

    public function test_authorized_user_can_create_news_with_media(): void
    {
        Storage::fake('public');
        $user = $this->createAuthorizedUser();
        $category = NewsCategory::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('admin.news.store'), array_merge(
                $this->validNewsPayload($category),
                [
                    'thumbnail' => UploadedFile::fake()->image('thumbnail.jpg'),
                    'main_media' => UploadedFile::fake()->image('main.jpg'),
                    'gallery' => [
                        UploadedFile::fake()->image('gallery-1.jpg'),
                        UploadedFile::fake()->image('gallery-2.jpg'),
                    ],
                ]
            ));

        $response->assertRedirect();

        $news = News::query()->where('slug', 'news-title')->first();

        $this->assertNotNull($news);
        $this->assertSame('News Title', $news->title_en);
        $this->assertSame($user->id, $news->created_by);
        $this->assertTrue($news->hasMedia('thumbnail'));
        $this->assertTrue($news->hasMedia('main_media'));
        $this->assertCount(2, $news->getMedia('gallery'));
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->createAuthorizedUser();

        $this->actingAs($user)
            ->post(route('admin.news.store'), [])
            ->assertSessionHasErrors(['title_ar', 'title_en', 'is_active', 'is_private', 'body_ar']);
    }

    public function test_store_empty_paragraph_body_ar_fails_required(): void
    {
        $user = $this->createAuthorizedUser();
        $category = NewsCategory::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.news.store'), $this->validNewsPayload($category, ['body_ar' => '<p></p>']))
            ->assertSessionHasErrors(['body_ar']);
    }

    public function test_store_paragraph_with_br_body_ar_fails_required(): void
    {
        $user = $this->createAuthorizedUser();
        $category = NewsCategory::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.news.store'), $this->validNewsPayload($category, ['body_ar' => '<p><br></p>']))
            ->assertSessionHasErrors(['body_ar']);
    }

    public function test_store_html_body_ar_is_sanitized_before_storage(): void
    {
        $user = $this->createAuthorizedUser();
        $category = NewsCategory::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.news.store'), $this->validNewsPayload($category, [
                'body_ar' => '<p>نص</p><script>alert(1)</script>',
            ]))
            ->assertSessionDoesntHaveErrors();

        $news = News::query()->where('slug', 'news-title')->first();
        $this->assertStringNotContainsString('<script>', (string) $news->body_ar);
        $this->assertStringContainsString('نص', (string) $news->body_ar);
    }

    public function test_user_without_permission_cannot_create_news(): void
    {
        $user = User::factory()->create();
        $category = NewsCategory::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.news.store'), $this->validNewsPayload($category))
            ->assertForbidden();
    }

    public function test_authorized_user_can_update_news_and_replace_media(): void
    {
        Storage::fake('public');
        $user = $this->createAuthorizedUser();
        $category = NewsCategory::factory()->create();

        $news = News::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'title_en' => 'Original Title',
            'slug' => 'original-title',
        ]);

        $news->addMedia(UploadedFile::fake()->image('old-thumb.jpg'))
            ->toMediaCollection('thumbnail');

        $this->actingAs($user)
            ->patch(route('admin.news.update', $news), array_merge(
                $this->validNewsPayload($category, [
                    'title_en' => 'Updated Title',
                    'slug' => 'updated-title',
                ]),
                [
                    'thumbnail' => UploadedFile::fake()->image('new-thumb.jpg'),
                ]
            ))
            ->assertRedirect();

        $news->refresh();

        $this->assertSame('Updated Title', $news->title_en);
        $this->assertSame('updated-title', $news->slug);
        $this->assertCount(1, $news->getMedia('thumbnail'));
        $this->assertStringContainsString('new-thumb', $news->getFirstMedia('thumbnail')->file_name);
    }

    public function test_authorized_user_can_soft_delete_news(): void
    {
        $user = $this->createAuthorizedUser();
        $news = News::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->delete(route('admin.news.destroy', $news))
            ->assertRedirect();

        $this->assertSoftDeleted('news', ['id' => $news->id]);
    }

    public function test_authorized_user_can_restore_news(): void
    {
        $user = $this->createAuthorizedUser();
        $news = News::factory()->create(['created_by' => $user->id]);
        $news->delete();

        $this->actingAs($user)
            ->post(route('admin.news.restore', $news->id))
            ->assertRedirect();

        $this->assertNull($news->fresh()->deleted_at);
    }

    public function test_authorized_user_can_bulk_delete_news(): void
    {
        $user = $this->createAuthorizedUser();
        $newsItems = News::factory()->count(3)->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('admin.news.bulk-destroy'), [
                'ids' => $newsItems->pluck('id')->all(),
            ])
            ->assertRedirect();

        foreach ($newsItems as $news) {
            $this->assertSoftDeleted('news', ['id' => $news->id]);
        }
    }

    public function test_user_without_permission_cannot_update_news(): void
    {
        $user = User::factory()->create();
        $category = NewsCategory::factory()->create();
        $news = News::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->patch(route('admin.news.update', $news), $this->validNewsPayload($category))
            ->assertForbidden();
    }
}
