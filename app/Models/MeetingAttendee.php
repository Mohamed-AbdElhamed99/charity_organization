<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\AttendeeRole;
use Database\Factories\MeetingAttendeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendee extends Model
{
    /** @use HasFactory<MeetingAttendeeFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attendance_status' => AttendanceStatus::class,
            'role' => AttendeeRole::class,
            'signature_present' => 'boolean',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}
