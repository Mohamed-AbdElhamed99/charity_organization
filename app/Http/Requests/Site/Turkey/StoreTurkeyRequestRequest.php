<?php

namespace App\Http\Requests\Site\Turkey;

use App\Enums\TurkeyRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTurkeyRequestRequest extends FormRequest
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
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $activeRequest = fn ($query) => $query->where('status', '!=', TurkeyRequestStatus::Cancelled->value);

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('turkey_requests', 'email')->where($activeRequest)],
            'phone' => ['required', 'string', 'min:7', 'max:50', Rule::unique('turkey_requests', 'phone')->where($activeRequest)],
            '_hp' => ['nullable', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => __('Please enter your name.'),
            'email.required' => __('Please enter your email address.'),
            'email.email' => __('Please enter a valid email address.'),
            'email.unique' => __('A request with this email already exists.'),
            'phone.required' => __('Please enter your phone number.'),
            'phone.min' => __('Please enter a valid phone number.'),
            'phone.unique' => __('A request with this phone number already exists.'),
            '_hp.max' => __('Submission rejected.'),
        ];
    }
}
