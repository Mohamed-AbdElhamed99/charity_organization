<?php

namespace App\Http\Requests\Admin\Faq;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFaqRequest extends FormRequest
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
        return [
            'question_ar' => ['required', 'string', 'max:1000'],
            'question_en' => ['nullable', 'string', 'max:1000'],
            'answer_ar' => ['required', 'string'],
            'answer_en' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_published' => ['required', 'boolean'],
        ];
    }
}
