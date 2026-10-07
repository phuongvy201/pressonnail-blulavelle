<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Migrate sample hand binaries from S3 to local disk so the queue worker
     * (ProcessNailTryOnJob) can read them via local FS operations.
     *
     * Background:
     *   - SampleHandSeeder used to record disk = 's3', but the worker calls
     *     Storage::disk($hand->disk)->path($hand->path) and then reads the result
     *     with filesize()/getimagesize()/imagecreatefromstring(). On the S3 driver,
     *     path() returns a virtual key (not a real local file), so validateHandImage()
     *     returns 'UNSUPPORTED_IMAGE' and every sample-hand try-on fails.
     *
     *   - This migration:
     *       1. Finds all sample-hand assets currently on the S3 disk.
     *       2. Downloads each binary from S3 into Storage::disk('local').
     *       3. Re-points the row to the local disk.
     *       4. Deletes the S3 copy on success.
     *
     *   - Safe to re-execute — already-migrated rows are skipped (disk = 'local').
     */
    public function up(): void
    {
        $sourceDisk = 's3';
        $targetDisk = 'local';

        $rows = DB::table('api_upload_assets')
            ->where('purpose', 'virtual_try_on_sample_hand')
            ->where('disk', $sourceDisk)
            ->whereNotNull('path')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($rows as $row) {
            try {
                if (! Storage::disk($sourceDisk)->exists($row->path)) {
                    Log::warning('Sample hand migration: source file missing on S3', [
                        'public_id' => $row->public_id,
                        'path' => $row->path,
                    ]);
                    $skipped++;
                    continue;
                }

                $binary = Storage::disk($sourceDisk)->get($row->path);
                if ($binary === null || $binary === '') {
                    $skipped++;
                    continue;
                }

                Storage::disk($targetDisk)->put($row->path, $binary);

                DB::table('api_upload_assets')
                    ->where('id', $row->id)
                    ->update([
                        'disk' => $targetDisk,
                        'updated_at' => now(),
                    ]);

                // Best-effort cleanup of the S3 copy.
                try {
                    Storage::disk($sourceDisk)->delete($row->path);
                } catch (\Throwable $e) {
                    Log::info('Sample hand migration: could not delete S3 copy (non-fatal)', [
                        'public_id' => $row->public_id,
                        'message' => $e->getMessage(),
                    ]);
                }

                $migrated++;
            } catch (\Throwable $e) {
                Log::error('Sample hand migration: failed to migrate row', [
                    'public_id' => $row->public_id ?? null,
                    'path' => $row->path ?? null,
                    'message' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('Sample hand migration complete', [
            'migrated' => $migrated,
            'skipped' => $skipped,
            'failed' => $failed,
            'total' => $rows->count(),
        ]);
    }

    /**
     * Reverse: copy binaries back to S3 (best-effort) and re-point rows.
     */
    public function down(): void
    {
        $sourceDisk = 'local';
        $targetDisk = 's3';

        $rows = DB::table('api_upload_assets')
            ->where('purpose', 'virtual_try_on_sample_hand')
            ->where('disk', $sourceDisk)
            ->whereNotNull('path')
            ->get();

        foreach ($rows as $row) {
            try {
                if (! Storage::disk($sourceDisk)->exists($row->path)) {
                    continue;
                }

                $binary = Storage::disk($sourceDisk)->get($row->path);
                if ($binary === null || $binary === '') {
                    continue;
                }

                Storage::disk($targetDisk)->put($row->path, $binary);

                DB::table('api_upload_assets')
                    ->where('id', $row->id)
                    ->update([
                        'disk' => $targetDisk,
                        'updated_at' => now(),
                    ]);
            } catch (\Throwable $e) {
                Log::error('Sample hand rollback: failed to migrate row back to S3', [
                    'public_id' => $row->public_id ?? null,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
};