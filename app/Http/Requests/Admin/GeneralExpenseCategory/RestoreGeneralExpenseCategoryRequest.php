<?php

namespace App\Http\Requests\Admin\GeneralExpenseCategory;

use Illuminate\Foundation\Http\FormRequest;

class RestoreGeneralExpenseCategoryRequest extends FormRequest
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
        return [];
    }
}
