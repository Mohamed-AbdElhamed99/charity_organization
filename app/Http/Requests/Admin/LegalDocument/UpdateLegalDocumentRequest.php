<?php

namespace App\Http\Requests\Admin\LegalDocument;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLegalDocumentRequest extends FormRequest
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
        return [
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_ar' => ['required', 'string'],
            'body_en' => ['nullable', 'string'],
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
