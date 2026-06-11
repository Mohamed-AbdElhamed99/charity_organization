<?php

namespace App\Http\Requests\Admin\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkDestroyPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_payment_methods') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
        ];
    }
}
