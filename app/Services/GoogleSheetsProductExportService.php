<?php

namespace App\Services;

use App\Models\Product;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GoogleSheetsProductExportService
{
    public function isConfigured(): bool
    {
        $spreadsheetId = trim((string) config('services.google.sheets.spreadsheet_id'));
        $credentialsPath = $this->resolveCredentialsPath(
            (string) config('services.google.sheets.credentials_path')
        );

        return $spreadsheetId !== '' && $credentialsPath !== '' && is_file($credentialsPath);
    }

    /**
     * Ghi đè toàn bộ dữ liệu sheet (xóa A:ZZ rồi ghi header + các dòng) hoặc chỉ append các dòng dữ liệu.
     *
     * @param  iterable<Product>|Collection<int, Product>  $products
     * @return int Số sản phẩm đã ghi (không tính dòng header khi full replace)
     */
    public function exportAllProducts(iterable $products, bool $clearAndReplace = true, bool $appendRowsOnly = false): int
    {
        $list = $products instanceof Collection ? $products : collect($products);

        if ($list->isEmpty()) {
            return 0;
        }

        $ctx = $this->createAuthorizedSheetsClient(null);
        if ($ctx === null) {
            throw new \RuntimeException(
                'Google Sheets chưa cấu hình: đặt GOOGLE_SHEETS_SPREADSHEET_ID và GOOGLE_SHEETS_CREDENTIALS_PATH (file JSON hợp lệ).'
            );
        }

        [$sheets, $spreadsheetId, $resolvedSheetName, $credentialsPath] = $ctx;

        $maxImages = 0;
        foreach ($list as $p) {
            $maxImages = max($maxImages, count($this->extractProductImageUrls($p)));
        }

        $header = ['id', 'name', 'url'];
        for ($i = 1; $i <= $maxImages; $i++) {
            $header[] = 'image_'.$i;
        }

        $values = [];
        if (! $appendRowsOnly) {
            $values[] = $header;
        }

        foreach ($list as $product) {
            $urls = $this->extractProductImageUrls($product);
            while (count($urls) < $maxImages) {
                $urls[] = '';
            }
            $values[] = [
                $product->id,
                (string) $product->name,
                $this->productShowUrl($product),
                ...$urls,
            ];
        }

        $escaped = str_replace("'", "''", trim($resolvedSheetName));

        Log::info('Google Sheets full export started.', [
            'product_count' => $list->count(),
            'clear_and_replace' => $clearAndReplace,
            'append_rows_only' => $appendRowsOnly,
            'max_image_columns' => $maxImages,
            'spreadsheet_id' => $spreadsheetId,
            'sheet_name' => $resolvedSheetName,
        ]);

        try {
            if ($clearAndReplace && ! $appendRowsOnly) {
                $sheets->spreadsheets_values->clear(
                    $spreadsheetId,
                    sprintf("'%s'!A:ZZ", $escaped),
                    new ClearValuesRequest()
                );
            }

            if ($appendRowsOnly) {
                $appendRange = sprintf("'%s'!A:A", $escaped);
                $body = new ValueRange(['values' => $values]);
                $sheets->spreadsheets_values->append(
                    $spreadsheetId,
                    $appendRange,
                    $body,
                    [
                        'valueInputOption' => 'RAW',
                        'insertDataOption' => 'INSERT_ROWS',
                    ]
                );
            } else {
                $range = sprintf("'%s'!A1", $escaped);
                $body = new ValueRange(['values' => $values]);
                $sheets->spreadsheets_values->update(
                    $spreadsheetId,
                    $range,
                    $body,
                    ['valueInputOption' => 'RAW']
                );
            }

            Log::info('Google Sheets full export success.', [
                'product_count' => $list->count(),
                'rows_written' => count($values),
            ]);

            return $list->count();
        } catch (\Throwable $e) {
            Log::error('Google Sheets full export failed.', [
                'spreadsheet_id' => $spreadsheetId,
                'sheet_name' => $resolvedSheetName,
                'credentials_path' => $credentialsPath,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Nối thêm nhiều dòng sản phẩm trong một lần gọi API (không ghi header).
     * Dùng sau import hàng loạt. Không ném exception nếu lỗi — chỉ ghi log.
     *
     * @param  iterable<Product>|Collection<int, Product>  $products
     */
    public function appendProductRows(iterable $products): int
    {
        $list = $products instanceof Collection ? $products : collect($products);
        if ($list->isEmpty()) {
            return 0;
        }

        if (! $this->isConfigured()) {
            return 0;
        }

        $ctx = $this->createAuthorizedSheetsClient(null);
        if ($ctx === null) {
            return 0;
        }

        [$sheets, $spreadsheetId, $resolvedSheetName, $credentialsPath] = $ctx;

        $maxImages = 0;
        foreach ($list as $p) {
            $maxImages = max($maxImages, count($this->extractProductImageUrls($p)));
        }

        $values = [];
        foreach ($list as $product) {
            $urls = $this->extractProductImageUrls($product);
            while (count($urls) < $maxImages) {
                $urls[] = '';
            }
            $values[] = [
                $product->id,
                (string) $product->name,
                $this->productShowUrl($product),
                ...$urls,
            ];
        }

        $escaped = str_replace("'", "''", trim($resolvedSheetName));
        $appendRange = sprintf("'%s'!A:A", $escaped);
        $body = new ValueRange(['values' => $values]);

        try {
            $sheets->spreadsheets_values->append(
                $spreadsheetId,
                $appendRange,
                $body,
                [
                    'valueInputOption' => 'RAW',
                    'insertDataOption' => 'INSERT_ROWS',
                ]
            );

            Log::info('Google Sheets batch append success.', [
                'rows' => $list->count(),
                'spreadsheet_id' => $spreadsheetId,
                'sheet_name' => $resolvedSheetName,
            ]);

            return $list->count();
        } catch (\Throwable $e) {
            Log::error('Google Sheets batch append failed.', [
                'rows' => $list->count(),
                'spreadsheet_id' => $spreadsheetId,
                'credentials_path' => $credentialsPath,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    public function exportNewProduct(Product $product): void
    {
        $ctx = $this->createAuthorizedSheetsClient($product->id);
        if ($ctx === null) {
            return;
        }

        [$sheets, $spreadsheetId, $resolvedSheetName, $credentialsPath] = $ctx;
        $row = $this->buildProductRow($product);

        Log::info('Google Sheets export started.', [
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'product_url' => $row[2] ?? null,
            'spreadsheet_id' => $spreadsheetId,
            'sheet_name' => $resolvedSheetName,
            'credentials_path' => $credentialsPath,
            'images_count' => count($row) - 3,
            'column_count' => count($row),
        ]);

        try {
            $appendRange = $this->buildA1RangeForColumnA($resolvedSheetName);
            $body = new ValueRange([
                'values' => [$row],
            ]);

            $result = $sheets->spreadsheets_values->append(
                $spreadsheetId,
                $appendRange,
                $body,
                [
                    'valueInputOption' => 'RAW',
                    'insertDataOption' => 'INSERT_ROWS',
                ]
            );

            Log::info('Google Sheets export success.', [
                'product_id' => $product->id,
                'resolved_sheet_name' => $resolvedSheetName,
                'append_range' => $appendRange,
                'updated_range' => $result->getUpdates()?->getUpdatedRange(),
                'updated_rows' => $result->getUpdates()?->getUpdatedRows(),
                'updated_columns' => $result->getUpdates()?->getUpdatedColumns(),
                'updated_cells' => $result->getUpdates()?->getUpdatedCells(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Google Sheets export failed.', [
                'product_id' => $product->id,
                'spreadsheet_id' => $spreadsheetId,
                'sheet_name' => $resolvedSheetName,
                'credentials_path' => $credentialsPath,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * @return array{0: Sheets, 1: string, 2: string, 3: string}|null
     */
    private function createAuthorizedSheetsClient(?int $logProductId): ?array
    {
        $spreadsheetId = trim((string) config('services.google.sheets.spreadsheet_id'));
        $sheetName = (string) config('services.google.sheets.sheet_name', 'Sheet1');
        $credentialsPath = $this->resolveCredentialsPath(
            (string) config('services.google.sheets.credentials_path')
        );

        if ($spreadsheetId === '' || $credentialsPath === '') {
            Log::warning('Skip Google Sheets export: missing spreadsheet or credentials config.', [
                'product_id' => $logProductId,
                'spreadsheet_id_present' => $spreadsheetId !== '',
                'credentials_path_present' => $credentialsPath !== '',
            ]);

            return null;
        }

        if (! is_file($credentialsPath)) {
            Log::error('Google Sheets export skipped: credentials file not found.', [
                'product_id' => $logProductId,
                'credentials_path' => $credentialsPath,
            ]);

            return null;
        }

        $client = new Client();
        $client->setApplicationName('PressOnNail Product Export');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig($credentialsPath);

        $sheets = new Sheets($client);
        $resolvedSheetName = $this->resolveExistingSheetTitle($sheets, $spreadsheetId, $sheetName);

        return [$sheets, $spreadsheetId, $resolvedSheetName, $credentialsPath];
    }

    /**
     * @return list<int|string>
     */
    private function buildProductRow(Product $product): array
    {
        $productUrl = $this->productShowUrl($product);
        $imageUrls = $this->extractProductImageUrls($product);

        return [
            $product->id,
            (string) $product->name,
            $productUrl,
            ...$imageUrls,
        ];
    }

    /**
     * Route products.show yêu cầu {slug}; một số bản ghi cũ có slug rỗng/null.
     */
    private function productShowUrl(Product $product): string
    {
        $slug = trim((string) ($product->slug ?? ''));
        if ($slug === '') {
            $slug = (string) $product->id;
        }

        return route('products.show', ['slug' => $slug]);
    }

    private function resolveCredentialsPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        // Windows absolute path: C:\... or C:/...
        $isWindowsAbsolute = (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
        // Unix absolute path: /...
        $isUnixAbsolute = str_starts_with($path, '/');

        if ($isWindowsAbsolute || $isUnixAbsolute) {
            return $path;
        }

        // Relative path -> resolve from project root.
        return base_path($path);
    }

    private function buildA1RangeForColumnA(string $sheetName): string
    {
        // Escape single quote according to A1 notation: ' -> ''
        $escapedSheetName = str_replace("'", "''", trim($sheetName));

        return sprintf("'%s'!A:A", $escapedSheetName);
    }

    private function resolveExistingSheetTitle(Sheets $sheets, string $spreadsheetId, string $configuredSheetName): string
    {
        $configuredSheetName = trim($configuredSheetName);
        $spreadsheet = $sheets->spreadsheets->get($spreadsheetId, [
            'fields' => 'sheets(properties(title))',
        ]);

        $sheetTitles = [];
        foreach ((array) $spreadsheet->getSheets() as $sheet) {
            $title = (string) ($sheet->getProperties()?->getTitle() ?? '');
            if ($title !== '') {
                $sheetTitles[] = $title;
            }
        }

        if ($configuredSheetName !== '' && in_array($configuredSheetName, $sheetTitles, true)) {
            return $configuredSheetName;
        }

        if (! empty($sheetTitles)) {
            if ($configuredSheetName !== '') {
                Log::warning('Configured Google Sheet name not found. Falling back to first sheet.', [
                    'configured_sheet_name' => $configuredSheetName,
                    'fallback_sheet_name' => $sheetTitles[0],
                    'available_sheets' => $sheetTitles,
                ]);
            }

            return $sheetTitles[0];
        }

        // Defensive fallback if spreadsheet has no visible sheets (very rare).
        return $configuredSheetName !== '' ? $configuredSheetName : 'Sheet1';
    }

    /**
     * @return array<int, string>
     */
    private function extractProductImageUrls(Product $product): array
    {
        $mediaList = method_exists($product, 'getEffectiveMedia')
            ? $product->getEffectiveMedia()
            : (is_array($product->media) ? $product->media : []);

        $imageUrls = [];

        foreach ($mediaList as $mediaItem) {
            if (is_string($mediaItem) && $this->looksLikeImageUrl($mediaItem)) {
                $imageUrls[] = $mediaItem;
                continue;
            }

            if (! is_array($mediaItem)) {
                continue;
            }

            $type = strtolower((string) ($mediaItem['type'] ?? ''));

            if ($type === 'image') {
                $url = (string) ($mediaItem['webp'] ?? $mediaItem['url'] ?? '');
                if ($url !== '') {
                    $imageUrls[] = $url;
                }
                continue;
            }

            // Video item: use poster image if available.
            if ($type === 'video') {
                $poster = (string) ($mediaItem['poster'] ?? '');
                if ($poster !== '') {
                    $imageUrls[] = $poster;
                }
            }
        }

        return array_values(array_unique(array_filter($imageUrls)));
    }

    private function looksLikeImageUrl(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return (bool) preg_match('/\.(jpe?g|png|gif|webp|avif)$/i', $path);
    }
}
