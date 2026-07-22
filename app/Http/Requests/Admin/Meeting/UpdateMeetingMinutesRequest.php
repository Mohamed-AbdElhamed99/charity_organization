<?php

namespace App\Http\Requests\Admin\Meeting;

use App\Enums\MinutesFormat;
use App\Enums\MinutesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeetingMinutesRequest extends FormRequest
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
            'content' => ['required', 'string'],
            'summary' => ['nullable', 'string'],
            'format' => ['required', Rule::enum(MinutesFormat::class)],
            'language' => ['required', Rule::enum(MinutesLanguage::class)],
            'is_approved' => ['sometimes', 'boolean'],
        ];
    }
}
