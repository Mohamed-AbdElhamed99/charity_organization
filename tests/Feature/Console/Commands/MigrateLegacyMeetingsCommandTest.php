<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\AttendeeRole;
use App\Enums\DecisionStatus;
use App\Enums\DecisionType;
use App\Enums\MeetingLocationType;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Enums\MinutesLanguage;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\MeetingDecision;
use App\Models\MeetingMinutes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrateLegacyMeetingsCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDir;

    private string $meetingsPath;

    private string $minutesPath;

    private string $activitiesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'legacy_meetings_'.uniqid();
        File::ensureDirectoryExists($this->tempDir);

        $this->meetingsPath = $this->tempDir.DIRECTORY_SEPARATOR.'meetings.sql';
        $this->minutesPath = $this->tempDir.DIRECTORY_SEPARATOR.'minutes.sql';
        $this->activitiesPath = $this->tempDir.DIRECTORY_SEPARATOR.'activities.sql';

        File::put($this->meetingsPath, <<<'SQL'
INSERT INTO `meetings` (`id`, `club_id`, `board_id`, `meeting_type_id`, `meeting_title_ar`, `meeting_title_en`, `location_ar`, `location_en`, `time`, `date`, `meeting_called_by`, `facilitator`, `note_taker`, `timekeeper`, `status`, `meeting_types`, `lang`, `is_private`, `created_at`, `updated_at`, `deleted_at`, `lat`, `lng`) VALUES
(10, 10, 2, 1, 'اجتماع محذوف', 'Deleted Board Meeting', 'موقع', '99 Madison Ave', '10:0', '2022-8-11', 197, 194, 193, 194, 1, NULL, 'all', 0, '2022-09-27 18:43:55', '2022-09-27 18:43:55', '2022-10-01 00:00:00', 40.74, -73.98),
(20, 10, 2, 1, 'اجتماع مجلس الإدارة', 'Monthly Board Meeting', 'موقع عربي', '99 Madison Ave, New York', '10:1', '2022-9-14', 197, 194, 193, 194, 1, 'online', 'all', 0, '2022-09-27 18:43:55', '2022-09-27 18:43:55', NULL, 40.74, -73.98),
(30, 10, 2, 3, 'اجتماع سنوي', 'Annual Assembly', 'موقع', 'Assembly Hall', '9:30', '2023-1-5', 197, 194, 193, 194, 0, 'inperson', 'all', 0, '2023-01-01 10:00:00', '2023-01-01 10:00:00', NULL, 40.74, -73.98);

INSERT INTO `meeting_agendas` (`id`, `meeting_id`, `name_en`, `name_ar`, `time_allotted`, `presenter`, `discussion_ar`, `discussion_en`, `conclusions_ar`, `conclusions_en`, `created_at`, `updated_at`, `deleted_at`, `lang`, `purpose`) VALUES
(100, 20, 'Donation campaign agenda item', 'بند عربي', '01:00:00', 197, 'مناقشة عربية', 'Mr. Amin presented the donation idea.', 'خلاصة', 'Members agreed.', '2022-12-08 12:18:06', '2022-12-08 12:18:06', NULL, 'all', 'activity'),
(101, 20, 'Follow-up logistics', 'لوجستيات', '00:30:00', 197, 'عربي', 'Logistics were discussed next.', 'خلاصة', 'Agreed.', '2022-12-08 12:20:00', '2022-12-08 12:20:00', NULL, 'all', NULL),
(102, 10, 'Deleted meeting agenda', 'محذوف', '01:00:00', 197, 'عربي', 'Should be skipped with meeting.', 'خلاصة', 'N/A', '2022-12-08 12:18:06', '2022-12-08 12:18:06', NULL, 'all', NULL);

INSERT INTO `meeting_actions` (`id`, `meeting_agendas_id`, `name_ar`, `name_en`, `person_responsible`, `date`, `time`, `created_at`, `updated_at`, `deleted_at`) VALUES
(200, 100, 'اجراء عربي', 'Launch the donation campaign page', 194, '2021-09-05', '10:30:00', '2022-12-08 12:29:26', '2022-12-08 12:29:26', NULL),
(201, 102, 'محذوف', 'Action on deleted meeting', 194, '2021-09-05', '10:30:00', '2022-12-08 12:29:26', '2022-12-08 12:29:26', NULL);

INSERT INTO `meeting_activities` (`id`, `meeting_id`, `agenda_id`, `activity_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(300, 20, 100, 9, '2022-12-09 13:00:00', '2022-12-09 13:00:00', NULL),
(301, 20, 100, 999, '2022-12-09 13:00:00', '2022-12-09 13:00:00', NULL),
(302, 10, 102, 9, '2022-12-09 13:00:00', '2022-12-09 13:00:00', NULL);

INSERT INTO `meeting_attenders` (`id`, `user_id`, `meeting_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(400, 194, 20, '2022-12-09 12:00:00', '2022-12-09 12:00:00', NULL),
(401, 193, 20, '2022-12-09 12:00:00', '2022-12-09 12:00:00', NULL),
(402, 197, 20, '2022-12-09 12:00:00', '2022-12-09 12:00:00', NULL),
(403, 194, 10, '2022-12-09 12:00:00', '2022-12-09 12:00:00', NULL);

INSERT INTO `users` (`id`, `name_ar`, `name_en`, `email`, `password`, `phone`, `national_id`, `address`, `job`, `age`, `birthdate`, `bio`, `image`, `front_id`, `back_id`, `clearance`, `facebook`, `twitter`, `instagram`, `reddit`, `linkedin`, `email_verified_at`, `user_type_id`, `is_reseted_password`, `rejected_reason`, `remember_token`, `timezone`, `created_at`, `updated_at`, `deleted_at`, `verify_code`, `country_id`, `firebase_token`, `device_type`, `gender`, `featured`, `skills`, `start_time`, `end_time`, `terms`) VALUES
(193, 'سكرتير', 'Sara Secretary', 'sara@example.com', 'hash', '01000000001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, '2022-01-01 00:00:00', '2022-01-01 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 1),
(194, 'رئيس', 'Frank Facilitator', 'frank@example.com', 'hash', '01000000002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, '2022-01-01 00:00:00', '2022-01-01 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 1),
(197, 'عضو', 'Member User', 'member@example.com', 'hash', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, '2022-01-01 00:00:00', '2022-01-01 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 1);
SQL);

        File::put($this->minutesPath, <<<'SQL'
INSERT INTO `minutes` (`id`, `subject`, `time`, `meeting_id`, `created_at`, `updated_at`) VALUES
(500, 'Each attendee introduced themselves.', '09:30:00', 20, '2023-07-10 10:00:00', '2023-07-10 10:00:00'),
(501, 'Discussion on the donation campaign.', '09:55:00', 20, '2023-07-10 10:05:00', '2023-07-10 10:05:00'),
(502, 'Minutes for deleted meeting.', '10:00:00', 10, '2023-07-10 10:00:00', '2023-07-10 10:00:00');
SQL);

        File::put($this->activitiesPath, <<<'SQL'
INSERT INTO `activities` (`id`, `club_id`, `slug`, `name_ar`, `name_en`, `details_ar`, `details_en`, `activity_video`, `start_date`, `end_date`, `start_at`, `end_at`, `attends_number`, `type`, `status`, `budget`, `expenses`, `lat`, `lng`, `address`, `created_at`, `updated_at`, `deleted_at`, `meta_title_ar`, `meta_title_en`, `meta_description_ar`, `meta_description_en`, `meta_image`, `open_donation_form`, `target`, `is_repeated`, `repeat_until`, `excerpt_ar`, `excerpt_en`, `support`) VALUES
(9, 10, 'haya-karima-campaign', 'حياة كريمة', 'Haya Karima Campaign', NULL, NULL, NULL, '2021-01-01', '2021-12-31', NULL, NULL, 0, 1, 1, 0, 0, NULL, NULL, NULL, '2021-01-01 00:00:00', '2021-01-01 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, NULL, NULL, NULL, 0);
SQL);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_migrates_meetings_attendees_decisions_campaigns_and_minutes(): void
    {
        $user = User::factory()->create();
        $category = CampaignCategory::factory()->create();
        $campaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'slug' => 'haya-karima-campaign',
            'created_by' => $user->id,
        ]);

        $exitCode = Artisan::call('migrate:legacy-meetings', [
            '--user-id' => $user->id,
            '--meetings' => $this->meetingsPath,
            '--minutes' => $this->minutesPath,
            '--activities' => $this->activitiesPath,
        ]);

        $this->assertSame(0, $exitCode);

        // Soft-deleted legacy meeting skipped
        $this->assertSame(2, Meeting::query()->count());
        $this->assertNull(Meeting::query()->where('title', 'Deleted Board Meeting')->first());

        $board = Meeting::query()->where('title', 'Monthly Board Meeting')->first();
        $this->assertNotNull($board);
        $this->assertSame('Monthly Board Meeting', $board->title);
        $this->assertSame('Monthly Board Meeting', $board->title_en);
        $this->assertSame(MeetingType::Board, $board->type);
        $this->assertSame(MeetingStatus::Completed, $board->status);
        $this->assertSame(MeetingLocationType::Online, $board->location_type);
        $this->assertSame('99 Madison Ave, New York', $board->location);
        $this->assertSame('2022-09-14', $board->meeting_date->toDateString());
        $this->assertSame('10:01:00', $board->start_time);
        $this->assertSame('Frank Facilitator', $board->chairperson);
        $this->assertSame('Sara Secretary', $board->secretary);
        $this->assertSame("1. Donation campaign agenda item\n2. Follow-up logistics", $board->agenda);
        $this->assertSame(
            "Mr. Amin presented the donation idea.\n\nLogistics were discussed next.",
            $board->description
        );

        $assembly = Meeting::query()->where('title', 'Annual Assembly')->first();
        $this->assertNotNull($assembly);
        $this->assertSame(MeetingType::GeneralAssembly, $assembly->type);
        $this->assertSame(MeetingStatus::Scheduled, $assembly->status);
        $this->assertSame(MeetingLocationType::Physical, $assembly->location_type);

        $attendees = MeetingAttendee::query()->where('meeting_id', $board->id)->orderBy('name')->get();
        $this->assertCount(3, $attendees);

        $chair = $attendees->firstWhere('name', 'Frank Facilitator');
        $this->assertNotNull($chair);
        $this->assertSame(AttendeeRole::Chair, $chair->role);
        $this->assertSame(AttendanceStatus::Attended, $chair->attendance_status);
        $this->assertSame('frank@example.com', $chair->email);

        $secretary = $attendees->firstWhere('name', 'Sara Secretary');
        $this->assertNotNull($secretary);
        $this->assertSame(AttendeeRole::Secretary, $secretary->role);

        $member = $attendees->firstWhere('name', 'Member User');
        $this->assertNotNull($member);
        $this->assertSame(AttendeeRole::Member, $member->role);

        $decision = MeetingDecision::query()->where('meeting_id', $board->id)->first();
        $this->assertNotNull($decision);
        $this->assertSame('Launch the donation campaign page', $decision->title);
        $this->assertSame(DecisionType::ActionItem, $decision->decision_type);
        $this->assertSame(DecisionStatus::Completed, $decision->status);
        $this->assertSame('Frank Facilitator', $decision->assigned_to);
        $this->assertSame('2021-09-05', $decision->due_date->toDateString());

        $this->assertTrue($board->campaigns()->where('campaigns.id', $campaign->id)->exists());
        $this->assertStringContainsString('skipped_unresolved_campaign', Artisan::output());

        $minutes = MeetingMinutes::query()->where('meeting_id', $board->id)->first();
        $this->assertNotNull($minutes);
        $this->assertSame(MinutesLanguage::En, $minutes->language);
        $this->assertStringContainsString('[09:30] Each attendee introduced themselves.', $minutes->content);
        $this->assertStringContainsString('[09:55] Discussion on the donation campaign.', $minutes->content);
        $this->assertSame(1, MeetingMinutes::query()->count());
    }

    public function test_dry_run_rolls_back_all_inserts(): void
    {
        $user = User::factory()->create();
        Campaign::factory()->create([
            'category_id' => CampaignCategory::factory(),
            'slug' => 'haya-karima-campaign',
            'created_by' => $user->id,
        ]);

        $exitCode = Artisan::call('migrate:legacy-meetings', [
            '--user-id' => $user->id,
            '--meetings' => $this->meetingsPath,
            '--minutes' => $this->minutesPath,
            '--activities' => $this->activitiesPath,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, Meeting::query()->count());
        $this->assertSame(0, MeetingAttendee::query()->count());
        $this->assertSame(0, MeetingDecision::query()->count());
        $this->assertSame(0, MeetingMinutes::query()->count());
        $this->assertSame(0, DB::table('campaign_meeting')->count());
    }

    public function test_include_deleted_imports_soft_deleted_meetings(): void
    {
        $user = User::factory()->create();
        Campaign::factory()->create([
            'category_id' => CampaignCategory::factory(),
            'slug' => 'haya-karima-campaign',
            'created_by' => $user->id,
        ]);

        Artisan::call('migrate:legacy-meetings', [
            '--user-id' => $user->id,
            '--meetings' => $this->meetingsPath,
            '--minutes' => $this->minutesPath,
            '--activities' => $this->activitiesPath,
            '--include-deleted' => true,
        ]);

        $deleted = Meeting::withTrashed()->where('title', 'Deleted Board Meeting')->first();
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);
        $this->assertSame(3, Meeting::withTrashed()->count());
    }
}
