<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductMediaWebpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateProductMediaWebp extends Command
{
    protected $signature = 'products:generate-media-webp
        {--ids= : Comma-separated product IDs}
        {--limit= : Max products to process}
        {--only-s3 : Chỉ xử lý URL trên S3 bucket đã cấu hình (base URL như rename command)}
        {--dry-run : Không ghi S3 / DB}
        {--force : Tạo lại file WebP dù đã tồn tại}
        {--sleep=0 : Nghỉ N ms giữa mỗi sản phẩm}';

    protected $description = 'Tạo file WebP song song trên S3 (cùng thư mục với ảnh gốc), cập nhật products.media (url + webp)';

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('PHP GD không hỗ trợ imagewebp. Bật gd + webp trong php.ini.');

            return Command::FAILURE;
        }

        $ids = $this->parseIdsOption($this->option('ids'));
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $onlyS3 = (bool) $this->option('only-s3');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $sleepMs = max(0, (int) $this->option('sleep'));

        $s3 = Storage::disk('s3');

        $q = Product::query()->select(['id', 'media'])->orderBy('id');
        if (! empty($ids)) {
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

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($q->cursor() as $product) {
            try {
                $media = $product->media;
                if (is_string($media)) {
                    $decoded = json_decode($media, true);
                    $media = is_array($decoded) ? $decoded : [];
                }
                if (! is_array($media) || $media === []) {
                    $skipped++;
                    $this->line("Skip #{$product->id} (empty media)");
                    $this->sleepMs($sleepMs);

                    continue;
                }

                $newMedia = [];
                $touched = false;
                $skipHints = [];

                foreach ($media as $item) {
                    if (is_string($item)) {
                        $res = $this->processStringItem($s3, $item, $onlyS3, $dryRun, $force);
                        $newMedia[] = $res['value'];
                        $touched = $touched || $res['changed'];
                        if (! $res['changed'] && ! empty($res['reason'])) {
                            $skipHints[] = 'string: '.$res['reason'];
                        }

                        continue;
                    }

                    if (is_array($item) && (($item['type'] ?? null) === 'video')) {
                        $newMedia[] = $item;

                        continue;
                    }

                    if (is_array($item)) {
                        $res = $this->processArrayImageItem($s3, $item, $onlyS3, $dryRun, $force);
                        $newMedia[] = $res['value'];
                        $touched = $touched || $res['changed'];
                        if (! $res['changed'] && ! empty($res['reason'])) {
                            $skipHints[] = 'image[]: '.$res['reason'];
                        }

                        continue;
                    }

                    $newMedia[] = $item;
                }

                if (! $touched) {
                    $skipped++;
                    if ($skipHints === [] && $media !== []) {
                        $onlyVideos = true;
                        foreach ($media as $m) {
                            if (! (is_array($m) && (($m['type'] ?? null) === 'video'))) {
                                $onlyVideos = false;
                                break;
                            }
                        }
                        if ($onlyVideos) {
                            $skipHints[] = 'only_videos';
                        }
                    }
                    $hint = $skipHints !== [] ? ' — '.implode('; ', array_unique($skipHints)) : '';
                    $this->line("Skip #{$product->id} (no webp changes)".$hint);
                    $this->sleepMs($sleepMs);

                    continue;
                }

                if (! $dryRun) {
                    $product->media = array_values($newMedia);
                    $product->save();
                }

                $this->info("OK #{$product->id}".($dryRun ? ' (dry-run)' : ''));
                $updated++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("#{$product->id}: ".$e->getMessage());
                Log::error('products:generate-media-webp failed.', [
                    'product_id' => $product->id,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            }

            $this->sleepMs($sleepMs);
        }

        $this->newLine();
        $this->info("Done. updated={$updated}, skipped={$skipped}, failed={$failed}".($dryRun ? ' (dry-run)' : ''));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return array{changed: bool, value: mixed, reason?: string}
     */
    private function processStringItem($s3, string $url, bool $onlyS3, bool $dryRun, bool $force): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['changed' => false, 'value' => $url, 'reason' => 'empty_url'];
        }

        if ($onlyS3 && ProductMediaWebpService::resolvePublicUrlToKey($url) === null) {
            return ['changed' => false, 'value' => $url, 'reason' => 'only_s3_mismatch'];
        }

        $result = ProductMediaWebpService::ensureWebpOnS3($s3, $url, $dryRun, $force);
        if ($result['webp_url'] === null) {
            return ['changed' => false, 'value' => $url, 'reason' => (string) ($result['error'] ?? 'unknown')];
        }

        return ['changed' => true, 'value' => ['url' => $url, 'webp' => $result['webp_url']]];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{changed: bool, value: array<string, mixed>, reason?: string}
     */
    private function processArrayImageItem($s3, array $item, bool $onlyS3, bool $dryRun, bool $force): array
    {
        $u = isset($item['url']) ? trim((string) $item['url']) : '';
        if ($u === '') {
            return ['changed' => false, 'value' => $item, 'reason' => 'empty_url'];
        }

        if ($onlyS3 && ProductMediaWebpService::resolvePublicUrlToKey($u) === null) {
            return ['changed' => false, 'value' => $item, 'reason' => 'only_s3_mismatch'];
        }

        $existingWebp = isset($item['webp']) ? trim((string) $item['webp']) : '';
        if ($existingWebp !== '' && ! $force) {
            return ['changed' => false, 'value' => $item, 'reason' => 'already_has_webp'];
        }

        $result = ProductMediaWebpService::ensureWebpOnS3($s3, $u, $dryRun, $force);
        if ($result['webp_url'] === null) {
            return ['changed' => false, 'value' => $item, 'reason' => (string) ($result['error'] ?? 'unknown')];
        }

        $newItem = $item;
        $newItem['webp'] = $result['webp_url'];

        return ['changed' => true, 'value' => $newItem];
    }

    private function parseIdsOption($value): array
    {
        if (! is_string($value) || trim($value) === '') {
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
