<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\OpenAIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateProductKeywords extends Command
{
    protected $signature = 'products:generate-keywords
        {--ids= : Comma-separated product IDs (e.g. 1,2,3)}
        {--status= : Filter by status (active|inactive|draft)}
        {--limit= : Max number of products to process}
        {--chunk=50 : Chunk size when scanning products}
        {--only-missing : Only products with empty meta_keywords}
        {--force : Overwrite existing meta_keywords}
        {--dry-run : Do not write to database}
        {--sleep=0 : Sleep N milliseconds between requests}';

    protected $description = 'Generate SEO meta_keywords for existing products using OpenAI';

    public function handle(OpenAIService $ai): int
    {
        $ids = $this->parseIdsOption($this->option('ids'));
        $status = $this->option('status');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $chunk = max(1, (int) $this->option('chunk'));
        $onlyMissing = (bool) $this->option('only-missing');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $sleepMs = max(0, (int) $this->option('sleep'));

        if ($onlyMissing && $force) {
            $this->warn('Both --only-missing and --force were provided; --force will effectively overwrite when reached.');
        }

        $q = Product::query()->select(['id', 'name', 'status', 'meta_keywords']);

        if (!empty($ids)) {
            $q->whereIn('id', $ids);
        }

        if (is_string($status) && $status !== '') {
            $q->where('status', $status);
        }

        if ($onlyMissing) {
            $q->where(function ($w) {
                $w->whereNull('meta_keywords')->orWhere('meta_keywords', '');
            });
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

        $q->orderBy('id')->chunk($chunk, function ($products) use (
            $ai,
            $force,
            $dryRun,
            $sleepMs,
            &$processed,
            &$updated,
            &$skipped,
            &$failed
        ) {
            foreach ($products as $product) {
                $processed++;

                $existing = is_string($product->meta_keywords) ? trim($product->meta_keywords) : '';
                if ($existing !== '' && !$force) {
                    $skipped++;
                    $this->line("Skip #{$product->id} (has keywords)");
                    continue;
                }

                $name = is_string($product->name) ? trim($product->name) : '';
                if ($name === '') {
                    $skipped++;
                    $this->warn("Skip #{$product->id} (empty name)");
                    continue;
                }

                try {
                    $keywords = $ai->extractKeywords($name);
                    $keywordsString = implode(', ', $keywords);

                    if ($keywordsString === '') {
                        $failed++;
                        $this->error("Fail #{$product->id} (empty keywords)");
                        Log::warning('AI returned empty keywords for product.', ['product_id' => $product->id]);
                        continue;
                    }

                    $this->info("OK #{$product->id}: {$keywordsString}");

                    if (!$dryRun) {
                        $product->meta_keywords = $keywordsString;
                        $product->save();
                    }

                    $updated++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("Error #{$product->id}: " . $e->getMessage());
                    Log::error('Failed generating keywords for product.', [
                        'product_id' => $product->id,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);
                }

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }
        });

        $this->newLine();
        $this->info("Done. processed={$processed}, updated={$updated}, skipped={$skipped}, failed={$failed}" . ($dryRun ? ' (dry-run)' : ''));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
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
}

