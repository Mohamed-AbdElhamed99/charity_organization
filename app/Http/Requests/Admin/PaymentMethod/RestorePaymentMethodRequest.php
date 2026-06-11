<?php

namespace App\Http\Requests\Admin\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;

class RestorePaymentMethodRequest extends FormRequest
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
        return [];
    }
}
