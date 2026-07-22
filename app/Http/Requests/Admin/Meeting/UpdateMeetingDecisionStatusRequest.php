<?php

namespace App\Http\Requests\Admin\Meeting;

use App\Enums\DecisionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeetingDecisionStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(DecisionStatus::class)],
            'completion_date' => ['nullable', 'date'],
            'completion_notes' => ['nullable', 'string'],
        ];
    }
}
