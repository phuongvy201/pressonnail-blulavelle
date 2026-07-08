<?php

namespace App\Jobs;

use App\Jobs\ImportProductRowJob;
use App\Services\ProductImportCsvReader;
use App\Services\ProductImportProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class RunProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public string $csvPath,
        public string $progressKey,
        public int $totalRows,
        public int $userId,
        public bool $deleteCsvAfterUse = true,
        public string $sourceExtension = 'csv',
    ) {
        $this->onQueue((string) config('product_import.queue', 'imports'));
    }

    public function handle(ProductImportCsvReader $csvReader, ProductImportSpreadsheetConverter $converter): void
    {
        $workingPath = $this->csvPath;
        $converted = false;

        if (strtolower($this->sourceExtension) !== 'csv') {
            $workingPath = $converter->convertToCsv($this->csvPath, $this->sourceExtension);
            $converted = true;
        }

        ProductImportProgress::init(
            $this->progressKey,
            $this->totalRows,
            $this->userId,
            $workingPath,
            $this->deleteCsvAfterUse || $converted
        );

        if ($converted && $this->deleteCsvAfterUse && file_exists($this->csvPath)) {
            @unlink($this->csvPath);
        }

        $jobs = [];

        try {
            foreach ($csvReader->rows($workingPath) as $row) {
                $jobs[] = new ImportProductRowJob($this->progressKey, $row, $this->userId);
            }
        } catch (\Throwable $e) {
            Log::error('RunProductImportJob: cannot read CSV', [
                'path' => $this->csvPath,
                'error' => $e->getMessage(),
            ]);
            ProductImportProgress::markFailed($this->progressKey, $e->getMessage());
            $this->cleanupCsv();

            return;
        }

        if ($jobs === []) {
            Log::warning('RunProductImportJob: no data rows', ['path' => $this->csvPath]);
            FinalizeProductImportJob::dispatch($this->progressKey);

            return;
        }

        ProductImportProgress::markProcessing($this->progressKey);

        $progressKey = $this->progressKey;

        Bus::batch($jobs)
            ->name('product-import:'.$progressKey)
            ->finally(function () use ($progressKey) {
                FinalizeProductImportJob::dispatch($progressKey);
            })
            ->onQueue((string) config('product_import.queue', 'imports'))
            ->dispatch();
    }

    public function failed(\Throwable $exception): void
    {
        ProductImportProgress::markFailed($this->progressKey, $exception->getMessage());
        $this->cleanupCsv();
    }

    private function cleanupCsv(): void
    {
        if ($this->deleteCsvAfterUse && file_exists($this->csvPath)) {
            @unlink($this->csvPath);
        }
    }
}
