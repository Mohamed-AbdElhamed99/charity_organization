<?php

namespace Tests\Feature\Site;

use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createPublishedNews(array $overrides = []): News
    {
        return News::factory()->create(array_merge([
            'is_active' => true,
            'is_private' => false,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    // ─── Home ─────────────────────────────────────────────────────────────────

    public function test_home_page_includes_latest_four_news(): void
    {
        News::factory()->count(6)->create([
            'is_active' => true,
            'is_private' => false,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/home')
                ->has('latestNews', 4)
            );
    }

    public function test_home_page_excludes_private_news(): void
    {
        News::factory()->count(3)->create([
            'is_active' => true,
            'is_private' => false,
            'published_at' => now()->subDay(),
        ]);

        News::factory()->count(3)->create([
            'is_active' => true,
            'is_private' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/home')
                ->has('latestNews', 3)
            );
    }

    public function test_home_page_excludes_inactive_news(): void
    {
        News::factory()->count(2)->create([
            'is_active' => true,
            'is_private' => false,
            'published_at' => now()->subDay(),
        ]);
        News::factory()->create([
            'is_active' => false,
            'is_private' => false,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/home')
                ->has('latestNews', 2)
            );
    }

    // ─── News Index ───────────────────────────────────────────────────────────

    public function test_news_index_returns_paginated_news(): void
    {
        News::factory()->count(12)->create([
            'is_active' => true,
            'is_private' => false,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('news.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/news/news-index')
                ->has('news')
                ->has('news.data', 9)
                ->where('news.current_page', 1)
                ->where('news.per_page', 9)
                ->where('news.total', 12)
                ->has('categories')
            );
    }

    public function test_news_index_second_page(): void
    {
        News::factory()->count(12)->create([
            'is_active' => true,
            'is_private' => false,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('news.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/news/news-index')
                ->has('news.data', 3)
                ->where('news.current_page', 2)
            );
    }

    public function test_news_index_filters_by_search_query(): void
    {
        $this->createPublishedNews(['title_en' => 'Unique Article Title', 'title_ar' => 'Unique Article Title']);
        News::factory()->count(5)->create([
            'is_active' => true,
            'is_private' => false,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('news.index', ['query' => 'Unique Article']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('news.data', 1)
            );
    }

    public function test_news_index_filters_by_category(): void
    {
        $category = NewsCategory::factory()->create();
        $otherCategory = NewsCategory::factory()->create();
        $this->createPublishedNews(['category_id' => $category->id]);
        News::factory()->count(4)->create([
            'is_active' => true,
            'is_private' => false,
            'published_at' => now()->subDay(),
            'category_id' => $otherCategory->id,
        ]);

        $this->get(route('news.index', ['category' => $category->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('news.data', 1)
            );
    }

    public function test_news_index_excludes_private_and_inactive_news(): void
    {
        $this->createPublishedNews();
        News::factory()->create(['is_active' => false, 'is_private' => false, 'published_at' => now()]);
        News::factory()->create(['is_active' => true, 'is_private' => true, 'published_at' => now()]);

        $this->get(route('news.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('news.data', 1)
            );
    }

    // ─── News Show ────────────────────────────────────────────────────────────

    public function test_news_show_renders_published_public_article(): void
    {
        $news = $this->createPublishedNews(['title_en' => 'Test Article', 'body_en' => 'Body content']);

        $this->get(route('news.show', $news->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/news/news-show')
                ->has('article')
                ->where('article.slug', $news->slug)
            );
    }

    public function test_news_show_returns_404_for_private_news(): void
    {
        $news = News::factory()->create([
            'is_active' => true,
            'is_private' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('news.show', $news->slug))->assertNotFound();
    }

    public function test_news_show_returns_404_for_inactive_news(): void
    {
        $news = News::factory()->create([
            'is_active' => false,
            'is_private' => false,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('news.show', $news->slug))->assertNotFound();
    }

    public function test_news_show_returns_404_for_unpublished_news(): void
    {
        $news = News::factory()->create([
            'is_active' => true,
            'is_private' => false,
            'published_at' => null,
        ]);

        $this->get(route('news.show', $news->slug))->assertNotFound();
    }
}
