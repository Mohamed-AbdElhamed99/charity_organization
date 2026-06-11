<?php

namespace App\Http\Requests\Admin\GeneralExpense;

use Illuminate\Foundation\Http\FormRequest;

class DestroyGeneralExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete_expenses') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
