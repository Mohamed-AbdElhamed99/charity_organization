<?php

namespace App\Services;

use App\Contracts\Services\NewsServiceInterface;
use App\DTOs\CreateNewsDTO;
use App\DTOs\UpdateNewsDTO;
use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class NewsService implements NewsServiceInterface
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function getPaginatedNews(array $filters): LengthAwarePaginator
    {
        $query = $filters['query'] ?? null;
        $category = $filters['category'] ?? null;
        $status = $filters['status'] ?? null;

        return News::query()
            ->with(['category', 'media'])
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('title_ar', 'like', "%{$query}%")
                        ->orWhere('title_en', 'like', "%{$query}%")
                        ->orWhere('slug', 'like', "%{$query}%");
                });
            })
            ->when($category, function ($builder) use ($category) {
                $categories = is_array($category) ? $category : [$category];
                $builder->whereIn('category_id', $categories);
            })
            ->when($status, function ($builder) use ($status) {
                $statuses = is_array($status) ? $status : [$status];

                $builder->where(function ($q) use ($statuses) {
                    foreach ($statuses as $statusValue) {
                        match ($statusValue) {
                            'active' => $q->orWhere(fn ($b) => $b->where('is_active', true)),
                            'inactive' => $q->orWhere(fn ($b) => $b->where('is_active', false)),
                            'published' => $q->orWhere(fn ($b) => $b
                                ->where('is_active', true)
                                ->whereNotNull('published_at')
                                ->where('published_at', '<=', now())),
                            'draft' => $q->orWhere(fn ($b) => $b
                                ->where(function ($inner) {
                                    $inner->whereNull('published_at')
                                        ->orWhere('is_active', false);
                                })),
                            default => null,
                        };
                    }
                });
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();
    }

    public function createNews(CreateNewsDTO $dto): News
    {
        $news = News::create([
            'category_id' => $dto->categoryId,
            'slug' => $dto->slug,
            'title_ar' => $dto->titleAr,
            'title_en' => $dto->titleEn,
            'subtitle_ar' => $dto->subtitleAr,
            'subtitle_en' => $dto->subtitleEn,
            'excerpt_ar' => $dto->excerptAr,
            'excerpt_en' => $dto->excerptEn,
            'body_ar' => $this->sanitizer->sanitize($dto->bodyAr),
            'body_en' => $this->sanitizer->sanitize($dto->bodyEn),
            'video_url' => $dto->videoUrl,
            'published_at' => $dto->publishedAt,
            'is_active' => $dto->isActive,
            'is_private' => $dto->isPrivate,
            'meta_title_ar' => $dto->metaTitleAr,
            'meta_title_en' => $dto->metaTitleEn,
            'meta_description_ar' => $dto->metaDescriptionAr,
            'meta_description_en' => $dto->metaDescriptionEn,
            'created_by' => Auth::id(),
        ]);

        $this->syncMedia($news, $dto->thumbnail, $dto->mainMedia, $dto->gallery);

        return $news->load(['category', 'media']);
    }

    public function updateNews(News $news, UpdateNewsDTO $dto): News
    {
        $news->fill([
            'category_id' => $dto->categoryId,
            'slug' => $dto->slug,
            'title_ar' => $dto->titleAr,
            'title_en' => $dto->titleEn,
            'subtitle_ar' => $dto->subtitleAr,
            'subtitle_en' => $dto->subtitleEn,
            'excerpt_ar' => $dto->excerptAr,
            'excerpt_en' => $dto->excerptEn,
            'body_ar' => $this->sanitizer->sanitize($dto->bodyAr),
            'body_en' => $this->sanitizer->sanitize($dto->bodyEn),
            'video_url' => $dto->videoUrl,
            'published_at' => $dto->publishedAt,
            'is_active' => $dto->isActive,
            'is_private' => $dto->isPrivate,
            'meta_title_ar' => $dto->metaTitleAr,
            'meta_title_en' => $dto->metaTitleEn,
            'meta_description_ar' => $dto->metaDescriptionAr,
            'meta_description_en' => $dto->metaDescriptionEn,
        ]);
        $news->save();

        if ($dto->thumbnail) {
            $news->clearMediaCollection('thumbnail');
            $news->addMedia($dto->thumbnail)->toMediaCollection('thumbnail');
        }

        if ($dto->mainMedia) {
            $news->clearMediaCollection('main_media');
            $news->addMedia($dto->mainMedia)->toMediaCollection('main_media');
        }

        if ($dto->removedGalleryIds !== null && $dto->removedGalleryIds !== []) {
            Media::query()
                ->where('model_type', News::class)
                ->where('model_id', $news->id)
                ->where('collection_name', 'gallery')
                ->whereIn('id', $dto->removedGalleryIds)
                ->each(fn (Media $media) => $media->delete());
        }

        if ($dto->gallery !== null && $dto->gallery !== []) {
            foreach ($dto->gallery as $file) {
                $news->addMedia($file)->toMediaCollection('gallery');
            }
        }

        return $news->load(['category', 'media']);
    }

    public function deleteNews(News $news): void
    {
        $news->delete();
    }

    public function restoreNews(int|string $id): News
    {
        $news = News::withTrashed()->findOrFail($id);
        $news->restore();

        return $news->load(['category', 'media']);
    }

    public function bulkDelete(array $ids): void
    {
        News::query()
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * @param  array<int, UploadedFile>  $gallery
     */
    private function syncMedia(News $news, ?UploadedFile $thumbnail, ?UploadedFile $mainMedia, array $gallery): void
    {
        if ($thumbnail) {
            $news->addMedia($thumbnail)->toMediaCollection('thumbnail');
        }

        if ($mainMedia) {
            $news->addMedia($mainMedia)->toMediaCollection('main_media');
        }

        foreach ($gallery as $file) {
            $news->addMedia($file)->toMediaCollection('gallery');
        }
    }
}
