<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RenameProductMediaToTitle extends Command
{
    protected $signature = 'products:rename-media-to-title
        {--ids= : Comma-separated product IDs (e.g. 1,2,3)}
        {--limit= : Max products to process}
        {--only-s3 : Skip media not on configured S3 base URL}
        {--dry-run : Do not copy/delete or write DB}
        {--delete-old : Delete old S3 objects after successful copy}
        {--sleep=0 : Sleep N milliseconds between products}';

    protected $description = 'Copy/rename product media files on S3 to slug+timestamp naming based on product title, then update products.media URLs';

    public function handle(): int
    {
        $ids = $this->parseIdsOption($this->option('ids'));
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $onlyS3 = (bool) $this->option('only-s3');
        $dryRun = (bool) $this->option('dry-run');
        $deleteOld = (bool) $this->option('delete-old');
        $sleepMs = max(0, (int) $this->option('sleep'));

        $s3 = Storage::disk('s3');
        $s3BaseUrl = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/';

        $q = Product::query()->select(['id', 'name', 'slug', 'media'])->orderBy('id');
        if (!empty($ids)) {
            $q->whereIn('id', $ids);
        }
        if ($limit !== null && $limit > 0) {
            $q->limit($limit);
        }

        $total = (clone $q)->count();
        $this->info("Products matched: {$total}");
        if ($total === 0) {
            return Command::SUCCESS;
        }

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($q->cursor() as $product) {
            $processed++;

            $media = $product->media;
            if (is_string($media)) {
                $decoded = json_decode($media, true);
                $media = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($media) || $media === []) {
                $skipped++;
                $this->line("Skip #{$product->id} (no media)");
                $this->sleepMs($sleepMs);
                continue;
            }

            $baseSlug = trim((string) ($product->slug ?: Str::slug((string) $product->name)));
            $baseSlug = $baseSlug !== '' ? $baseSlug : 'product-' . $product->id;
            $uploadTs = time();

            $newMedia = [];
            $index = 1;
            $touched = false;

            try {
                foreach ($media as $item) {
                    if (is_string($item)) {
                        $res = $this->renameUrlItem($s3, $s3BaseUrl, $item, $baseSlug, $uploadTs, $index, $onlyS3, $dryRun, $deleteOld);
                        $newMedia[] = $res['value'];
                        $touched = $touched || $res['changed'];
                        $index++;
                        continue;
                    }

                    if (is_array($item) && (($item['type'] ?? null) === 'video')) {
                        $videoUrl = (string) ($item['url'] ?? '');
                        $posterUrl = isset($item['poster']) ? (string) $item['poster'] : null;

                        $videoRes = $this->renameUrlItem($s3, $s3BaseUrl, $videoUrl, $baseSlug, $uploadTs, $index, $onlyS3, $dryRun, $deleteOld);
                        $changed = $videoRes['changed'];

                        $newItem = $item;
                        $newItem['url'] = $videoRes['value'];

                        if ($posterUrl) {
                            // Poster uses same index and a "-poster" suffix if it follows our new naming.
                            $posterRes = $this->renameUrlItem($s3, $s3BaseUrl, $posterUrl, $baseSlug, $uploadTs, $index, $onlyS3, $dryRun, $deleteOld, true);
                            $newItem['poster'] = $posterRes['value'];
                            $changed = $changed || $posterRes['changed'];
                        }

                        $newMedia[] = $newItem;
                        $touched = $touched || $changed;
                        $index++;
                        continue;
                    }

                    // Unknown shape: keep as-is
                    $newMedia[] = $item;
                }

                if (!$touched) {
                    $skipped++;
                    $this->line("Skip #{$product->id} (no changes)");
                    $this->sleepMs($sleepMs);
                    continue;
                }

                $this->info("Update #{$product->id} media renamed" . ($dryRun ? " (dry-run)" : ""));

                if (!$dryRun) {
                    $product->media = array_values($newMedia);
                    $product->save();
                }

                $updated++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Error #{$product->id}: " . $e->getMessage());
                Log::error('products:rename-media-to-title failed.', [
                    'product_id' => $product->id,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            }

            $this->sleepMs($sleepMs);
        }

        $this->newLine();
        $this->info("Done. processed={$processed}, updated={$updated}, skipped={$skipped}, failed={$failed}" . ($dryRun ? ' (dry-run)' : ''));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return array{changed: bool, value: mixed}
     */
    private function renameUrlItem($s3, string $s3BaseUrl, string $url, string $baseSlug, int $timestamp, int $index, bool $onlyS3, bool $dryRun, bool $deleteOld, bool $isPoster = false): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['changed' => false, 'value' => $url];
        }

        $isOnOurS3 = str_starts_with($url, $s3BaseUrl);
        if ($onlyS3 && !$isOnOurS3) {
            return ['changed' => false, 'value' => $url];
        }
        if (!$isOnOurS3) {
            // Not our bucket URL; keep as-is.
            return ['changed' => false, 'value' => $url];
        }

        $oldKey = ltrim(substr($url, strlen($s3BaseUrl)), '/');
        if ($oldKey === '') {
            return ['changed' => false, 'value' => $url];
        }

        $oldExt = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $ext = $oldExt !== '' ? $oldExt : 'jpg';

        $safeSlug = Str::slug($baseSlug);
        if ($safeSlug === '') {
            $safeSlug = 'product';
        }

        $indexStr = str_pad((string) $index, 2, '0', STR_PAD_LEFT);

        if ($isPoster) {
            $newKey = 'products/posters/' . $safeSlug . '-' . $timestamp . '-' . $indexStr . '-poster.jpg';
        } else {
            $newKey = 'products/' . $safeSlug . '-' . $timestamp . '-' . $indexStr . '.' . $ext;
        }

        // If already matches destination key, no-op.
        if ($oldKey === $newKey) {
            return ['changed' => false, 'value' => $url];
        }

        $newUrl = $s3BaseUrl . $newKey;

        if ($dryRun) {
            return ['changed' => true, 'value' => $newUrl];
        }

        if (!$s3->exists($oldKey)) {
            // Old object missing -> keep original URL to avoid breaking media.
            Log::warning('Old S3 media key missing; skipping rename.', ['oldKey' => $oldKey]);
            return ['changed' => false, 'value' => $url];
        }

        // Copy then (optional) delete
        $copied = $s3->copy($oldKey, $newKey);
        if (!$copied) {
            throw new \RuntimeException("Failed to copy S3 object: {$oldKey} -> {$newKey}");
        }

        if ($deleteOld) {
            $s3->delete($oldKey);
        }

        return ['changed' => true, 'value' => $newUrl];
    }

    private function parseIdsOption($value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $parts = array_filter(array_map('trim', explode(',', $value)));
        $ids = [];
        foreach ($parts as $p) {
            if (is_numeric($p)) {
                $ids[] = (int) $p;
            }
        }

        return array_values(array_unique(array_filter($ids, fn ($i) => $i > 0)));
    }

    private function sleepMs(int $ms): void
    {
        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }
}

