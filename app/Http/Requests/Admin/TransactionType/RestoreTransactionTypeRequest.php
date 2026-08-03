<?php

namespace App\Http\Requests\Admin\TransactionType;

use Illuminate\Foundation\Http\FormRequest;

class RestoreTransactionTypeRequest extends FormRequest
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
