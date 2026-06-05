<?php

namespace App\Http\Requests\Admin\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_users') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive', 'banned'])],
            'role' => ['sometimes', 'required', 'string', Rule::exists('roles', 'name')],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
