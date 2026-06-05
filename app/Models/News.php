<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class News extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $guarded = ['id'];

    // ─── Media Collections ───────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('main_media')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm']);
    }

    // ─── Casts ───────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'is_active' => 'boolean',
            'is_private' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->title_ar ?? $this->title_en)
                : ($this->title_en ?? $this->title_ar),
        );
    }

    protected function excerpt(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->excerpt_ar ?? $this->excerpt_en)
                : ($this->excerpt_en ?? $this->excerpt_ar),
        );
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->body_ar ?? $this->body_en)
                : ($this->body_en ?? $this->body_ar),
        );
    }

    protected $appends = ['title', 'excerpt'];
}
