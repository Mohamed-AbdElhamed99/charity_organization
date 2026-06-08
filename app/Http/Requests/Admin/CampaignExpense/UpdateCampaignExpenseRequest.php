<?php

namespace App\Http\Requests\Admin\CampaignExpense;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create_expenses') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'residual_quantity' => ['required', 'numeric', 'min:0'],
            'residual_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
