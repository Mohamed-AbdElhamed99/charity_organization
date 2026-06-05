<?php

namespace App\Http\Requests\Admin\News;

use Illuminate\Foundation\Http\FormRequest;

class RestoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete_news') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
