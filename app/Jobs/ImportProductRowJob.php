<?php

namespace App\Jobs;

use App\Imports\ProductsImport;
use App\Models\User;
use App\Services\ProductImportProgress;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportProductRowJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public int $tries = 2;

    /**
     * @param  array<string, string>  $row
     */
    public function __construct(
        public string $progressKey,
        public array $row,
        public int $userId,
    ) {
        $this->timeout = (int) config('product_import.row_job_timeout', 900);
        $this->onQueue((string) config('product_import.queue', 'imports'));
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $user = User::query()->find($this->userId);
        if ($user === null) {
            ProductImportProgress::recordRow($this->progressKey, false, 'User import không tồn tại.');

            return;
        }

        $import = new ProductsImport($user, $this->progressKey, true);

        try {
            $import->processRow($this->row);
        } catch (\Throwable $e) {
            Log::error('ImportProductRowJob failed', [
                'progress_key' => $this->progressKey,
                'product_name' => $this->row['product_name'] ?? null,
                'error' => $e->getMessage(),
            ]);

            ProductImportProgress::recordRow(
                $this->progressKey,
                false,
                ($this->row['product_name'] ?? 'Row').': '.$e->getMessage()
            );
        }
    }
}
