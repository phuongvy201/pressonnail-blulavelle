<?php

namespace App\Jobs;

use App\Imports\ProductsImport;
use App\Services\ProductImportProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FinalizeProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public string $progressKey,
    ) {
        $this->onQueue((string) config('product_import.queue', 'imports'));
    }

    public function handle(): void
    {
        try {
            ProductsImport::finalizeFromProgress($this->progressKey);
            ProductImportProgress::markCompleted($this->progressKey);
        } catch (\Throwable $e) {
            Log::error('FinalizeProductImportJob failed', [
                'progress_key' => $this->progressKey,
                'error' => $e->getMessage(),
            ]);
            ProductImportProgress::markFailed($this->progressKey, $e->getMessage());
        } finally {
            $meta = ProductImportProgress::getMeta($this->progressKey);
            if (
                is_array($meta)
                && ! empty($meta['delete_csv'])
                && ! empty($meta['csv_path'])
                && is_string($meta['csv_path'])
                && file_exists($meta['csv_path'])
            ) {
                @unlink($meta['csv_path']);
            }
        }
    }
}
