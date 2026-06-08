<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Campaign;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigrateCampaignImagesTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_DISK = 'old_activities_test';

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // ── Point old_mysql to a second in-memory SQLite database ────────────
        Config::set('database.connections.old_mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::purge('old_mysql');

        DB::connection('old_mysql')->statement('
            CREATE TABLE activities (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                slug       TEXT NOT NULL,
                name_ar    TEXT,
                name_en    TEXT,
                deleted_at DATETIME
            )
        ');

        DB::connection('old_mysql')->statement('
            CREATE TABLE activity_media (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                activity_id INTEGER NOT NULL,
                image       TEXT NOT NULL,
                type        TEXT NOT NULL DEFAULT "image",
                is_private  INTEGER NOT NULL DEFAULT 0,
                created_at  DATETIME,
                updated_at  DATETIME,
                deleted_at  DATETIME
            )
        ');

        // ── Real temp dir so addMedia() can resolve an actual file path ───────
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'old_activities_test_'.uniqid();
        mkdir($this->tempDir, 0755, true);

        Config::set('filesystems.disks.'.self::OLD_DISK, [
            'driver' => 'local',
            'root' => $this->tempDir,
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('old_mysql');

        // Clean up temp source files created during the test
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    private function deleteDirectory(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function seedActivity(string $slug, ?string $deletedAt = null): int
    {
        return DB::connection('old_mysql')->table('activities')->insertGetId([
            'slug' => $slug,
            'name_en' => 'Activity '.$slug,
            'name_ar' => 'نشاط '.$slug,
            'deleted_at' => $deletedAt,
        ]);
    }

    private function seedMedia(int $activityId, string $filename, string $type = 'image', ?string $deletedAt = null): int
    {
        return DB::connection('old_mysql')->table('activity_media')->insertGetId([
            'activity_id' => $activityId,
            'image' => $filename,
            'type' => $type,
            'is_private' => 0,
            'deleted_at' => $deletedAt,
        ]);
    }

    private function putFile(string $filename): void
    {
        // Use a real JPEG so Spatie's MIME-type check passes for gallery collection.
        // The routing to image vs video collection is driven by activity_media.type,
        // not by the file's actual MIME type, so a JPEG is fine for all test cases.
        $fake = UploadedFile::fake()->image($filename);
        copy($fake->getRealPath(), $this->tempDir.DIRECTORY_SEPARATOR.$filename);
    }

    private function createCampaign(string $slug): Campaign
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();

        return Campaign::factory()->create([
            'slug' => $slug,
            'created_by' => $user->id,
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_migrates_an_image_into_the_gallery_collection(): void
    {
        $campaign = $this->createCampaign('test-campaign');
        $activityId = $this->seedActivity('test-campaign');
        $this->seedMedia($activityId, 'photo.jpg');
        $this->putFile('photo.jpg', 'fake-image-content');

        $this->artisan('migrate:campaign-images', [
            '--source-disk' => self::OLD_DISK,
            '--image-collection' => 'gallery',
        ])->assertExitCode(0);

        $this->assertCount(1, $campaign->fresh()->getMedia('gallery'));
        $this->assertSame('photo.jpg', $campaign->fresh()->getMedia('gallery')->first()->file_name);
    }

    public function test_migrates_a_video_into_the_video_collection(): void
    {
        $campaign = $this->createCampaign('video-campaign');
        $activityId = $this->seedActivity('video-campaign');
        $this->seedMedia($activityId, 'clip.mp4', 'video');
        $this->putFile('clip.mp4', 'fake-video-content');

        $this->artisan('migrate:campaign-images', [
            '--source-disk' => self::OLD_DISK,
            '--video-collection' => 'gallery',
        ])->assertExitCode(0);

        $this->assertCount(1, $campaign->fresh()->getMedia('gallery'));
        $this->assertSame('clip.mp4', $campaign->fresh()->getMedia('gallery')->first()->file_name);
    }

    public function test_skips_records_where_campaign_slug_does_not_exist_in_new_db(): void
    {
        $activityId = $this->seedActivity('orphan-activity');
        $this->seedMedia($activityId, 'orphan.jpg');
        $this->putFile('orphan.jpg');

        $this->artisan('migrate:campaign-images', ['--source-disk' => self::OLD_DISK])
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('media')->count());
    }

    public function test_records_a_failure_when_the_file_is_missing_on_the_source_disk(): void
    {
        $campaign = $this->createCampaign('missing-file-campaign');
        $activityId = $this->seedActivity('missing-file-campaign');
        $this->seedMedia($activityId, 'missing.jpg');

        $this->artisan('migrate:campaign-images', ['--source-disk' => self::OLD_DISK])
            ->assertExitCode(1);

        $this->assertCount(0, $campaign->fresh()->getMedia('gallery'));
    }

    public function test_is_idempotent_and_skips_already_migrated_files(): void
    {
        $campaign = $this->createCampaign('idempotent-campaign');
        $activityId = $this->seedActivity('idempotent-campaign');
        $this->seedMedia($activityId, 'img.jpg');
        $this->putFile('img.jpg');

        $this->artisan('migrate:campaign-images', ['--source-disk' => self::OLD_DISK])
            ->assertExitCode(0);

        $this->artisan('migrate:campaign-images', ['--source-disk' => self::OLD_DISK])
            ->assertExitCode(0);

        $this->assertCount(1, $campaign->fresh()->getMedia('gallery'));
    }

    public function test_dry_run_does_not_write_any_media_records(): void
    {
        $campaign = $this->createCampaign('dry-campaign');
        $activityId = $this->seedActivity('dry-campaign');
        $this->seedMedia($activityId, 'dry.jpg');
        $this->putFile('dry.jpg');

        $this->artisan('migrate:campaign-images', [
            '--source-disk' => self::OLD_DISK,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertCount(0, $campaign->fresh()->getMedia('gallery'));
    }

    public function test_ignores_soft_deleted_activity_media_records(): void
    {
        $campaign = $this->createCampaign('soft-deleted-campaign');
        $activityId = $this->seedActivity('soft-deleted-campaign');
        $this->seedMedia($activityId, 'deleted.jpg', 'image', now()->toDateTimeString());
        $this->putFile('deleted.jpg');

        $this->artisan('migrate:campaign-images', ['--source-disk' => self::OLD_DISK])
            ->assertExitCode(0);

        $this->assertCount(0, $campaign->fresh()->getMedia('gallery'));
    }

    public function test_ignores_media_for_soft_deleted_activities(): void
    {
        $campaign = $this->createCampaign('active-campaign');
        $activityId = $this->seedActivity('active-campaign', now()->toDateTimeString());
        $this->seedMedia($activityId, 'from-deleted.jpg');
        $this->putFile('from-deleted.jpg');

        $this->artisan('migrate:campaign-images', ['--source-disk' => self::OLD_DISK])
            ->assertExitCode(0);

        $this->assertCount(0, $campaign->fresh()->getMedia('gallery'));
    }

    public function test_type_filter_limits_migration_to_images_only(): void
    {
        $campaign = $this->createCampaign('mixed-campaign');
        $activityId = $this->seedActivity('mixed-campaign');
        $this->seedMedia($activityId, 'photo.jpg', 'image');
        $this->seedMedia($activityId, 'clip.mp4', 'video');
        $this->putFile('photo.jpg');
        $this->putFile('clip.mp4');

        $this->artisan('migrate:campaign-images', [
            '--source-disk' => self::OLD_DISK,
            '--type' => 'image',
        ])->assertExitCode(0);

        $media = $campaign->fresh()->getMedia('gallery');

        $this->assertCount(1, $media);
        $this->assertSame('photo.jpg', $media->first()->file_name);
    }

    public function test_stores_migration_custom_properties_on_the_media_record(): void
    {
        $campaign = $this->createCampaign('props-campaign');
        $activityId = $this->seedActivity('props-campaign');
        $mediaId = $this->seedMedia($activityId, 'prop.jpg');
        $this->putFile('prop.jpg');

        $this->artisan('migrate:campaign-images', ['--source-disk' => self::OLD_DISK])
            ->assertExitCode(0);

        $mediaItem = $campaign->fresh()->getMedia('gallery')->first();

        $this->assertSame($mediaId, $mediaItem->getCustomProperty('migrated_from_id'));
        $this->assertSame($activityId, $mediaItem->getCustomProperty('original_activity_id'));
        $this->assertSame('image', $mediaItem->getCustomProperty('original_type'));
    }

    public function test_exits_with_failure_when_the_old_db_is_unreachable(): void
    {
        Config::set('database.connections.old_mysql', [
            'driver' => 'sqlite',
            'database' => '/non/existent/path/db.sqlite',
        ]);

        DB::purge('old_mysql');

        $this->artisan('migrate:campaign-images', ['--source-disk' => self::OLD_DISK])
            ->assertExitCode(1);
    }
}
