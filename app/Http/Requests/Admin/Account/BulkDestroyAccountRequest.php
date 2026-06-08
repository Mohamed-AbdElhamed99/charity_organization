<?php

namespace App\Http\Requests\Admin\Account;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete_accounts') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:accounts,id'],
        ];
    }
}
