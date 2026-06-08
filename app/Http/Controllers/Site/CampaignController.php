<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\Site\CampaignResource;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
            ->latest('start_date')
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

        return Inertia::render('site/campaigns/campaigns-show', [
            'campaign' => (new CampaignResource($campaign))->resolve(),
        ]);
    }
}
