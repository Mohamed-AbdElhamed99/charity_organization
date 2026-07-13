<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\AttendeeRole;
use App\Enums\DecisionPriority;
use App\Enums\DecisionStatus;
use App\Enums\DecisionType;
use App\Enums\MeetingLocationType;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Enums\MinutesFormat;
use App\Enums\MinutesLanguage;
use App\Models\Campaign;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\MeetingDecision;
use App\Models\MeetingMinutes;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports legacy meeting SQL dumps into the current meetings schema.
 *
 *   Source                          -> Target
 *   --------------------------------------------------------------
 *   meetings                        -> meetings (EN title/agenda/description)
 *   meeting_attenders + users       -> meeting_attendees
 *   meeting_actions                 -> meeting_decisions
 *   meeting_activities + activities -> campaign_meeting (via activity slug)
 *   minutes                         -> meeting_minutes
 *
 * English fields are preferred; Arabic is ignored except as a title fallback.
 * Legacy PKs are not preserved — related rows are remapped via an in-memory ID map.
 */
#[Signature('migrate:legacy-meetings
    {--user-id= : Staff user id written to created_by (defaults to first user)}
    {--meetings= : Path to meetings.sql (default: database/data/meetings.sql)}
    {--minutes= : Path to minutes.sql (default: database/data/minutes.sql)}
    {--activities= : Path to activities.sql (default: database/data/activities.sql)}
    {--include-deleted : Also import soft-deleted legacy rows}
    {--dry-run : Run inside a transaction and roll back at the end}')]
#[Description('Migrate legacy meetings SQL dumps into the current meetings schema')]
class MigrateLegacyMeetingsCommand extends Command
{
    /** @var array<int, int> legacy meeting id → new meeting id */
    private array $meetingIdMap = [];

    /** @var array<int, MeetingStatus> legacy meeting id → mapped status */
    private array $meetingStatusMap = [];

    /** @var array<int, array{facilitator: ?int, note_taker: ?int}> */
    private array $meetingRoleUserIds = [];

    /** @var array<int, array<string, mixed>> */
    private array $legacyUsers = [];

    /** @var array<int, array<string, mixed>> */
    private array $legacyAgendas = [];

    /** @var array<int, list<array<string, mixed>>> agendas grouped by meeting_id */
    private array $agendasByMeeting = [];

    /** @var array<int, string> activity_id → slug */
    private array $activitySlugById = [];

    /** @var array<string, int> campaign slug → id */
    private array $campaignIdBySlug = [];

    /** @var array<int, int> sort order counters per new meeting id */
    private array $decisionSortOrder = [];

    /** @var array<string, int> */
    private array $stats = [];

    /** @var list<string> */
    private array $failures = [];

    public function handle(): int
    {
        $userId = $this->resolveCreatorId();
        if ($userId === null) {
            return self::FAILURE;
        }

        $meetingsPath = $this->resolvePath(
            $this->option('meetings'),
            database_path('data/meetings.sql')
        );
        $minutesPath = $this->resolvePath(
            $this->option('minutes'),
            database_path('data/minutes.sql')
        );
        $activitiesPath = $this->resolvePath(
            $this->option('activities'),
            database_path('data/activities.sql')
        );

        foreach ([
            'Meetings' => $meetingsPath,
            'Minutes' => $minutesPath,
            'Activities' => $activitiesPath,
        ] as $label => $path) {
            if (! is_readable($path)) {
                $this->error("{$label} SQL file not readable: {$path}");

                return self::FAILURE;
            }
        }

        $this->stats = [
            'meetings_created' => 0,
            'attendees_created' => 0,
            'decisions_created' => 0,
            'campaign_links_created' => 0,
            'minutes_created' => 0,
            'skipped_deleted' => 0,
            'skipped_invalid' => 0,
            'skipped_unresolved_campaign' => 0,
            'skipped_missing_meeting' => 0,
            'skipped_failed' => 0,
        ];
        $this->failures = [];
        $this->meetingIdMap = [];
        $this->meetingStatusMap = [];
        $this->meetingRoleUserIds = [];
        $this->decisionSortOrder = [];

        $dryRun = (bool) $this->option('dry-run');
        $this->info('Migrating legacy meetings'
            .($dryRun ? ' (DRY RUN — will roll back)' : '')
            ." as user #{$userId}...");

        if ($dryRun) {
            DB::beginTransaction();
        }

        try {
            $this->bootLookups($meetingsPath, $activitiesPath);

            $this->migrateMeetings($meetingsPath, $userId);
            $this->migrateAttendees($meetingsPath);
            $this->migrateDecisions($meetingsPath, $userId);
            $this->migrateCampaignLinks($meetingsPath);
            $this->migrateMinutes($minutesPath, $userId);

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry run complete — all changes rolled back.');
            } else {
                $this->info('Migration finished.');
            }
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error('Migration aborted: '.$e->getMessage());
            $this->line($e->getFile().':'.$e->getLine());

            return self::FAILURE;
        }

        $this->printSummary();

        return self::SUCCESS;
    }

    private function resolveCreatorId(): ?int
    {
        $option = $this->option('user-id');

        if ($option !== null && $option !== '') {
            $user = User::query()->find((int) $option);
            if (! $user) {
                $this->error("User #{$option} does not exist.");

                return null;
            }

            return (int) $user->id;
        }

        $userId = User::query()->orderBy('id')->value('id');
        if ($userId === null) {
            $this->error('No users found. Pass --user-id= or seed a user first.');

            return null;
        }

        return (int) $userId;
    }

    private function resolvePath(mixed $option, string $default): string
    {
        $path = is_string($option) && $option !== '' ? $option : $default;

        if (! Str::startsWith($path, ['/', '\\']) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        return $path;
    }

    private function bootLookups(string $meetingsPath, string $activitiesPath): void
    {
        $includeDeleted = (bool) $this->option('include-deleted');

        $this->legacyUsers = [];
        foreach ($this->parseSqlInsertFile($meetingsPath, 'users') as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $this->legacyUsers[$id] = $row;
            }
        }
        $this->line('Legacy users loaded: '.count($this->legacyUsers));

        $this->legacyAgendas = [];
        $this->agendasByMeeting = [];
        foreach ($this->parseSqlInsertFile($meetingsPath, 'meeting_agendas') as $row) {
            if (! empty($row['deleted_at']) && ! $includeDeleted) {
                continue;
            }

            $id = (int) ($row['id'] ?? 0);
            $meetingId = (int) ($row['meeting_id'] ?? 0);
            if ($id <= 0 || $meetingId <= 0) {
                continue;
            }

            $this->legacyAgendas[$id] = $row;
            $this->agendasByMeeting[$meetingId][] = $row;
        }
        $this->line('Legacy agendas loaded: '.count($this->legacyAgendas));

        $this->activitySlugById = [];
        foreach ($this->parseSqlInsertFile($activitiesPath, 'activities') as $row) {
            $id = (int) ($row['id'] ?? 0);
            $slug = $this->nullableString($row['slug'] ?? null);
            if ($id > 0 && $slug !== null) {
                $this->activitySlugById[$id] = $slug;
            }
        }
        $this->line('Legacy activity slugs loaded: '.count($this->activitySlugById));

        $this->campaignIdBySlug = Campaign::withTrashed()
            ->whereNotNull('slug')
            ->pluck('id', 'slug')
            ->all();
        $this->line('Current campaigns indexed by slug: '.count($this->campaignIdBySlug));
    }

    private function migrateMeetings(string $path, int $userId): void
    {
        $rows = $this->parseSqlInsertFile($path, 'meetings');
        $this->line('Meetings rows parsed: '.count($rows));

        $includeDeleted = (bool) $this->option('include-deleted');
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            if (! empty($row['deleted_at']) && ! $includeDeleted) {
                $this->stats['skipped_deleted']++;

                continue;
            }

            $legacyId = (int) ($row['id'] ?? 0);
            $titleEn = $this->nullableString($row['meeting_title_en'] ?? null);
            $title = $titleEn ?? $this->nullableString($row['meeting_title_ar'] ?? null);

            if ($legacyId <= 0 || $title === null) {
                $this->stats['skipped_invalid']++;

                continue;
            }

            $meetingDate = $this->nullableDate($row['date'] ?? null);
            if ($meetingDate === null) {
                $this->stats['skipped_invalid']++;

                continue;
            }

            $status = $this->mapMeetingStatus($row['status'] ?? null);
            $agendas = $this->agendasByMeeting[$legacyId] ?? [];

            try {
                $meeting = DB::transaction(function () use ($row, $userId, $title, $titleEn, $meetingDate, $status, $agendas, $includeDeleted): Meeting {
                    return Meeting::query()->create([
                        'title' => $title,
                        'title_en' => $titleEn,
                        'type' => $this->mapMeetingType($row['meeting_type_id'] ?? null),
                        'status' => $status,
                        'meeting_date' => $meetingDate,
                        'start_time' => $this->normalizeTime($row['time'] ?? null) ?? '00:00:00',
                        'end_time' => null,
                        'location' => $this->nullableString($row['location_en'] ?? null),
                        'location_type' => $this->mapLocationType($row['meeting_types'] ?? null),
                        'agenda' => $this->buildAgendaText($agendas),
                        'description' => $this->buildDescriptionText($agendas),
                        'chairperson' => $this->resolveUserName($row['facilitator'] ?? null),
                        'secretary' => $this->resolveUserName($row['note_taker'] ?? null),
                        'notes' => 'Imported from legacy meetings #'.($row['id'] ?? '?'),
                        'created_by' => $userId,
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? now(),
                        'deleted_at' => $includeDeleted ? ($row['deleted_at'] ?? null) : null,
                    ]);
                });
            } catch (Throwable $e) {
                $this->recordFailure('meeting', $legacyId, $e);

                continue;
            }

            $this->meetingIdMap[$legacyId] = (int) $meeting->id;
            $this->meetingStatusMap[$legacyId] = $status;
            $this->meetingRoleUserIds[$legacyId] = [
                'facilitator' => $this->nullableInt($row['facilitator'] ?? null),
                'note_taker' => $this->nullableInt($row['note_taker'] ?? null),
            ];
            $this->stats['meetings_created']++;
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateAttendees(string $path): void
    {
        $rows = $this->parseSqlInsertFile($path, 'meeting_attenders');
        $this->line('Attenders rows parsed: '.count($rows));

        $includeDeleted = (bool) $this->option('include-deleted');
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            if (! empty($row['deleted_at']) && ! $includeDeleted) {
                $this->stats['skipped_deleted']++;

                continue;
            }

            $legacyMeetingId = (int) ($row['meeting_id'] ?? 0);
            $newMeetingId = $this->meetingIdMap[$legacyMeetingId] ?? null;
            if ($newMeetingId === null) {
                $this->stats['skipped_missing_meeting']++;

                continue;
            }

            $userId = (int) ($row['user_id'] ?? 0);
            $user = $this->legacyUsers[$userId] ?? null;
            $name = $this->resolveUserName($userId);
            if ($name === null) {
                $this->stats['skipped_invalid']++;

                continue;
            }

            $roles = $this->meetingRoleUserIds[$legacyMeetingId] ?? ['facilitator' => null, 'note_taker' => null];
            $role = AttendeeRole::Member;
            if ($roles['facilitator'] !== null && $roles['facilitator'] === $userId) {
                $role = AttendeeRole::Chair;
            } elseif ($roles['note_taker'] !== null && $roles['note_taker'] === $userId) {
                $role = AttendeeRole::Secretary;
            }

            try {
                DB::transaction(function () use ($row, $newMeetingId, $name, $user, $role): void {
                    MeetingAttendee::query()->create([
                        'meeting_id' => $newMeetingId,
                        'name' => $name,
                        'name_en' => $this->nullableString($user['name_en'] ?? null) ?? $name,
                        'email' => $this->nullableString($user['email'] ?? null),
                        'phone' => $this->nullableString($user['phone'] ?? null),
                        'attendance_status' => AttendanceStatus::Attended,
                        'role' => $role,
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? now(),
                    ]);
                });
            } catch (Throwable $e) {
                $this->recordFailure('attender', $row['id'] ?? null, $e);

                continue;
            }

            $this->stats['attendees_created']++;
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateDecisions(string $path, int $userId): void
    {
        $rows = $this->parseSqlInsertFile($path, 'meeting_actions');
        $this->line('Actions rows parsed: '.count($rows));

        $includeDeleted = (bool) $this->option('include-deleted');
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            if (! empty($row['deleted_at']) && ! $includeDeleted) {
                $this->stats['skipped_deleted']++;

                continue;
            }

            $agendaId = (int) ($row['meeting_agendas_id'] ?? 0);
            $agenda = $this->legacyAgendas[$agendaId] ?? null;
            if ($agenda === null) {
                $this->stats['skipped_invalid']++;

                continue;
            }

            $legacyMeetingId = (int) ($agenda['meeting_id'] ?? 0);
            $newMeetingId = $this->meetingIdMap[$legacyMeetingId] ?? null;
            if ($newMeetingId === null) {
                $this->stats['skipped_missing_meeting']++;

                continue;
            }

            $nameEn = $this->nullableString($row['name_en'] ?? null);
            $title = $nameEn !== null ? Str::limit($nameEn, 255, '') : null;
            $description = $nameEn ?? '—';

            if ($title === null) {
                $this->stats['skipped_invalid']++;

                continue;
            }

            $meetingStatus = $this->meetingStatusMap[$legacyMeetingId] ?? MeetingStatus::Scheduled;
            $decisionStatus = $meetingStatus === MeetingStatus::Completed
                ? DecisionStatus::Completed
                : DecisionStatus::Pending;

            $sortOrder = ($this->decisionSortOrder[$newMeetingId] ?? 0) + 1;
            $this->decisionSortOrder[$newMeetingId] = $sortOrder;

            try {
                DB::transaction(function () use ($row, $newMeetingId, $title, $description, $decisionStatus, $sortOrder, $userId): void {
                    MeetingDecision::query()->create([
                        'meeting_id' => $newMeetingId,
                        'title' => $title,
                        'description' => $description,
                        'decision_type' => DecisionType::ActionItem,
                        'status' => $decisionStatus,
                        'priority' => DecisionPriority::Medium,
                        'assigned_to' => $this->resolveUserName($row['person_responsible'] ?? null),
                        'due_date' => $this->nullableDate($row['date'] ?? null),
                        'sort_order' => $sortOrder,
                        'created_by' => $userId,
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? now(),
                    ]);
                });
            } catch (Throwable $e) {
                $this->recordFailure('action', $row['id'] ?? null, $e);

                continue;
            }

            $this->stats['decisions_created']++;
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateCampaignLinks(string $path): void
    {
        $rows = $this->parseSqlInsertFile($path, 'meeting_activities');
        $this->line('Meeting activities rows parsed: '.count($rows));

        $includeDeleted = (bool) $this->option('include-deleted');
        $seen = [];
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $row) {
            $bar->advance();

            if (! empty($row['deleted_at']) && ! $includeDeleted) {
                $this->stats['skipped_deleted']++;

                continue;
            }

            $legacyMeetingId = (int) ($row['meeting_id'] ?? 0);
            $newMeetingId = $this->meetingIdMap[$legacyMeetingId] ?? null;
            if ($newMeetingId === null) {
                $this->stats['skipped_missing_meeting']++;

                continue;
            }

            $activityId = (int) ($row['activity_id'] ?? 0);
            $slug = $this->activitySlugById[$activityId] ?? null;
            $campaignId = $slug !== null ? ($this->campaignIdBySlug[$slug] ?? null) : null;

            if ($campaignId === null) {
                $this->stats['skipped_unresolved_campaign']++;

                continue;
            }

            $pairKey = $newMeetingId.':'.$campaignId;
            if (isset($seen[$pairKey])) {
                continue;
            }
            $seen[$pairKey] = true;

            try {
                DB::transaction(function () use ($newMeetingId, $campaignId): void {
                    $meeting = Meeting::withTrashed()->findOrFail($newMeetingId);
                    $meeting->campaigns()->syncWithoutDetaching([$campaignId]);
                });
            } catch (Throwable $e) {
                $this->recordFailure('meeting_activity', $row['id'] ?? null, $e);

                continue;
            }

            $this->stats['campaign_links_created']++;
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateMinutes(string $path, int $userId): void
    {
        $rows = $this->parseSqlInsertFile($path, 'minutes');
        $this->line('Minutes rows parsed: '.count($rows));

        /** @var array<int, list<array<string, mixed>>> $byMeeting */
        $byMeeting = [];
        foreach ($rows as $row) {
            $legacyMeetingId = (int) ($row['meeting_id'] ?? 0);
            if ($legacyMeetingId <= 0) {
                continue;
            }
            $byMeeting[$legacyMeetingId][] = $row;
        }

        $bar = $this->output->createProgressBar(count($byMeeting));
        $bar->start();

        foreach ($byMeeting as $legacyMeetingId => $minuteRows) {
            $bar->advance();

            $newMeetingId = $this->meetingIdMap[$legacyMeetingId] ?? null;
            if ($newMeetingId === null) {
                $this->stats['skipped_missing_meeting']++;

                continue;
            }

            if (MeetingMinutes::withTrashed()->where('meeting_id', $newMeetingId)->exists()) {
                continue;
            }

            usort($minuteRows, function (array $a, array $b): int {
                $timeA = (string) ($a['time'] ?? '');
                $timeB = (string) ($b['time'] ?? '');
                $cmp = strcmp($timeA, $timeB);
                if ($cmp !== 0) {
                    return $cmp;
                }

                return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
            });

            $sections = [];
            foreach ($minuteRows as $minuteRow) {
                $subject = $this->nullableString($minuteRow['subject'] ?? null);
                if ($subject === null) {
                    continue;
                }

                $time = $this->normalizeTime($minuteRow['time'] ?? null);
                $prefix = $time !== null ? '['.substr($time, 0, 5).'] ' : '';
                $sections[] = $prefix.$subject;
            }

            if ($sections === []) {
                $this->stats['skipped_invalid']++;

                continue;
            }

            $content = implode("\n\n", $sections);

            try {
                DB::transaction(function () use ($newMeetingId, $content, $userId, $minuteRows): void {
                    $first = $minuteRows[0];
                    MeetingMinutes::query()->create([
                        'meeting_id' => $newMeetingId,
                        'content' => $content,
                        'summary' => null,
                        'format' => MinutesFormat::Standard,
                        'language' => MinutesLanguage::En,
                        'version' => 1,
                        'is_approved' => false,
                        'created_by' => $userId,
                        'created_at' => $first['created_at'] ?? now(),
                        'updated_at' => $first['updated_at'] ?? now(),
                    ]);
                });
            } catch (Throwable $e) {
                $this->recordFailure('minutes', $legacyMeetingId, $e);

                continue;
            }

            $this->stats['minutes_created']++;
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * @param  list<array<string, mixed>>  $agendas
     */
    private function buildAgendaText(array $agendas): ?string
    {
        $lines = [];
        $n = 1;
        foreach ($agendas as $agenda) {
            $name = $this->nullableString($agenda['name_en'] ?? null);
            if ($name === null) {
                continue;
            }
            $lines[] = $n.'. '.$name;
            $n++;
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $agendas
     */
    private function buildDescriptionText(array $agendas): ?string
    {
        $parts = [];
        foreach ($agendas as $agenda) {
            $discussion = $this->nullableString($agenda['discussion_en'] ?? null);
            if ($discussion !== null) {
                $parts[] = $discussion;
            }
        }

        return $parts === [] ? null : implode("\n\n", $parts);
    }

    private function mapMeetingType(mixed $value): MeetingType
    {
        return match ((int) $value) {
            1 => MeetingType::Board,
            3 => MeetingType::GeneralAssembly,
            4 => MeetingType::Other,
            default => MeetingType::Other,
        };
    }

    private function mapMeetingStatus(mixed $value): MeetingStatus
    {
        return ((int) $value) === 1
            ? MeetingStatus::Completed
            : MeetingStatus::Scheduled;
    }

    private function mapLocationType(mixed $value): MeetingLocationType
    {
        $normalized = Str::lower((string) ($this->nullableString($value) ?? ''));

        return $normalized === 'online'
            ? MeetingLocationType::Online
            : MeetingLocationType::Physical;
    }

    private function resolveUserName(mixed $userId): ?string
    {
        $id = $this->nullableInt($userId);
        if ($id === null) {
            return null;
        }

        $user = $this->legacyUsers[$id] ?? null;
        if ($user === null) {
            return null;
        }

        return $this->nullableString($user['name_en'] ?? null)
            ?? $this->nullableString($user['name_ar'] ?? null);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableDate(mixed $value): ?string
    {
        $date = $this->nullableString($value);
        if ($date === null || $date === '0000-00-00' || str_starts_with($date, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeTime(mixed $value): ?string
    {
        $raw = $this->nullableString($value);
        if ($raw === null) {
            return null;
        }

        // Handle messy values like "10:1", "10:0", "9:30", "4:03 AM"
        if (preg_match('/^(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?\s*(AM|PM)?$/i', $raw, $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            $second = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : 0;
            $meridiem = strtoupper($matches[4] ?? '');

            if ($meridiem === 'PM' && $hour < 12) {
                $hour += 12;
            } elseif ($meridiem === 'AM' && $hour === 12) {
                $hour = 0;
            }

            if ($hour > 23 || $minute > 59 || $second > 59) {
                return null;
            }

            return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
        }

        try {
            return Carbon::parse($raw)->format('H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseSqlInsertFile(string $path, string $table): array
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new \RuntimeException("Unable to read SQL file: {$path}");
        }

        if (! preg_match_all(
            '/INSERT\s+INTO\s+`'.preg_quote($table, '/').'`\s*\(([^)]+)\)\s*VALUES\s*(.*?);/is',
            $sql,
            $matches,
            PREG_SET_ORDER
        )) {
            throw new \RuntimeException("No INSERT INTO `{$table}` statements found in {$path}");
        }

        $rows = [];
        foreach ($matches as $match) {
            $columns = array_map(
                fn (string $column): string => trim($column, " `\t\n\r"),
                explode(',', $match[1])
            );

            foreach ($this->parseValueTuples($match[2]) as $values) {
                if (count($values) !== count($columns)) {
                    $this->stats['skipped_invalid'] = ($this->stats['skipped_invalid'] ?? 0) + 1;

                    continue;
                }

                $rows[] = array_combine($columns, $values);
            }
        }

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function parseValueTuples(string $valuesSql): array
    {
        $rows = [];
        $len = strlen($valuesSql);
        $i = 0;

        while ($i < $len) {
            while ($i < $len && $valuesSql[$i] !== '(') {
                $i++;
            }

            if ($i >= $len) {
                break;
            }

            $i++; // skip '('
            $fields = [];
            $current = '';
            $inString = false;

            while ($i < $len) {
                $ch = $valuesSql[$i];

                if ($inString) {
                    if ($ch === '\\' && $i + 1 < $len) {
                        $next = $valuesSql[$i + 1];
                        $current .= match ($next) {
                            'n' => "\n",
                            'r' => "\r",
                            't' => "\t",
                            '0' => "\0",
                            'Z' => chr(26),
                            default => $next,
                        };
                        $i += 2;

                        continue;
                    }

                    if ($ch === "'") {
                        if ($i + 1 < $len && $valuesSql[$i + 1] === "'") {
                            $current .= "'";
                            $i += 2;

                            continue;
                        }

                        $inString = false;
                        $i++;

                        continue;
                    }

                    $current .= $ch;
                    $i++;

                    continue;
                }

                if ($ch === "'") {
                    $inString = true;
                    $current = '';
                    $i++;

                    continue;
                }

                if ($ch === ',') {
                    $fields[] = $this->normalizeSqlLiteral($current);
                    $current = '';
                    $i++;

                    continue;
                }

                if ($ch === ')') {
                    $fields[] = $this->normalizeSqlLiteral($current);
                    $rows[] = $fields;
                    $i++;

                    break;
                }

                if (! ctype_space($ch)) {
                    $current .= $ch;
                }

                $i++;
            }
        }

        return $rows;
    }

    private function normalizeSqlLiteral(string $raw): mixed
    {
        $raw = trim($raw);

        if ($raw === '' || strtoupper($raw) === 'NULL') {
            return null;
        }

        return $raw;
    }

    private function recordFailure(string $source, mixed $legacyId, Throwable $e): void
    {
        $this->stats['skipped_failed']++;

        $message = sprintf(
            '%s #%s: %s',
            $source,
            $legacyId ?? '?',
            Str::limit($e->getMessage(), 200, '')
        );

        if (count($this->failures) < 25) {
            $this->failures[] = $message;
        }

        $this->output->writeln('');
        $this->warn('Skipped failed insert — '.$message);
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->info('Import summary');
        $this->table(
            ['Metric', 'Count'],
            collect($this->stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all()
        );

        if ($this->failures !== []) {
            $this->newLine();
            $this->warn('Failed inserts (showing up to 25):');
            foreach ($this->failures as $failure) {
                $this->line('  - '.$failure);
            }

            $remaining = $this->stats['skipped_failed'] - count($this->failures);
            if ($remaining > 0) {
                $this->line("  … and {$remaining} more");
            }
        }
    }
}
