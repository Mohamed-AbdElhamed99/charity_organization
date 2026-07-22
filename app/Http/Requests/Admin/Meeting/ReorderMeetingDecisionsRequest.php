<?php

namespace App\Http\Requests\Admin\Meeting;

use Illuminate\Foundation\Http\FormRequest;

class ReorderMeetingDecisionsRequest extends FormRequest
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
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'exists:meeting_decisions,id'],
        ];
    }
}
