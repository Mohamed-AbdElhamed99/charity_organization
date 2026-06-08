<?php

namespace App\Http\Requests\Admin\ContactMessage;

use Illuminate\Foundation\Http\FormRequest;

class MarkContactMessageReviewedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view_contact_submissions') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
