<?php

namespace Tests\Feature\Site;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createPublicActiveCampaign(array $overrides = []): Campaign
    {
        return Campaign::factory()->active()->create(array_merge([
            'is_public' => true,
            'start_date' => now()->addWeek(),
        ], $overrides));
    }

    // ─── Home ─────────────────────────────────────────────────────────────────

    public function test_home_page_includes_upcoming_active_public_campaigns(): void
    {
        Campaign::factory()->count(6)->active()->create([
            'is_public' => true,
            'start_date' => now()->addDays(7),
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/home')
                ->has('latestCampaigns', 4)
            );
    }

    public function test_home_page_excludes_private_campaigns(): void
    {
        Campaign::factory()->count(3)->active()->create([
            'is_public' => true,
            'start_date' => now()->addWeek(),
        ]);

        Campaign::factory()->count(3)->active()->create([
            'is_public' => false,
            'start_date' => now()->addWeek(),
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/home')
                ->has('latestCampaigns', 3)
            );
    }

    public function test_home_page_excludes_draft_campaigns(): void
    {
        Campaign::factory()->count(2)->active()->create([
            'is_public' => true,
            'start_date' => now()->addWeek(),
        ]);

        Campaign::factory()->draft()->create([
            'start_date' => now()->addWeek(),
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/home')
                ->has('latestCampaigns', 2)
            );
    }

    public function test_home_page_backfills_when_fewer_than_four_upcoming(): void
    {
        Campaign::factory()->active()->create([
            'is_public' => true,
            'start_date' => now()->addWeek(),
        ]);

        Campaign::factory()->active()->create([
            'is_public' => true,
            'start_date' => now()->subMonth(),
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/home')
                ->has('latestCampaigns', 2)
            );
    }

    // ─── Campaign Index ───────────────────────────────────────────────────────

    public function test_campaigns_index_returns_paginated_campaigns(): void
    {
        Campaign::factory()->count(12)->active()->create([
            'is_public' => true,
        ]);

        $this->get(route('campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/campaigns/campaigns-index')
                ->has('campaigns')
                ->has('campaigns.data', 9)
                ->where('campaigns.current_page', 1)
                ->where('campaigns.per_page', 9)
                ->where('campaigns.total', 12)
                ->has('categories')
            );
    }

    public function test_campaigns_index_second_page(): void
    {
        Campaign::factory()->count(12)->active()->create([
            'is_public' => true,
        ]);

        $this->get(route('campaigns.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/campaigns/campaigns-index')
                ->has('campaigns.data', 3)
                ->where('campaigns.current_page', 2)
            );
    }

    public function test_campaigns_index_filters_by_search_query(): void
    {
        $this->createPublicActiveCampaign(['title_en' => 'Unique Campaign Title', 'title_ar' => 'Unique Campaign Title']);
        Campaign::factory()->count(5)->active()->create(['is_public' => true]);

        $this->get(route('campaigns.index', ['query' => 'Unique Campaign']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 1)
            );
    }

    public function test_campaigns_index_filters_by_category(): void
    {
        $category = CampaignCategory::factory()->create();
        $otherCategory = CampaignCategory::factory()->create();
        $this->createPublicActiveCampaign(['category_id' => $category->id]);
        Campaign::factory()->count(4)->active()->create([
            'is_public' => true,
            'category_id' => $otherCategory->id,
        ]);

        $this->get(route('campaigns.index', ['category' => $category->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 1)
            );
    }

    public function test_campaigns_index_excludes_private_and_non_publishable(): void
    {
        $this->createPublicActiveCampaign();
        Campaign::factory()->draft()->create(['is_public' => true]);
        Campaign::factory()->active()->create(['is_public' => false]);

        $this->get(route('campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 1)
            );
    }

    public function test_campaigns_index_includes_completed_campaigns(): void
    {
        Campaign::factory()->completed()->create(['is_public' => true]);

        $this->get(route('campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 1)
            );
    }

    // ─── Campaign Show ────────────────────────────────────────────────────────

    public function test_campaign_show_renders_public_active_campaign(): void
    {
        $campaign = $this->createPublicActiveCampaign([
            'title_en' => 'Test Campaign',
            'description_en' => 'Body content',
        ]);

        $this->get(route('campaigns.show', $campaign->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('site/campaigns/campaigns-show')
                ->has('campaign')
                ->where('campaign.slug', $campaign->slug)
            );
    }

    public function test_campaign_show_renders_public_completed_campaign(): void
    {
        $campaign = Campaign::factory()->completed()->create([
            'is_public' => true,
            'title_en' => 'Completed Campaign',
        ]);

        $this->get(route('campaigns.show', $campaign->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('campaign.slug', $campaign->slug)
            );
    }

    public function test_campaign_show_returns_404_for_private_campaign(): void
    {
        $campaign = Campaign::factory()->active()->create([
            'is_public' => false,
        ]);

        $this->get(route('campaigns.show', $campaign->slug))->assertNotFound();
    }

    public function test_campaign_show_returns_404_for_draft_campaign(): void
    {
        $campaign = Campaign::factory()->draft()->create([
            'is_public' => true,
        ]);

        $this->get(route('campaigns.show', $campaign->slug))->assertNotFound();
    }

    public function test_campaign_show_returns_404_for_cancelled_campaign(): void
    {
        $campaign = Campaign::factory()->active()->create([
            'is_public' => true,
            'status' => CampaignStatus::Cancelled,
        ]);

        $this->get(route('campaigns.show', $campaign->slug))->assertNotFound();
    }

    // ─── Media Fallbacks ──────────────────────────────────────────────────────

    public function test_campaign_uses_cover_as_thumbnail_when_present(): void
    {
        Storage::fake('s3');

        $campaign = $this->createPublicActiveCampaign();
        $campaign->addMedia(UploadedFile::fake()->image('cover.jpg'))
            ->toMediaCollection('cover');
        $campaign->addMedia(UploadedFile::fake()->image('gallery.jpg'))
            ->toMediaCollection('gallery');

        $coverUrl = $campaign->getFirstMediaUrl('cover');

        $this->get(route('campaigns.show', $campaign->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('campaign.thumbnail', $coverUrl)
            );
    }

    public function test_campaign_uses_first_gallery_image_as_thumbnail_when_cover_missing(): void
    {
        Storage::fake('s3');

        $campaign = $this->createPublicActiveCampaign();
        $campaign->addMedia(UploadedFile::fake()->image('gallery-1.jpg'))
            ->toMediaCollection('gallery');
        $campaign->addMedia(UploadedFile::fake()->image('gallery-2.jpg'))
            ->toMediaCollection('gallery');

        $firstGalleryUrl = $campaign->getMedia('gallery')->first()->getUrl();

        $this->get(route('campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 1)
                ->where('campaigns.data.0.slug', $campaign->slug)
                ->where('campaigns.data.0.thumbnail', $firstGalleryUrl)
            );
    }

    public function test_campaign_thumbnail_is_empty_without_cover_or_gallery_images(): void
    {
        Storage::fake('s3');

        $this->createPublicActiveCampaign();

        $this->get(route('campaigns.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.data', 1)
                ->where('campaigns.data.0.thumbnail', '')
            );
    }

    public function test_campaign_show_uses_first_gallery_as_main_media_when_cover_missing(): void
    {
        Storage::fake('s3');

        $campaign = $this->createPublicActiveCampaign();
        $campaign->addMedia(UploadedFile::fake()->image('gallery.jpg'))
            ->toMediaCollection('gallery');

        $firstGallery = $campaign->getMedia('gallery')->first();

        $this->get(route('campaigns.show', $campaign->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('campaign.main_media', $firstGallery->getUrl())
                ->where('campaign.main_media_type', $firstGallery->mime_type)
            );
    }
}
