<?php

namespace App\Http\Requests\Admin\DonorProfile;

use App\Enums\DonorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDonorProfileRequest extends FormRequest
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
        return [
            'type' => ['required', 'string', Rule::enum(DonorType::class)],
            'organization_name' => [
                Rule::requiredIf($this->input('type') === DonorType::Organization->value),
                'nullable',
                'string',
                'max:255',
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
