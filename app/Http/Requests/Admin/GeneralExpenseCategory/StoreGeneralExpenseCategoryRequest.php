<?php

namespace App\Http\Requests\Admin\GeneralExpenseCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreGeneralExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_general_expense_categories') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
