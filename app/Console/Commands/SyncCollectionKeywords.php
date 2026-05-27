<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Services\CollectionAutoAssignService;
use App\Support\CollectionKeywordRules;
use Illuminate\Console\Command;

class SyncCollectionKeywords extends Command
{
    protected $signature = 'collections:sync-keywords
                            {--collection= : Collection ID to sync only}
                            {--product= : Product ID to sync against all eligible collections}';

    protected $description = 'Auto-add products to collections by keyword rules (no auto-remove)';

    public function handle(CollectionAutoAssignService $service): int
    {
        $productId = $this->option('product');

        if ($productId) {
            $product = \App\Models\Product::query()->find($productId);
            if (! $product) {
                $this->error("Product #{$productId} not found.");

                return self::FAILURE;
            }

            $stats = $service->syncProduct($product);
            $this->info("Product #{$productId}: attached to {$stats['attached']} collection(s).");

            return self::SUCCESS;
        }

        $collectionId = $this->option('collection');

        $query = Collection::query()->where('status', 'active')->whereNotNull('auto_rules');

        if ($collectionId) {
            $query->whereKey($collectionId);
        }

        $collections = $query->get()->filter(
            fn (Collection $c) => CollectionKeywordRules::isEnabled(
                is_array($c->auto_rules) ? $c->auto_rules : []
            )
        );

        if ($collections->isEmpty()) {
            $this->warn('No collections with keyword auto-assign enabled.');

            return self::SUCCESS;
        }

        $totalAttached = 0;
        $totalScanned = 0;

        foreach ($collections as $collection) {
            $stats = $service->syncCollection($collection);
            $totalAttached += $stats['attached'];
            $totalScanned += $stats['scanned'];
            $this->line("Collection #{$collection->id} ({$collection->name}): +{$stats['attached']} / {$stats['scanned']} products scanned");
        }

        $this->info("Done. Attached {$totalAttached} new link(s) across {$collections->count()} collection(s).");

        return self::SUCCESS;
    }
}
