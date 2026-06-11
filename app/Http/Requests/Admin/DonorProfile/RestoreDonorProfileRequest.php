<?php

namespace App\Http\Requests\Admin\DonorProfile;

use Illuminate\Foundation\Http\FormRequest;

class RestoreDonorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_donor_profiles') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
