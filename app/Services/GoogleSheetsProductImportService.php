<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleSheetsProductImportService
{
    /** @var list<string> */
    private const CSV_COLUMNS = [
        'template_id',
        'sku',
        'product_name',
        'price',
        'description',
        'meta_keywords',
        'quantity',
        'status',
        'image_1',
        'image_2',
        'image_3',
        'image_4',
        'image_5',
        'image_6',
        'image_7',
        'image_8',
        'video_url',
        'drive_folder',
    ];

    public function isConfigured(): bool
    {
        $credentialsPath = $this->resolveCredentialsPath(
            (string) config('services.google.sheets.credentials_path')
        );

        return $credentialsPath !== '' && is_file($credentialsPath);
    }

    /**
     * @return array{id: string, gid: int|null}
     */
    public static function parseSpreadsheetInput(string $input): array
    {
        $input = trim($input);
        $gid = null;

        if (preg_match('~#gid=(\d+)~', $input, $matches)) {
            $gid = (int) $matches[1];
        }

        return [
            'id' => self::parseSpreadsheetId($input),
            'gid' => $gid,
        ];
    }

    public static function parseSpreadsheetId(string $input): string
    {
        $input = trim($input);

        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('#^[a-zA-Z0-9-_]{20,}$#', $input)) {
            return $input;
        }

        throw new RuntimeException('Spreadsheet ID hoặc URL Google Sheet không hợp lệ.');
    }

    /**
     * @param  array{default_quantity?: int, default_status?: string}  $options
     * @return array{path: string, total_rows: int, sheet_title: string}
     */
    public function buildImportCsv(
        string $spreadsheetInput,
        int $templateId,
        ?string $sheetName,
        int $headerRow = 1,
        int $rowFrom = 2,
        int $rowTo = 100,
        array $options = []
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Google Sheets chưa cấu hình. Đặt GOOGLE_SHEETS_CREDENTIALS_PATH trong .env và share sheet cho service account.'
            );
        }

        $parsed = self::parseSpreadsheetInput($spreadsheetInput);
        $spreadsheetId = $parsed['id'];
        $headerRow = max(1, $headerRow);
        $rowFrom = max(1, $rowFrom);
        $rowTo = max($rowFrom, $rowTo);
        $defaultQuantity = max(0, (int) ($options['default_quantity'] ?? 0));
        $defaultStatus = in_array($options['default_status'] ?? 'active', ['active', 'draft', 'inactive'], true)
            ? ($options['default_status'] ?? 'active')
            : 'active';

        if ($rowTo - $rowFrom + 1 > config('product_import.max_rows', 2000)) {
            throw new RuntimeException('Mỗi lần import tối đa '.config('product_import.max_rows', 2000).' dòng dữ liệu.');
        }

        $ctx = $this->createAuthorizedSheetsClient();
        if ($ctx === null) {
            throw new RuntimeException('Không khởi tạo được Google Sheets client.');
        }

        [$sheets, $credentialsPath] = $ctx;
        $sheetTitle = $this->resolveSheetTitle(
            $sheets,
            $spreadsheetId,
            trim((string) $sheetName),
            $parsed['gid']
        );

        $headerCells = $this->fetchRow($sheets, $spreadsheetId, $sheetTitle, $headerRow);
        if ($headerCells === []) {
            throw new RuntimeException("Dòng header {$headerRow} trống hoặc không đọc được.");
        }

        $columnMap = $this->resolveColumnMap($headerCells);
        if ($columnMap['title'] === null && $columnMap['product'] === null) {
            throw new RuntimeException(
                'Sheet phải có cột TITLE hoặc PRODUCT. Kiểm tra dòng header.'
            );
        }

        $dataStart = max($rowFrom, $headerRow + 1);
        if ($rowTo < $dataStart) {
            throw new RuntimeException('Dòng kết thúc phải lớn hơn dòng header.');
        }

        $rawRows = $this->fetchRange($sheets, $spreadsheetId, $sheetTitle, $dataStart, $rowTo);
        $mappedRows = [];

        foreach ($rawRows as $cells) {
            $sheetRow = $this->mapSheetRow($cells, $columnMap);

            $productName = $sheetRow['title'] !== '' ? $sheetRow['title'] : $sheetRow['product'];
            if ($productName === '') {
                continue;
            }

            $description = $sheetRow['description'];
            if ($sheetRow['etsy_link'] !== '') {
                $description = trim($description."\n\nEtsy: ".$sheetRow['etsy_link']);
            }
            if ($sheetRow['reference_link'] !== '' && ! str_contains($description, $sheetRow['reference_link'])) {
                $description = trim($description."\n\nLink tham khảo: ".$sheetRow['reference_link']);
            }

            $metaKeywords = $this->buildMetaKeywords($sheetRow['tag'], $sheetRow['catalog']);

            $images = [];
            if ($sheetRow['drive_folder'] === '' && $this->isDirectMediaUrl($sheetRow['reference_link'])) {
                $images[] = $sheetRow['reference_link'];
            }

            $csvRow = [
                'template_id' => (string) $templateId,
                'sku' => $sheetRow['sku'],
                'product_name' => $productName,
                'price' => $sheetRow['custom'],
                'description' => $description,
                'meta_keywords' => $metaKeywords,
                'quantity' => $defaultQuantity > 0 ? (string) $defaultQuantity : '',
                'status' => $defaultStatus,
                'image_1' => $images[0] ?? '',
                'image_2' => $images[1] ?? '',
                'image_3' => $images[2] ?? '',
                'image_4' => $images[3] ?? '',
                'image_5' => $images[4] ?? '',
                'image_6' => $images[5] ?? '',
                'image_7' => $images[6] ?? '',
                'image_8' => $images[7] ?? '',
                'video_url' => '',
                'drive_folder' => $sheetRow['drive_folder'],
            ];

            $mappedRows[] = $csvRow;
        }

        if ($mappedRows === []) {
            throw new RuntimeException('Không có dòng dữ liệu hợp lệ trong phạm vi đã chọn.');
        }

        $csvPath = $this->writeCsv($mappedRows);

        return [
            'path' => $csvPath,
            'total_rows' => count($mappedRows),
            'sheet_title' => $sheetTitle,
            'credentials_path' => $credentialsPath,
        ];
    }

    /**
     * @return array{0: Sheets, 1: string}|null
     */
    private function createAuthorizedSheetsClient(): ?array
    {
        $credentialsPath = $this->resolveCredentialsPath(
            (string) config('services.google.sheets.credentials_path')
        );

        if ($credentialsPath === '' || ! is_file($credentialsPath)) {
            return null;
        }

        $client = new Client();
        $client->setApplicationName('PressOnNail Product Import');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig($credentialsPath);

        return [new Sheets($client), $credentialsPath];
    }

    private function resolveCredentialsPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    private function resolveSheetTitle(
        Sheets $sheets,
        string $spreadsheetId,
        string $configuredSheetName,
        ?int $gid
    ): string {
        $spreadsheet = $sheets->spreadsheets->get($spreadsheetId, [
            'fields' => 'sheets(properties(sheetId,title))',
        ]);

        $sheetTitles = [];
        $sheetIdToTitle = [];
        foreach ((array) $spreadsheet->getSheets() as $sheet) {
            $title = (string) ($sheet->getProperties()?->getTitle() ?? '');
            $sheetId = (int) ($sheet->getProperties()?->getSheetId() ?? 0);
            if ($title !== '') {
                $sheetTitles[] = $title;
                $sheetIdToTitle[$sheetId] = $title;
            }
        }

        if ($gid !== null && isset($sheetIdToTitle[$gid])) {
            return $sheetIdToTitle[$gid];
        }

        if ($configuredSheetName !== '') {
            foreach ($sheetTitles as $title) {
                if (strcasecmp($title, $configuredSheetName) === 0) {
                    return $title;
                }
            }

            $available = implode(', ', array_map(fn ($t) => '«'.$t.'»', $sheetTitles));
            throw new RuntimeException(
                "Không tìm thấy tab «{$configuredSheetName}». Các tab có sẵn: {$available}."
            );
        }

        if ($sheetTitles !== []) {
            return $sheetTitles[0];
        }

        return 'Sheet1';
    }

    /**
     * @return array<int, string|null>
     */
    private function fetchRow(Sheets $sheets, string $spreadsheetId, string $sheetTitle, int $row): array
    {
        $rows = $this->fetchRange($sheets, $spreadsheetId, $sheetTitle, $row, $row);

        return $rows[0] ?? [];
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function fetchRange(
        Sheets $sheets,
        string $spreadsheetId,
        string $sheetTitle,
        int $rowFrom,
        int $rowTo
    ): array {
        $escaped = str_replace("'", "''", trim($sheetTitle));
        $range = sprintf("'%s'!A%d:Z%d", $escaped, $rowFrom, $rowTo);

        $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        return is_array($values) ? $values : [];
    }

    /**
     * @param  array<int, string|null>  $headerCells
     * @return array<string, int|null>
     */
    private function resolveColumnMap(array $headerCells): array
    {
        $aliases = [
            'stt' => ['stt', 'no', '#'],
            'sku' => ['sku', 'ma sku', 'ma_sku'],
            'product' => ['product', 'san pham'],
            'title' => ['title', 'ten', 'ten san pham', 'product name', 'product_name'],
            'custom' => ['custom', 'gia them', 'price', 'gia'],
            'drive_folder' => ['link drive', 'link_drive', 'drive', 'google drive', 'folder drive'],
            'reference_link' => ['link tham khao', 'link_tham_khao', 'tham khao', 'reference', 'reference link'],
            'posted' => ['da dang', 'đã đăng', 'da_dang', 'posted', 'published', 'dang'],
            'etsy_link' => ['etsy link', 'etsy_link', 'etsy'],
            'tag' => ['tag', 'tags', 'keyword', 'keywords'],
            'description' => ['description', 'mo ta', 'desc', 'mô tả'],
            'catalog' => ['catalog', 'calalog', 'catalogue', 'collection'],
        ];

        $fields = array_keys($aliases);
        $map = array_fill_keys($fields, null);

        foreach ($aliases as $field => $names) {
            foreach ($names as $alias) {
                foreach ($headerCells as $index => $label) {
                    if ($this->headerMatches($label, $alias)) {
                        $map[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    private function headerMatches(?string $label, string $alias): bool
    {
        return $this->normalizeHeader($label) === $this->normalizeHeader($alias);
    }

    private function normalizeHeader(?string $label): string
    {
        $value = Str::lower(trim((string) $label));
        if ($value === '') {
            return '';
        }

        $value = str_replace(['đ', 'Đ'], 'd', $value);

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_D);
            if (is_string($normalized)) {
                $value = preg_replace('/\p{Mn}/u', '', $normalized) ?? $normalized;
            }
        }

        $value = str_replace(['-', ' '], '_', $value);
        $value = preg_replace('/_+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    /**
     * @param  array<int, string|null>  $cells
     * @param  array<string, int|null>  $columnMap
     * @return array{sku: string, product: string, title: string, custom: string, drive_folder: string, reference_link: string, posted: string, etsy_link: string, tag: string, description: string, catalog: string}
     */
    private function mapSheetRow(array $cells, array $columnMap): array
    {
        $get = fn (string $key): string => $this->cell($cells, $columnMap[$key] ?? null);

        return [
            'sku' => $get('sku'),
            'product' => $get('product'),
            'title' => $get('title'),
            'custom' => $get('custom'),
            'drive_folder' => $get('drive_folder'),
            'reference_link' => $get('reference_link'),
            'posted' => $get('posted'),
            'etsy_link' => $get('etsy_link'),
            'tag' => $get('tag'),
            'description' => $get('description'),
            'catalog' => $get('catalog'),
        ];
    }

    /**
     * @param  array<int, string|null>  $cells
     */
    private function cell(array $cells, ?int $index): string
    {
        if ($index === null) {
            return '';
        }

        return trim((string) ($cells[$index] ?? ''));
    }

    private function buildMetaKeywords(string $tag, string $catalog): string
    {
        $parts = array_filter([
            trim($tag),
            trim($catalog) !== '' ? 'catalog:'.trim($catalog) : '',
        ]);

        return Str::limit(implode(', ', $parts), 255, '');
    }

    private function isDirectMediaUrl(string $url): bool
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        if (str_contains($url, 'drive.google.com/drive/folders')) {
            return false;
        }

        if (preg_match('#^https?://(www\.)?(pin\.it|pinterest\.|etsy\.com/)#i', $url)) {
            return false;
        }

        return str_contains($url, 'drive.google.com/file')
            || (bool) preg_match('/\.(jpe?g|png|gif|webp|mp4|mov)(\?|$)/i', parse_url($url, PHP_URL_PATH) ?? '');
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function writeCsv(array $rows): string
    {
        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.uniqid('gsheet_import_', true).'.csv';
        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new RuntimeException('Không tạo được file CSV tạm.');
        }

        fputcsv($handle, self::CSV_COLUMNS);
        foreach ($rows as $row) {
            $line = [];
            foreach (self::CSV_COLUMNS as $column) {
                $line[] = $row[$column] ?? '';
            }
            fputcsv($handle, $line);
        }

        fclose($handle);

        return $path;
    }
}
