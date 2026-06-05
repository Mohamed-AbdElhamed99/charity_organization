<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\NewsServiceInterface;
use App\DTOs\CreateNewsDTO;
use App\DTOs\UpdateNewsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\News\BulkDestroyNewsRequest;
use App\Http\Requests\Admin\News\RestoreNewsRequest;
use App\Http\Requests\Admin\News\StoreNewsRequest;
use App\Http\Requests\Admin\News\UpdateNewsRequest;
use App\Http\Resources\Admin\News\NewsResource;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function __construct(
        private readonly NewsServiceInterface $newsService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['query', 'category', 'status', 'page', 'per_page']);
        $paginator = $this->newsService->getPaginatedNews($filters);

        $news = $paginator->toArray();
        $news['data'] = NewsResource::collection($paginator->items())->resolve();

        return Inertia::render('admin/news/news-index', [
            'news' => $news,
            'categories' => NewsCategory::query()
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get(['id', 'name_ar', 'name_en']),
            'search' => $filters,
        ]);
    }

    public function store(StoreNewsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $slug = $validated['slug'] ?? Str::slug($validated['title_en']);
        $baseSlug = $slug;
        $counter = 1;

        while (News::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $this->newsService->createNews(new CreateNewsDTO(
            titleAr: $validated['title_ar'],
            titleEn: $validated['title_en'],
            slug: $slug,
            categoryId: $validated['category_id'] ?? null,
            subtitleAr: $validated['subtitle_ar'] ?? null,
            subtitleEn: $validated['subtitle_en'] ?? null,
            excerptAr: $validated['excerpt_ar'] ?? null,
            excerptEn: $validated['excerpt_en'] ?? null,
            bodyAr: $validated['body_ar'] ?? null,
            bodyEn: $validated['body_en'] ?? null,
            videoUrl: $validated['video_url'] ?? null,
            publishedAt: $validated['published_at'] ?? null,
            isActive: (bool) $validated['is_active'],
            isPrivate: (bool) $validated['is_private'],
            metaTitleAr: $validated['meta_title_ar'] ?? null,
            metaTitleEn: $validated['meta_title_en'] ?? null,
            metaDescriptionAr: $validated['meta_description_ar'] ?? null,
            metaDescriptionEn: $validated['meta_description_en'] ?? null,
            thumbnail: $request->file('thumbnail'),
            mainMedia: $request->file('main_media'),
            gallery: $request->file('gallery') ?? [],
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News created successfully.')]);

        return back();
    }

    public function update(UpdateNewsRequest $request, News $news): RedirectResponse
    {
        $validated = $request->validated();

        $this->newsService->updateNews($news, new UpdateNewsDTO(
            categoryId: $validated['category_id'] ?? null,
            slug: $validated['slug'],
            titleAr: $validated['title_ar'],
            titleEn: $validated['title_en'],
            subtitleAr: $validated['subtitle_ar'] ?? null,
            subtitleEn: $validated['subtitle_en'] ?? null,
            excerptAr: $validated['excerpt_ar'] ?? null,
            excerptEn: $validated['excerpt_en'] ?? null,
            bodyAr: $validated['body_ar'] ?? null,
            bodyEn: $validated['body_en'] ?? null,
            videoUrl: $validated['video_url'] ?? null,
            publishedAt: $validated['published_at'] ?? null,
            isActive: (bool) $validated['is_active'],
            isPrivate: (bool) $validated['is_private'],
            metaTitleAr: $validated['meta_title_ar'] ?? null,
            metaTitleEn: $validated['meta_title_en'] ?? null,
            metaDescriptionAr: $validated['meta_description_ar'] ?? null,
            metaDescriptionEn: $validated['meta_description_en'] ?? null,
            thumbnail: $request->file('thumbnail'),
            mainMedia: $request->file('main_media'),
            gallery: $request->file('gallery'),
            removedGalleryIds: $validated['removed_gallery_ids'] ?? null,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News updated successfully.')]);

        return back();
    }

    public function destroy(News $news): RedirectResponse
    {
        $this->newsService->deleteNews($news);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News deleted successfully.')]);

        return back();
    }

    public function bulkDestroy(BulkDestroyNewsRequest $request): RedirectResponse
    {
        $this->newsService->bulkDelete($request->validated('ids'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News deleted successfully.')]);

        return back();
    }

    public function restore(RestoreNewsRequest $request, int|string $id): RedirectResponse
    {
        $this->newsService->restoreNews($id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('News restored successfully.')]);

        return back();
    }
}
