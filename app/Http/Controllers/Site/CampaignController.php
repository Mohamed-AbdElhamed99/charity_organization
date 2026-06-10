<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\Site\CampaignResource;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'category', 'page']);

        $paginator = Campaign::query()
            ->public()
            ->publishable()
            ->with(['category', 'media'])
            ->when($filters['query'] ?? null, function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $q->where('title_ar', 'like', "%{$value}%")
                        ->orWhere('title_en', 'like', "%{$value}%");
                });
            })
            ->when($filters['category'] ?? null, function ($query, $value) {
                $query->where('category_id', $value);
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $campaigns = $paginator->toArray();
        $campaigns['data'] = CampaignResource::collection($paginator->items())->resolve();

        return Inertia::render('site/campaigns/campaigns-index', [
            'campaigns' => $campaigns,
            'categories' => CampaignCategory::active()->orderBy('name_en')->get(['id', 'name_ar', 'name_en']),
            'search' => $filters,
        ]);
    }

    public function show(Campaign $campaign): Response
    {
        abort_if(
            ! $campaign->is_public || ! $campaign->status?->isPublishable(),
            404
        );

        $campaign->load(['category', 'media']);

        $campaignData = (new CampaignResource($campaign))->resolve();

        // Prefer the cover; only use main_media as image if it's actually an image (not a video)
        $image = $campaignData['thumbnail']
            ?: (str_starts_with((string) $campaignData['main_media_type'], 'image/') ? $campaignData['main_media'] : '');

        return Inertia::render('site/campaigns/campaigns-show', [
            'campaign' => $campaignData,
        ])->withViewData([
            'meta' => [
                'type'        => 'article',
                'title'       => $campaignData['meta_title'],
                'description' => Str::limit(strip_tags((string) $campaignData['meta_description']), 160),
                'image'       => $image ?: url('/images/new-egypt-logo.png'),
                'url'         => route('campaigns.show', $campaign),
                'published'   => $campaignData['published_at'],
            ],
        ]);
    }
}