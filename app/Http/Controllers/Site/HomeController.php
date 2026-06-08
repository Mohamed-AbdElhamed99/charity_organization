<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\Site\CampaignResource;
use App\Http\Resources\Site\NewsResource;
use App\Models\Campaign;
use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $latestNews = News::query()
            ->published()
            ->public()
            ->with(['category', 'media'])
            ->latest('published_at')
            ->limit(4)
            ->get();

        $upcoming = Campaign::query()
            ->public()
            ->active()
            ->whereDate('start_date', '>=', now()->toDateString())
            ->with(['category', 'media'])
            ->orderBy('start_date')
            ->limit(4)
            ->get();

        if ($upcoming->count() < 4) {
            $upcoming = $upcoming->merge(
                Campaign::query()
                    ->public()
                    ->active()
                    ->whereNotIn('id', $upcoming->pluck('id'))
                    ->with(['category', 'media'])
                    ->orderBy('start_date')
                    ->limit(4 - $upcoming->count())
                    ->get()
            );
        }

        return Inertia::render('site/home', [
            'latestNews' => NewsResource::collection($latestNews)->resolve(),
            'latestCampaigns' => CampaignResource::collection($upcoming)->resolve(),
        ]);
    }
}
