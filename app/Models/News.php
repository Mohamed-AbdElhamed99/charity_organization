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
                ? ($this->normalizeBody($this->body_ar) ?? $this->normalizeBody($this->body_en))
                : ($this->normalizeBody($this->body_en) ?? $this->normalizeBody($this->body_ar)),
        );
    }

    protected function subtitle(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->subtitle_ar ?? $this->subtitle_en)
                : ($this->subtitle_en ?? $this->subtitle_ar),
        );
    }

    protected function categoryName(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->category?->name_ar ?? $this->category?->name_en)
                : ($this->category?->name_en ?? $this->category?->name_ar),
        );
    }

    protected function metaTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->meta_title_ar ?? $this->meta_title_en)
                : ($this->meta_title_en ?? $this->meta_title_ar),
        );
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => app()->getLocale() === 'ar'
                ? ($this->meta_description_ar ?? $this->meta_description_en)
                : ($this->meta_description_en ?? $this->meta_description_ar),
        );
    }

    protected $appends = ['title', 'subtitle', 'excerpt', 'body', 'category_name'];

    /**
     * Convert a legacy JSON-array body into plain text.
     * Leaves already-plain-text values untouched.
     */
    private function normalizeBody(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $decoded = json_decode($value, true);

        // Not valid JSON → already plain text, return as-is.
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        // JSON array of paragraphs → join with blank lines.
        if (is_array($decoded)) {
            return implode("\n\n", array_map('trim', $decoded));
        }

        // JSON-encoded plain string → return the decoded string.
        if (is_string($decoded)) {
            return $decoded;
        }

        // Anything else (number, bool, null) → fall back to original.
        return $value;
    }
}
