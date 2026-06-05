<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\Site\NewsResource;
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

        return Inertia::render('site/home', [
            'latestNews' => NewsResource::collection($latestNews)->resolve(),
        ]);
    }
}
