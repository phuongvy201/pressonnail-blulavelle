<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ProductImportProgress
{
    public static function init(string $progressKey, int $totalRows, int $userId, string $csvPath, bool $deleteCsv): void
    {
        $ttl = (int) config('product_import.progress_ttl_seconds', 86400);

        Cache::put($progressKey, [
            'processed' => 0,
            'total' => max(1, $totalRows),
            'success' => 0,
            'errors' => 0,
            'percentage' => 0,
            'status' => 'queued',
            'updated_at' => now()->toIso8601String(),
        ], $ttl);

        Cache::put(self::metaKey($progressKey), [
            'user_id' => $userId,
            'csv_path' => $csvPath,
            'delete_csv' => $deleteCsv,
        ], $ttl);

        Cache::put(self::variantQueueKey($progressKey), [], $ttl);
        Cache::put(self::slugsKey($progressKey), [], $ttl);
        Cache::put(self::errorMessagesKey($progressKey), [], $ttl);
    }

    /**
     * @param  array{slug: string, template_id: int, template_variants: list<array{variant_name: string}>}|null  $variantMeta
     */
    public static function recordRow(
        string $progressKey,
        bool $success,
        ?string $error = null,
        ?array $variantMeta = null,
        ?string $slug = null
    ): void {
        $ttl = (int) config('product_import.progress_ttl_seconds', 86400);

        Cache::lock(self::lockKey($progressKey), 15)->block(10, function () use (
            $progressKey,
            $success,
            $error,
            $variantMeta,
            $slug,
            $ttl
        ) {
            $progress = Cache::get($progressKey, []);
            $progress['processed'] = (int) ($progress['processed'] ?? 0) + 1;
            $total = max(1, (int) ($progress['total'] ?? 1));

            if ($success) {
                $progress['success'] = (int) ($progress['success'] ?? 0) + 1;
            } else {
                $progress['errors'] = (int) ($progress['errors'] ?? 0) + 1;
                if ($error !== null && $error !== '') {
                    $messages = Cache::get(self::errorMessagesKey($progressKey), []);
                    if (count($messages) < 100) {
                        $messages[] = $error;
                        Cache::put(self::errorMessagesKey($progressKey), $messages, $ttl);
                    }
                }
            }

            $progress['percentage'] = min(100, round(($progress['processed'] / $total) * 100, 2));
            $progress['status'] = 'processing';
            $progress['updated_at'] = now()->toIso8601String();
            Cache::put($progressKey, $progress, $ttl);

            if ($success && $slug !== null && $slug !== '') {
                $slugs = Cache::get(self::slugsKey($progressKey), []);
                $slugs[] = $slug;
                Cache::put(self::slugsKey($progressKey), $slugs, $ttl);
            }

            if ($success && $variantMeta !== null) {
                $queue = Cache::get(self::variantQueueKey($progressKey), []);
                $queue[] = [
                    'slug' => (string) ($variantMeta['slug'] ?? $slug ?? ''),
                    'template_id' => (int) ($variantMeta['template_id'] ?? 0),
                    'template_variants' => array_values(array_map(
                        fn ($v) => ['variant_name' => (string) ($v['variant_name'] ?? '')],
                        $variantMeta['template_variants'] ?? []
                    )),
                ];
                Cache::put(self::variantQueueKey($progressKey), $queue, $ttl);
            }
        });
    }

    public static function markProcessing(string $progressKey): void
    {
        $ttl = (int) config('product_import.progress_ttl_seconds', 86400);
        $progress = Cache::get($progressKey, []);
        $progress['status'] = 'processing';
        $progress['updated_at'] = now()->toIso8601String();
        Cache::put($progressKey, $progress, $ttl);
    }

    public static function markCompleted(string $progressKey): void
    {
        $ttl = (int) config('product_import.progress_ttl_seconds', 86400);
        $progress = Cache::get($progressKey, []);
        $progress['status'] = 'completed';
        $progress['percentage'] = 100;
        $progress['updated_at'] = now()->toIso8601String();
        Cache::put($progressKey, $progress, $ttl);
    }

    public static function markFailed(string $progressKey, string $error): void
    {
        $ttl = (int) config('product_import.progress_ttl_seconds', 86400);
        $progress = Cache::get($progressKey, []);
        $progress['status'] = 'failed';
        $progress['error'] = $error;
        $progress['updated_at'] = now()->toIso8601String();
        Cache::put($progressKey, $progress, $ttl);
    }

    /** @return list<array{slug: string, template_id: int, template_variants: list<array{variant_name: string}>}> */
    public static function getVariantQueue(string $progressKey): array
    {
        return Cache::get(self::variantQueueKey($progressKey), []);
    }

    /** @return list<string> */
    public static function getSlugs(string $progressKey): array
    {
        return Cache::get(self::slugsKey($progressKey), []);
    }

    /** @return array<string, mixed>|null */
    public static function getMeta(string $progressKey): ?array
    {
        return Cache::get(self::metaKey($progressKey));
    }

    private static function metaKey(string $progressKey): string
    {
        return "{$progressKey}:meta";
    }

    private static function variantQueueKey(string $progressKey): string
    {
        return "{$progressKey}:variant_queue";
    }

    private static function slugsKey(string $progressKey): string
    {
        return "{$progressKey}:slugs";
    }

    private static function errorMessagesKey(string $progressKey): string
    {
        return "{$progressKey}:error_messages";
    }

    private static function lockKey(string $progressKey): string
    {
        return "{$progressKey}:lock";
    }
}
