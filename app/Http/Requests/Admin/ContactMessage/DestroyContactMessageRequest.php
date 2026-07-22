<?php

namespace App\Http\Requests\Admin\ContactMessage;

use Illuminate\Foundation\Http\FormRequest;

class DestroyContactMessageRequest extends FormRequest
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
