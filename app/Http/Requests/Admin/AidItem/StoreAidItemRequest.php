<?php

namespace App\Http\Requests\Admin\AidItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreAidItemRequest extends FormRequest
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
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'array'],
            'unit.en' => ['nullable', 'string', 'max:100'],
            'unit.ar' => ['nullable', 'string', 'max:100'],
            'default_unit_cost' => ['nullable', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
