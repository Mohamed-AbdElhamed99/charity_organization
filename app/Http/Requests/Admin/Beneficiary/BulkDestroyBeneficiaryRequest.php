<?php

namespace App\Http\Requests\Admin\Beneficiary;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkDestroyBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete_beneficiaries') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', Rule::exists('beneficiaries', 'id')],
        ];
    }
}
