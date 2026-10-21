<?php

namespace App\Console\Commands;

use App\Jobs\GenerateProductEmbedding;
use App\Models\Product;
use Illuminate\Console\Command;

class GenerateProductEmbeddings extends Command
{
    protected $signature = 'products:embed-search {--sync : Generate inline instead of queueing jobs}';

    protected $description = 'Generate semantic-search embeddings for all displayable products';

    public function handle(): int
    {
        $sync = (bool) $this->option('sync');
        $count = 0;

        Product::query()
            ->availableForDisplay()
            ->select('id')
            ->chunkById(100, function ($products) use ($sync, &$count) {
                foreach ($products as $product) {
                    if ($sync) {
                        GenerateProductEmbedding::dispatchSync((int) $product->id);
                    } else {
                        GenerateProductEmbedding::dispatch((int) $product->id);
                    }
                    $count++;
                }
            });

        $this->info(($sync ? 'Generated' : 'Queued')." embeddings for {$count} products.");

        return self::SUCCESS;
    }
}
