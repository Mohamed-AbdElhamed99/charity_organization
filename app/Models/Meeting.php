<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingLocationType;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Meeting $meeting): void {
            if (blank($meeting->meeting_number)) {
                $meeting->meeting_number = static::generateMeetingNumber();
            }
        });
    }

    public static function generateMeetingNumber(): string
    {
        $year = now()->year;
        $prefix = "MTG-{$year}-";

        $latest = static::withTrashed()
            ->where('meeting_number', 'like', "{$prefix}%")
            ->orderByDesc('meeting_number')
            ->value('meeting_number');

        $sequence = 1;

        if ($latest !== null && preg_match('/MTG-\d{4}-(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return [
            'type' => MeetingType::class,
            'status' => MeetingStatus::class,
            'location_type' => MeetingLocationType::class,
            'meeting_date' => 'date',
            'quorum_required' => 'integer',
            'quorum_met' => 'boolean',
        ];
    }

    public function minutes(): HasOne
    {
        return $this->hasOne(MeetingMinutes::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(MeetingDecision::class)->orderBy('sort_order');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MeetingAttachment::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)
            ->withPivot(['relationship_type', 'notes'])
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function formattedDate(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->meeting_date === null) {
                return null;
            }

            return $this->meeting_date->format('F j, Y');
        });
    }

    protected function duration(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (blank($this->start_time) || blank($this->end_time)) {
                return null;
            }

            $start = Carbon::parse($this->start_time);
            $end = Carbon::parse($this->end_time);

            if ($end->lessThanOrEqualTo($start)) {
                return null;
            }

            $minutes = (int) $start->diffInMinutes($end);
            $hours = intdiv($minutes, 60);
            $remainingMinutes = $minutes % 60;

            if ($hours > 0 && $remainingMinutes > 0) {
                return "{$hours}h {$remainingMinutes}m";
            }

            if ($hours > 0) {
                return "{$hours}h";
            }

            return "{$remainingMinutes}m";
        });
    }

    protected function attendedCount(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->relationLoaded('attendees')) {
                return $this->attendees
                    ->where('attendance_status', AttendanceStatus::Attended)
                    ->count();
            }

            return $this->attendees()
                ->where('attendance_status', AttendanceStatus::Attended)
                ->count();
        });
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', MeetingStatus::Completed);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('meeting_date', '>=', now()->toDateString())
            ->whereIn('status', [MeetingStatus::Scheduled, MeetingStatus::Postponed, MeetingStatus::InProgress]);
    }

    public function scopeByType(Builder $query, string|MeetingType $type): Builder
    {
        return $query->where('type', $type instanceof MeetingType ? $type->value : $type);
    }

    public function scopeByStatus(Builder $query, string|MeetingStatus $status): Builder
    {
        return $query->where('status', $status instanceof MeetingStatus ? $status->value : $status);
    }
}
