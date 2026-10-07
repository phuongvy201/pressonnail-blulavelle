<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNailTryOnJob;
use App\Models\ApiUploadAsset;
use App\Models\NailTryOn;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Recovery command for the 2026-10-07 sample hand outage.
 *
 * What went wrong:
 *   - Some sample-hand assets were migrated from S3 → local, but the file
 *     on the local disk is missing or empty (S3 download returned 0 bytes
 *     or the local write silently failed).
 *   - The queue worker then fails the try-on job with
 *     `ASSET_EXPIRED: Hand image file is missing.`
 *
 * What this command does:
 *   1. Walks every sample-hand asset in the database.
 *   2. If the binary is missing on the configured disk, tries to recover it
 *      from `public/images/virtual-nail/sample-hands/<basename>` (the
 *      public copy is the source of truth for sample hands).
 *   3. Walks every failed try-on job from the last `--hours` (default 24 h)
 *     whose error code is `ASSET_EXPIRED` and re-queues it — but only if
 *     the hand asset is now actually present on disk.
 *
 * User-uploaded (non-sample) assets are NOT touched — they have no public
 * source to recover from and must be re-uploaded by the customer.
 */
class RecoverSampleHands extends Command
{
    protected $signature = 'sample-hands:recover
                            {--hours=24 : Re-queue failed jobs created within this many hours}
                            {--dry-run : Report what would change without modifying anything}
                            {--job=* : Re-queue only these specific tryOn public_ids}';

    protected $description = 'Restore missing sample-hand binaries and re-queue the related try-on jobs.';

    /** Where the public copy of each sample hand lives — the seeder seeds from this folder. */
    private const PUBLIC_DIR = 'images/virtual-nail/sample-hands';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $hours = (int) $this->option('hours');
        $onlyJobs = (array) $this->option('job');

        $this->info(sprintf(
            'Sample-hand recovery — %s, lookback=%dh, job filter=%s',
            $dryRun ? 'DRY RUN' : 'LIVE',
            $hours,
            $onlyJobs ? implode(',', $onlyJobs) : 'all-failed',
        ));

        // ── Step 1: restore missing binaries for sample-hand assets ────────
        $sampleAssets = ApiUploadAsset::query()
            ->where('purpose', ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND)
            ->whereNull('trashed_at')
            ->orderBy('id')
            ->get();

        $this->info(sprintf('Found %d sample-hand asset(s) in DB.', $sampleAssets->count()));

        $recovered = 0;
        $alreadyOk = 0;
        $unrecoverable = 0;

        /** @var ApiUploadAsset $asset */
        foreach ($sampleAssets as $asset) {
            if ($this->ensureBinaryPresent($asset, $dryRun)) {
                $recovered++;
            } elseif ($this->binaryPresent($asset)) {
                $alreadyOk++;
            } else {
                $unrecoverable++;
            }
        }

        $this->line(sprintf(
            '  Binaries: %d recovered, %d already present, %d unrecoverable.',
            $recovered, $alreadyOk, $unrecoverable,
        ));

        // ── Step 2: re-queue failed jobs whose hand asset is now present ───
        //
        // Two error codes are interesting here, both stemming from the same
        // root cause (sample-hand asset on S3 before the 2026-10-07
        // migration that moved everything to local):
        //
        //   - ASSET_EXPIRED     → `filesize()` returned 0 because
        //                          `$disk->path()` returned the S3 key.
        //   - UNSUPPORTED_IMAGE → reached `validateHandImage()` but the
        //                          same root cause produced an empty file
        //                          on the local FS.
        //
        // Either way, re-queueing against the now-local disk should let the
        // job pass validation. Non-sample jobs (user uploads) are still
        // skipped — they have no public source to recover from.
        $jobsQuery = NailTryOn::query()
            ->where('status', 'failed')
            ->whereIn('error_code', ['ASSET_EXPIRED', 'UNSUPPORTED_IMAGE'])
            ->where('created_at', '>=', now()->subHours($hours))
            ->with('handAsset');

        if (! empty($onlyJobs)) {
            $jobsQuery->whereIn('public_id', $onlyJobs);
        }

        $failedJobs = $jobsQuery->orderByDesc('id')->get();

        $this->info(sprintf('Found %d failed ASSET_EXPIRED job(s) in the lookback window.', $failedJobs->count()));

        $reQueued = 0;
        $skippedMissingFile = 0;
        $skippedNotSample = 0;

        /** @var NailTryOn $job */
        foreach ($failedJobs as $job) {
            $hand = $job->handAsset;

            if (! $hand) {
                $this->warn("  - {$job->public_id}: hand asset relation is null, skipping.");
                continue;
            }

            if ($hand->purpose !== ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND) {
                // User-uploaded hand — nothing to recover without the original binary.
                $skippedNotSample++;
                $this->warn("  - {$job->public_id}: hand is a user upload (asset {$hand->id}), cannot recover.");
                continue;
            }

            if (! $this->binaryPresent($hand)) {
                $skippedMissingFile++;
                $this->warn("  - {$job->public_id}: hand file is still missing on disk, skipping.");
                continue;
            }

            if ($dryRun) {
                $this->line("  - {$job->public_id}: would reset to queued and re-dispatch.");
                $reQueued++;
                continue;
            }

            DB::transaction(function () use ($job) {
                $job->update([
                    'status' => 'queued',
                    'error_code' => null,
                    'error_message' => null,
                    'started_at' => null,
                    'completed_at' => null,
                ]);
            });

            ProcessNailTryOnJob::dispatch($job->id);
            $reQueued++;
            $this->info("  ✓ {$job->public_id}: re-queued.");
        }

        $this->line(sprintf(
            '  Jobs: %d re-queued, %d skipped (file still missing), %d skipped (user upload).',
            $reQueued, $skippedMissingFile, $skippedNotSample,
        ));

        Log::info('sample-hands:recover finished', [
            'dry_run' => $dryRun,
            'binaries_recovered' => $recovered,
            'binaries_already_ok' => $alreadyOk,
            'binaries_unrecoverable' => $unrecoverable,
            'jobs_requeued' => $reQueued,
            'jobs_skipped_missing_file' => $skippedMissingFile,
            'jobs_skipped_user_upload' => $skippedNotSample,
        ]);

        return self::SUCCESS;
    }

    /**
     * If the asset's binary is missing on the configured disk, attempt to
     * restore it from the public sample-hands folder. Returns true if the
     * binary was written, false otherwise (already present, or no source).
     */
    private function ensureBinaryPresent(ApiUploadAsset $asset, bool $dryRun): bool
    {
        if ($this->binaryPresent($asset)) {
            return false;
        }

        $filename = basename((string) $asset->path);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return false;
        }

        $publicAbsolute = public_path(self::PUBLIC_DIR.'/'.$filename);
        if (! is_file($publicAbsolute) || ! is_readable($publicAbsolute)) {
            $this->warn("  - asset {$asset->id} ({$asset->public_id}): no public source at {$publicAbsolute}.");
            return false;
        }

        $bytes = file_get_contents($publicAbsolute);
        if ($bytes === false || $bytes === '') {
            $this->warn("  - asset {$asset->id}: public source is empty/unreadable.");
            return false;
        }

        if ($dryRun) {
            $this->line("  - asset {$asset->id} ({$asset->public_id}): would restore from public source (" .strlen($bytes). ' bytes).');
            return true;
        }

        $disk = $asset->disk ?: 'local';
        Storage::disk($disk)->put($asset->path, $bytes);

        // Also recompute byte_size/checksum in case the public copy differs from the S3 copy.
        $asset->update([
            'byte_size' => strlen($bytes),
            'checksum_sha256' => hash('sha256', $bytes),
        ]);

        $this->info("  ✓ asset {$asset->id} ({$asset->public_id}): restored " .strlen($bytes). " bytes to disk '{$disk}'.");
        return true;
    }

    /** True iff the asset's binary actually exists on the configured disk. */
    private function binaryPresent(ApiUploadAsset $asset): bool
    {
        if (! $asset->path || ! $asset->disk) {
            return false;
        }

        try {
            return (bool) Storage::disk($asset->disk)->exists($asset->path);
        } catch (\Throwable) {
            return false;
        }
    }
}