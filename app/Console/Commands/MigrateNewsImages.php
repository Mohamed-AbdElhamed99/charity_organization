<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MigrateNewsImages extends Command
{
    protected $signature = 'migrate:news-images
                            {--source-disk=old_news : Filesystem disk name pointing to the old images directory}
                            {--media-collection=images : Spatie Media Library collection name}
                            {--dry-run : Preview what would be migrated without writing anything}
                            {--chunk=100 : Number of records to process per chunk}';

    protected $description = 'Migrate news images from legacy news_images table (old DB) into Spatie Media Library (new DB)';

    private int $migrated   = 0;
    private int $skipped    = 0;
    private int $failed     = 0;
    private array $failures = [];

    public function handle(): int
    {
        $disk       = $this->option('source-disk');
        $collection = $this->option('media-collection');
        $dryRun     = $this->option('dry-run');
        $chunk      = (int) $this->option('chunk');

        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║       News Images Migration              ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        if ($dryRun) {
            $this->warn('  [DRY RUN] No files or DB records will be written.');
            $this->info('');
        }

        // ── Verify old DB connection ──────────────────────────────────────────
        try {
            $total = DB::connection('old_mysql')
                ->table('news_images')
                ->whereNull('deleted_at')
                ->count();
        } catch (\Exception $e) {
            $this->error("Cannot connect to old DB: {$e->getMessage()}");
            return self::FAILURE;
        }

        if ($total === 0) {
            $this->info('No active records found in news_images. Nothing to migrate.');
            return self::SUCCESS;
        }

        $this->info("  Found <fg=yellow>{$total}</> active image records to process.");
        $this->info("  Source disk  : <fg=cyan>{$disk}</>");
        $this->info("  Collection   : <fg=cyan>{$collection}</>");
        $this->info('');

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Starting…');
        $bar->start();

        // ── Chunk through old records ─────────────────────────────────────────
        DB::connection('old_mysql')
            ->table('news_images')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($disk, $collection, $dryRun, $bar) {
                foreach ($rows as $row) {
                    $bar->setMessage("news_image #{$row->id}");

                    $this->processRow($row, $disk, $collection, $dryRun);

                    $bar->advance();
                }
            });

        $bar->setMessage('Done.');
        $bar->finish();

        $this->info('');
        $this->info('');
        $this->printSummary($dryRun);

        if (!empty($this->failures)) {
            $this->printFailures();
        }

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ── Per-row logic ─────────────────────────────────────────────────────────

    private function processRow(object $row, string $disk, string $collection, bool $dryRun): void
    {
        // 1. Resolve the target News model in the NEW database
        $news = News::find($row->news_id);

        if (!$news) {
            $this->recordSkip($row->id, $row->news_id, "News #{$row->news_id} not found in new DB");
            return;
        }

        // 2. Verify the file exists on the source disk
        $filename = $row->image; // e.g. "photo.jpg"

        if (!Storage::disk($disk)->exists($filename)) {
            $this->recordFailure($row->id, $row->news_id, "File not found on disk '{$disk}': {$filename}");
            return;
        }

        // 3. Skip if this exact file was already migrated (idempotency guard)
        $alreadyExists = $news->getMedia($collection)
            ->contains(fn ($m) => $m->file_name === $filename);

        if ($alreadyExists) {
            $this->recordSkip($row->id, $row->news_id, "Already migrated: {$filename}");
            return;
        }

        if ($dryRun) {
            $this->migrated++;
            return;
        }

        // 4. Add to Media Library from the resolved absolute path
        try {
            $absolutePath = Storage::disk($disk)->path($filename);

            $news->addMedia($absolutePath)
                ->preservingOriginal()           // keep the source file intact
                ->withCustomProperties([
                    'migrated_from_id'  => $row->id,
                    'original_news_id'  => $row->news_id,
                    'migrated_at'       => now()->toIso8601String(),
                ])
                ->toMediaCollection($collection);

            $this->migrated++;

        } catch (\Exception $e) {
            $this->recordFailure($row->id, $row->news_id, $e->getMessage());
            Log::error('MigrateNewsImages: failed', [
                'news_image_id' => $row->id,
                'news_id'       => $row->news_id,
                'filename'      => $filename,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function recordSkip(int $imageId, int $newsId, string $reason): void
    {
        $this->skipped++;
        Log::info("MigrateNewsImages: skipped news_image #{$imageId} (news #{$newsId}): {$reason}");
    }

    private function recordFailure(int $imageId, int $newsId, string $reason): void
    {
        $this->failed++;
        $this->failures[] = ['id' => $imageId, 'news_id' => $newsId, 'reason' => $reason];
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
            ['news_image_id', 'news_id', 'Reason'],
            $this->failures
        );
    }
}