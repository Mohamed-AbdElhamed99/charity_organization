<?php

namespace App\Http\Requests\Admin\Faq;

use Illuminate\Foundation\Http\FormRequest;

class RestoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete_faqs') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
