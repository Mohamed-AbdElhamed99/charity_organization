<?php

namespace App\Models;

use App\Enums\DecisionPriority;
use App\Enums\DecisionStatus;
use App\Enums\DecisionType;
use Database\Factories\MeetingDecisionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingDecision extends Model
{
    /** @use HasFactory<MeetingDecisionFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $appends = ['is_overdue'];

    protected static function booted(): void
    {
        static::creating(function (MeetingDecision $decision): void {
            if (blank($decision->decision_number)) {
                $decision->decision_number = static::generateDecisionNumber($decision->meeting_id);
            }
        });
    }

    public static function generateDecisionNumber(int $meetingId): string
    {
        $meeting = Meeting::query()->findOrFail($meetingId);
        $prefix = $meeting->meeting_number.'-D';

        $latest = static::withTrashed()
            ->where('meeting_id', $meetingId)
            ->where('decision_number', 'like', "{$prefix}%")
            ->orderByDesc('decision_number')
            ->value('decision_number');

        $sequence = 1;

        if ($latest !== null && preg_match('/-D(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return [
            'decision_type' => DecisionType::class,
            'status' => DecisionStatus::class,
            'priority' => DecisionPriority::class,
            'due_date' => 'date',
            'completion_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->due_date === null) {
                return false;
            }

            if (in_array($this->status, [DecisionStatus::Completed, DecisionStatus::Cancelled], true)) {
                return false;
            }

            return $this->due_date->isPast();
        });
    }
}
