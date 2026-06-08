<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\Site\NewsResource;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'category', 'page']);

        $paginator = News::query()
            ->published()
            ->public()
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
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $news = $paginator->toArray();
        $news['data'] = NewsResource::collection($paginator->items())->resolve();

        return Inertia::render('site/news/news-index', [
            'news' => $news,
            'categories' => NewsCategory::active()->orderBy('name_en')->get(['id', 'name_ar', 'name_en']),
            'search' => $filters,
        ]);
    }

    public function show(News $news): Response
    {
        abort_if(
            $news->is_private || ! $news->is_active || $news->published_at === null,
            404
        );

        $news->load(['category', 'media']);

        $article = (new NewsResource($news))->resolve();

        // Prefer the thumbnail; only use main_media as image if it's actually an image (not a video)
        $image = $article['thumbnail']
            ?: (str_starts_with((string) $article['main_media_type'], 'image/') ? $article['main_media'] : '');

        return Inertia::render('site/news/news-show', [
            'article' => $article,
        ])->withViewData([
            'meta' => [
                'type'        => 'article',
                'title'       => $article['meta_title'],
                'description' => Str::limit(strip_tags((string) $article['meta_description']), 160),
                'image'       => $image ?: url('/images/new-egypt-logo.png'),
                'url'         => route('news.show', $news),
                'published'   => $article['published_at'],
            ],
        ]);
    }
}