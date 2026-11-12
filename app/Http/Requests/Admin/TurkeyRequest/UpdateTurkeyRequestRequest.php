<?php

namespace App\Http\Requests\Admin\TurkeyRequest;

use App\Enums\TurkeyRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTurkeyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
            'address' => trim((string) $this->input('address')),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $otherActiveRequests = fn ($query) => $query
            ->where('status', '!=', TurkeyRequestStatus::Cancelled->value)
            ->where('id', '!=', $this->route('turkeyRequest')->id);

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('turkey_requests', 'email')->where($otherActiveRequests)],
            'phone' => ['required', 'string', 'min:7', 'max:50', Rule::unique('turkey_requests', 'phone')->where($otherActiveRequests)],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('A request with this email already exists.'),
            'phone.unique' => __('A request with this phone number already exists.'),
        ];
    }
}
