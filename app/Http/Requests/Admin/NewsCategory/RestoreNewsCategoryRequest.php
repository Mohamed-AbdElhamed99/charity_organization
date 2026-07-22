<?php

namespace App\Http\Requests\Admin\NewsCategory;

use Illuminate\Foundation\Http\FormRequest;

class RestoreNewsCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
