<?php

namespace App\Http\Requests\Site\Turkey;

use App\Models\TurkeyRequest;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'address' => trim((string) $this->input('address')),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:50', $this->withinPersonRequestLimit()],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            '_hp' => ['nullable', 'max:0'],
        ];
    }

    /**
     * The same person (same phone or email) may register several times, up to a configured limit.
     */
    private function withinPersonRequestLimit(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $limit = (int) config('turkey_campaign.max_requests_per_person');

            $existing = TurkeyRequest::query()
                ->holdingStock()
                ->where(fn ($query) => $query
                    ->where('phone', $this->input('phone'))
                    ->orWhere('email', $this->input('email')))
                ->count();

            if ($existing >= $limit) {
                $fail(__('This phone number or email has reached the maximum number of requests.'));
            }
        };
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
            'phone.required' => __('Please enter your phone number.'),
            'phone.min' => __('Please enter a valid phone number.'),
            'address.required' => __('Please enter your address.'),
            'address.min' => __('Please enter a valid address.'),
            '_hp.max' => __('Submission rejected.'),
        ];
    }
}
