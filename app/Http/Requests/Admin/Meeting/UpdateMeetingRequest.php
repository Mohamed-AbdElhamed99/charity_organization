<?php

namespace App\Http\Requests\Admin\Meeting;

use App\Enums\AttendanceStatus;
use App\Enums\AttendeeRole;
use App\Enums\MeetingLocationType;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_time' => $this->normalizeTime($this->input('start_time')),
            'end_time' => $this->normalizeTime($this->input('end_time')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(MeetingType::class)],
            'status' => ['required', Rule::enum(MeetingStatus::class)],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'location_type' => ['required', Rule::enum(MeetingLocationType::class)],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'agenda' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'quorum_required' => ['nullable', 'integer', 'min:1'],
            'quorum_met' => ['sometimes', 'boolean'],
            'chairperson' => ['nullable', 'string', 'max:255'],
            'secretary' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'campaign_ids' => ['nullable', 'array'],
            'campaign_ids.*' => ['integer', 'exists:campaigns,id'],
            'attendees' => ['nullable', 'array'],
            'attendees.*.id' => ['nullable', 'integer', 'exists:meeting_attendees,id'],
            'attendees.*.name' => ['required', 'string', 'max:255'],
            'attendees.*.name_en' => ['nullable', 'string', 'max:255'],
            'attendees.*.title' => ['nullable', 'string', 'max:255'],
            'attendees.*.organization' => ['nullable', 'string', 'max:255'],
            'attendees.*.email' => ['nullable', 'email', 'max:255'],
            'attendees.*.phone' => ['nullable', 'string', 'max:50'],
            'attendees.*.attendance_status' => ['required', Rule::enum(AttendanceStatus::class)],
            'attendees.*.role' => ['required', Rule::enum(AttendeeRole::class)],
            'attendees.*.signature_present' => ['sometimes', 'boolean'],
            'attendees.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function normalizeTime(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return substr($value, 0, 5);
        }

        return $value;
    }
}
