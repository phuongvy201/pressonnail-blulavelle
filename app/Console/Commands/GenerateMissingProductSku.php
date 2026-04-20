<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateMissingProductSku extends Command
{
    protected $signature = 'products:generate-missing-sku
        {--chunk=200 : Chunk size for processing}
        {--dry-run : Preview only, do not update database}';

    protected $description = 'Generate unique SKU for products that are missing SKU';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $query = Product::query()
            ->select(['id', 'sku'])
            ->where(function ($q) {
                $q->whereNull('sku')->orWhere('sku', '');
            });

        $total = (clone $query)->count();
        $this->info("Products without SKU: {$total}");

        if ($total === 0) {
            return Command::SUCCESS;
        }

        $processed = 0;
        $updated = 0;

        $query->orderBy('id')->chunkById($chunkSize, function ($products) use (&$processed, &$updated, $dryRun) {
            foreach ($products as $product) {
                $processed++;
                $newSku = $this->generateUniqueSku();

                if ($dryRun) {
                    $this->line("[DRY-RUN] Product #{$product->id} => {$newSku}");
                    continue;
                }

                $product->sku = $newSku;
                $product->save();
                $updated++;

                $this->line("Updated product #{$product->id} => {$newSku}");
            }
        });

        $this->newLine();
        $this->info(
            $dryRun
                ? "Done (dry-run). processed={$processed}, would_update={$processed}"
                : "Done. processed={$processed}, updated={$updated}"
        );

        return Command::SUCCESS;
    }

    protected function generateUniqueSku(): string
    {
        do {
            $sku = 'PRD-' . strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}

