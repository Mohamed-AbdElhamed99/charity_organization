<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MigrateCampaignImages extends Command
{
    protected $signature = 'migrate:campaign-images
                            {--source-disk=old_activities : Filesystem disk name pointing to the old activity images directory}
                            {--image-collection=gallery : Spatie Media Library collection name for images}
                            {--video-collection=gallery : Spatie Media Library collection name for videos}
                            {--type= : Limit migration to a specific media type: "image" or "video" (defaults to both)}
                            {--dry-run : Preview what would be migrated without writing anything}
                            {--chunk=100 : Number of records to process per chunk}';

    protected $description = 'Migrate campaign images/videos from legacy activity_media table (old DB) into Spatie Media Library (new DB)';

    private int $migrated = 0;

    private int $skipped = 0;

    private int $failed = 0;

    private array $failures = [];

    public function handle(): int
    {
        $disk = $this->option('source-disk');
        $imageCollection = $this->option('image-collection');
        $videoCollection = $this->option('video-collection');
        $typeFilter = $this->option('type');
        $dryRun = $this->option('dry-run');
        $chunk = (int) $this->option('chunk');

        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║     Campaign Images Migration            ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        if ($dryRun) {
            $this->warn('  [DRY RUN] No files or DB records will be written.');
            $this->info('');
        }

        if ($typeFilter && ! in_array($typeFilter, ['image', 'video'])) {
            $this->error("Invalid --type value: '{$typeFilter}'. Use 'image' or 'video'.");

            return self::FAILURE;
        }

        // ── Verify old DB connection ──────────────────────────────────────────
        try {
            $query = DB::connection('old_mysql')
                ->table('activity_media')
                ->whereNull('deleted_at');

            if ($typeFilter) {
                $query->where('type', $typeFilter);
            }

            $total = $query->count();
        } catch (\Exception $e) {
            $this->error("Cannot connect to old DB: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($total === 0) {
            $this->info('No active records found in activity_media. Nothing to migrate.');

            return self::SUCCESS;
        }

        $this->info("  Found <fg=yellow>{$total}</> active media records to process.");
        $this->info("  Source disk       : <fg=cyan>{$disk}</>");
        $this->info("  Image collection  : <fg=cyan>{$imageCollection}</>");
        $this->info("  Video collection  : <fg=cyan>{$videoCollection}</>");
        if ($typeFilter) {
            $this->info("  Type filter       : <fg=cyan>{$typeFilter}</>");
        }
        $this->info('');

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Starting…');
        $bar->start();

        // ── Chunk through old records (join activities to get slug for matching) ─
        $query = DB::connection('old_mysql')
            ->table('activity_media as am')
            ->join('activities as a', 'a.id', '=', 'am.activity_id')
            ->whereNull('am.deleted_at')
            ->whereNull('a.deleted_at')
            ->select([
                'am.id',
                'am.activity_id',
                'am.image',
                'am.type',
                'am.is_private',
                'a.slug as activity_slug',
            ])
            ->orderBy('am.id');

        if ($typeFilter) {
            $query->where('am.type', $typeFilter);
        }

        $query->chunk($chunk, function ($rows) use ($disk, $imageCollection, $videoCollection, $dryRun, $bar) {
            foreach ($rows as $row) {
                $bar->setMessage("activity_media #{$row->id}");

                $this->processRow($row, $disk, $imageCollection, $videoCollection, $dryRun);

                $bar->advance();
            }
        });

        $bar->setMessage('Done.');
        $bar->finish();

        $this->info('');
        $this->info('');
        $this->printSummary($dryRun);

        if (! empty($this->failures)) {
            $this->printFailures();
        }

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ── Per-row logic ─────────────────────────────────────────────────────────

    private function processRow(
        object $row,
        string $disk,
        string $imageCollection,
        string $videoCollection,
        bool $dryRun,
    ): void {
        // 1. Resolve the target Campaign model in the NEW database (match by slug)
        $campaign = Campaign::withTrashed()->where('slug', $row->activity_slug)->first();

        if (! $campaign) {
            $this->recordSkip(
                $row->id,
                $row->activity_id,
                "Campaign with slug '{$row->activity_slug}' not found in new DB",
            );

            return;
        }

        // 2. Determine target collection based on media type
        $collection = $row->type === 'video' ? $videoCollection : $imageCollection;

        // 3. Verify the file exists on the source disk
        $filename = $row->image;

        if (! Storage::disk($disk)->exists($filename)) {
            $this->recordFailure(
                $row->id,
                $row->activity_id,
                "File not found on disk '{$disk}': {$filename}",
            );

            return;
        }

        // 4. Skip if this exact file was already migrated (idempotency guard)
        $alreadyExists = $campaign->getMedia($collection)
            ->contains(fn ($m) => $m->file_name === $filename);

        if ($alreadyExists) {
            $this->recordSkip($row->id, $row->activity_id, "Already migrated: {$filename}");

            return;
        }

        if ($dryRun) {
            $this->migrated++;

            return;
        }

        // 5. Add to Media Library from the resolved absolute path
        try {
            $absolutePath = Storage::disk($disk)->path($filename);

            $campaign->addMedia($absolutePath)
                ->preservingOriginal()
                ->withCustomProperties([
                    'migrated_from_id' => $row->id,
                    'original_activity_id' => $row->activity_id,
                    'original_type' => $row->type,
                    'is_private' => (bool) $row->is_private,
                    'migrated_at' => now()->toIso8601String(),
                ])
                ->toMediaCollection($collection);

            $this->migrated++;

        } catch (\Exception $e) {
            $this->recordFailure($row->id, $row->activity_id, $e->getMessage());
            Log::error('MigrateCampaignImages: failed', [
                'activity_media_id' => $row->id,
                'activity_id' => $row->activity_id,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function recordSkip(int $mediaId, int $activityId, string $reason): void
    {
        $this->skipped++;
        Log::info("MigrateCampaignImages: skipped activity_media #{$mediaId} (activity #{$activityId}): {$reason}");
    }

    private function recordFailure(int $mediaId, int $activityId, string $reason): void
    {
        $this->failed++;
        $this->failures[] = ['id' => $mediaId, 'activity_id' => $activityId, 'reason' => $reason];
    }

    private function printSummary(bool $dryRun): void
    {
        $label = $dryRun ? 'Would migrate' : 'Migrated';

        $this->info('  ┌──────────────────────────────┐');
        $this->info("  │ <fg=green>✓ {$label}: {$this->migrated}</>");
        $this->info("  │ <fg=yellow>- Skipped : {$this->skipped}</>");
        $this->info("  │ <fg=red>✗ Failed  : {$this->failed}</>");
        $this->info('  └──────────────────────────────┘');
        $this->info('');
    }

    private function printFailures(): void
    {
        $this->error('  Failed records:');
        $this->table(
            ['activity_media_id', 'activity_id', 'Reason'],
            $this->failures,
        );
    }
}
