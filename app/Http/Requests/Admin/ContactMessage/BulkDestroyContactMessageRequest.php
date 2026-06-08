<?php

namespace App\Http\Requests\Admin\ContactMessage;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete_contact_submissions') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:contact_us,id'],
        ];
    }
}
