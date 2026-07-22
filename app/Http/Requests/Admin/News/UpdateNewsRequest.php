<?php

namespace App\Http\Requests\Admin\News;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'body_ar' => $this->normalizeHtmlField($this->input('body_ar')),
            'body_en' => $this->normalizeHtmlField($this->input('body_en')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $news = $this->route('news');

        return [
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('news', 'slug')->ignore($news),
            ],
            'category_id' => ['nullable', 'integer', Rule::exists('news_categories', 'id')],
            'subtitle_ar' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'excerpt_ar' => ['nullable', 'string'],
            'excerpt_en' => ['nullable', 'string'],
            'body_ar' => ['required', 'string'],
            'body_en' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
            'is_private' => ['required', 'boolean'],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_ar' => ['nullable', 'string'],
            'meta_description_en' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:5120'],
            'main_media' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm'],
            'removed_gallery_ids' => ['nullable', 'array'],
            'removed_gallery_ids.*' => ['integer', Rule::exists('media', 'id')],
        ];
    }

    private function normalizeHtmlField(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return trim(strip_tags($value)) !== '' ? $value : null;
    }
}
