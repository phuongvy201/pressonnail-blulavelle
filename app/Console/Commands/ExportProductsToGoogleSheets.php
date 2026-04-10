<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\GoogleSheetsProductExportService;
use Illuminate\Console\Command;

class ExportProductsToGoogleSheets extends Command
{
    protected $signature = 'products:export-google-sheets
        {--append : Chỉ append các dòng dữ liệu (không xóa sheet, không ghi header)}
        {--status= : Lọc theo status: active|inactive|draft}
        {--ids= : Danh sách ID cách nhau bởi dấu phẩy (ví dụ 1,2,3)}
        {--chunk=500 : Số bản ghi mỗi lần đọc từ DB (giảm tải bộ nhớ)}';

    protected $description = 'Xuất sản phẩm lên Google Sheet (cấu hình qua GOOGLE_SHEETS_* trong .env)';

    public function handle(GoogleSheetsProductExportService $sheets): int
    {
        if (! $sheets->isConfigured()) {
            $this->error('Chưa cấu hình Google Sheets. Kiểm tra GOOGLE_SHEETS_SPREADSHEET_ID, GOOGLE_SHEETS_CREDENTIALS_PATH và file JSON service account.');

            return self::FAILURE;
        }

        $append = (bool) $this->option('append');
        $status = $this->option('status');
        $idsRaw = $this->option('ids');
        $chunk = max(1, (int) $this->option('chunk'));

        $query = Product::query()->orderBy('id');

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $ids = $this->parseIds($idsRaw);
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->warn('Không có sản phẩm nào khớp điều kiện.');

            return self::SUCCESS;
        }

        if ($append) {
            $this->warn('Chế độ --append: dữ liệu sẽ được nối vào cuối sheet, không xóa nội dung cũ và không ghi dòng header.');
        } else {
            $this->info('Chế độ mặc định: xóa vùng A:ZZ trên tab cấu hình, ghi lại header + toàn bộ sản phẩm.');
        }

        $this->info('APP_URL hiện tại: '.config('app.url').' (dùng cho cột link sản phẩm).');

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $collected = collect();
        $query->chunkById($chunk, function ($products) use (&$collected, $bar) {
            foreach ($products as $product) {
                $collected->push($product);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        try {
            $exported = $sheets->exportAllProducts(
                $collected,
                clearAndReplace: ! $append,
                appendRowsOnly: $append
            );

            $this->info("Đã xuất {$exported} sản phẩm lên Google Sheet.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Lỗi khi ghi Google Sheet: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return list<int>
     */
    private function parseIds(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [];
        }

        $ids = [];
        foreach ($parts as $part) {
            if (ctype_digit($part)) {
                $ids[] = (int) $part;
            }
        }

        return array_values(array_unique($ids));
    }
}
