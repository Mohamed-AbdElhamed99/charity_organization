<?php

namespace App\Http\Requests\Admin\GeneralExpense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_expenses') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('general_expense_categories', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'is_recurring' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
