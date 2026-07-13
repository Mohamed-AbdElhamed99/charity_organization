<?php

namespace App\Http\Requests\Admin\Meeting;

use App\Enums\DecisionPriority;
use App\Enums\DecisionStatus;
use App\Enums\DecisionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeetingDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_meetings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'decision_type' => ['required', Rule::enum(DecisionType::class)],
            'status' => ['required', Rule::enum(DecisionStatus::class)],
            'priority' => ['required', Rule::enum(DecisionPriority::class)],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
