<?php

namespace App\Http\Requests\Admin\GeneralExpenseCategory;

use Illuminate\Foundation\Http\FormRequest;

class RestoreGeneralExpenseCategoryRequest extends FormRequest
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
