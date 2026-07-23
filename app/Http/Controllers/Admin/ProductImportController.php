<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunProductImportJob;
use App\Models\ProductTemplate;
use App\Services\GoogleSheetsProductImportService;
use App\Services\ProductImportCsvReader;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductImportController extends Controller
{
    public function __construct(
        private GoogleSheetsProductImportService $googleSheetsImport
    ) {}

    /**
     * Show import form
     */
    public function showImportForm()
    {
        $user = auth()->user();

        // Get templates for reference
        if ($user->hasRole('admin')) {
            $templates = ProductTemplate::with('category')->orderBy('name', 'asc')->get();
        } else {
            $templates = ProductTemplate::where('user_id', $user->id)
                ->with('category')
                ->orderBy('name', 'asc')
                ->get();
        }

        $sheetsConfigured = $this->googleSheetsImport->isConfigured();

        return view('admin.products.import', compact('templates', 'sheetsConfigured'));
    }

    /**
     * Download Excel template
     */
    public function downloadTemplate()
    {
        $headers = [
            'template_id',
            'product_name',
            'price',
            'description',
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
            'video_url'
        ];

        $sampleData = [
            [
                '16',
                'Sample Product 1',
                '5.00',
                'Custom description for this product',
                '100',
                'active',
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
                '',
                '',
                '',
                '',
                '',
                '',
                'https://example.com/video.mp4'
            ],
            [
                '16',
                'Sample Product 2',
                '10.00',
                '',
                '50',
                'draft',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
        ];

        // Create CSV content
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        foreach ($sampleData as $row) {
            fputcsv($csv, $row);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="products_import_template.csv"');
    }

    /**
     * Import products from Google Sheet
     */
    public function importFromGoogleSheet(Request $request)
    {
        $isAjax = $request->ajax() || $request->wantsJson();

        try {
            $validated = $request->validate([
                'template_id' => 'required|integer|exists:product_templates,id',
                'spreadsheet_url' => 'required|string|max:2048',
                'sheet_name' => 'nullable|string|max:255',
                'header_row' => 'nullable|integer|min:1|max:1000',
                'row_from' => 'required|integer|min:1|max:10000',
                'row_to' => 'required|integer|min:1|max:10000|gte:row_from',
                'default_quantity' => 'nullable|integer|min:0|max:999999',
                'default_status' => 'nullable|in:active,draft,inactive',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed: '.implode(', ', array_map(
                        fn ($msgs) => is_array($msgs) ? implode(', ', $msgs) : (string) $msgs,
                        array_values($e->errors())
                    )),
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        $importPath = null;

        try {
            $template = ProductTemplate::findOrFail((int) $validated['template_id']);
            $user = auth()->user();
            if (! $user->hasRole('admin') && $template->user_id !== $user->id) {
                throw new \RuntimeException('Bạn không có quyền dùng template này.');
            }

            $result = $this->googleSheetsImport->buildImportCsv(
                $validated['spreadsheet_url'],
                (int) $validated['template_id'],
                $validated['sheet_name'] ?? null,
                (int) ($validated['header_row'] ?? 1),
                (int) $validated['row_from'],
                (int) $validated['row_to'],
                [
                    'default_quantity' => (int) ($validated['default_quantity'] ?? 0),
                    'default_status' => $validated['default_status'] ?? 'active',
                ]
            );

            $importPath = $result['path'];
            $totalRows = $result['total_rows'];

            Log::info('Google Sheet import prepared', [
                'template_id' => $validated['template_id'],
                'sheet_title' => $result['sheet_title'],
                'total_rows' => $totalRows,
                'row_from' => $validated['row_from'],
                'row_to' => $validated['row_to'],
            ]);

            return $this->runImportFromPath($importPath, 'csv', $totalRows, $request);
        } catch (\Throwable $e) {
            if (is_string($importPath) && file_exists($importPath)) {
                @unlink($importPath);
            }

            Log::error('Google Sheet import exception: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Import từ Google Sheet thất bại: '.$e->getMessage());
        }
    }

    /**
     * Process import file
     */
    public function import(Request $request)
    {
        // For AJAX requests, always return JSON even on validation errors
        $isAjax = $request->ajax() || $request->wantsJson();

        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed: ' . implode(', ', $e->errors()['file'] ?? ['Invalid file']),
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        $importPath = null;
        $deleteImportFile = false;

        try {
            $file = $request->file('file');
            [$importPath, $deleteImportFile] = $this->resolveImportFilePath($file);

            if ($importPath === null) {
                throw new \RuntimeException('Cannot read uploaded import file.');
            }

            $extension = strtolower((string) $file->getClientOriginalExtension());

            $totalRows = $extension === 'csv'
                ? app(ProductImportCsvReader::class)->countDataRows($importPath)
                : $this->countRowsFromPath($importPath, $extension, $file);

            // Giữ file tạm cho queue worker — RunProductImportJob tự cleanup sau khi xử lý.
            // Không unlink ngay sau dispatch: với database queue worker chạy sau → mất file → FAIL.
            return $this->runImportFromPath($importPath, $extension, $totalRows, $request, $deleteImportFile);
        } catch (\Throwable $e) {
            if ($deleteImportFile && is_string($importPath) && file_exists($importPath)) {
                @unlink($importPath);
            }

            Log::error("Import exception: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Import failed: ' . $e->getMessage(),
                    'message' => 'An error occurred during import. Please check the logs for details.',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Get import progress
     */
    public function getProgress(Request $request)
    {
        $progressKey = $request->input('progress_key');

        if (!$progressKey) {
            return response()->json([
                'error' => 'Progress key is required',
            ], 400);
        }

        $progress = Cache::get($progressKey, [
            'processed' => 0,
            'total' => 0,
            'success' => 0,
            'errors' => 0,
            'percentage' => 0,
            'status' => 'unknown',
        ]);

        $errorMessages = Cache::get("{$progressKey}:error_messages", []);
        if ($errorMessages !== []) {
            $progress['error_messages'] = array_slice($errorMessages, 0, 10);
        }

        // Add timestamp to help debug
        $progress['fetched_at'] = now()->toIso8601String();

        return response()->json($progress);
    }

    /**
     * Đưa import vào queue — worker xử lý từng sản phẩm song song (queue imports).
     */
    protected function runImportFromPath(
        string $importPath,
        string $extension,
        int $totalRows,
        Request $request,
        bool $deleteAfterUse = true
    ) {
        if ($totalRows < 1) {
            if ($deleteAfterUse && file_exists($importPath)) {
                @unlink($importPath);
            }

            $message = 'File import không có dòng dữ liệu hợp lệ.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $progressKey = 'import_progress_'.uniqid();

        Log::info('Product import queued', [
            'total_rows' => $totalRows,
            'progress_key' => $progressKey,
            'file_type' => $extension,
            'import_path' => $importPath,
            'queue' => config('product_import.queue'),
        ]);

        RunProductImportJob::dispatch(
            $importPath,
            $progressKey,
            $totalRows,
            (int) auth()->id(),
            $deleteAfterUse,
            $extension
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'progress_key' => $progressKey,
                'completed' => false,
                'message' => 'Import đã được đưa vào hàng đợi. Vui lòng chờ xử lý...',
            ]);
        }

        return redirect()->route('admin.products.import')
            ->with('success', 'Import đã được đưa vào hàng đợi. Theo dõi tiến độ trên trang import.')
            ->with('import_progress_key', $progressKey);
    }

    /**
     * Copy file upload vào storage/app/temp — path ổn định cho cả countRows và Excel::import.
     * Không dùng trực tiếp C:\Windows\Temp\php*.tmp (PHP có thể xóa trước khi import chạy).
     *
     * @return array{0: string|null, 1: bool} [path, shouldDeleteAfterUse]
     */
    protected function resolveImportFilePath(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            return [null, false];
        }

        $ext = strtolower((string) $file->getClientOriginalExtension()) ?: 'tmp';
        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dest = $dir . DIRECTORY_SEPARATOR . uniqid('import_', true) . '.' . $ext;

        $source = null;
        foreach ([$file->getRealPath(), $file->getPathname()] as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_readable($candidate)) {
                $source = $candidate;
                break;
            }
        }

        if ($source !== null && @copy($source, $dest)) {
            return [$dest, true];
        }

        $pathname = $file->getPathname();
        if (is_string($pathname) && $pathname !== '') {
            $contents = @file_get_contents($pathname);
            if ($contents !== false && @file_put_contents($dest, $contents) !== false) {
                return [$dest, true];
            }
        }

        return [null, false];
    }

    /**
     * Count total rows in file (excluding header) - Memory efficient
     */
    protected function countRowsFromPath(string $path, string $extension, UploadedFile $file): int
    {
        try {
            $count = 0;

            if ($extension === 'csv') {
                $handle = fopen($path, 'r');
                if ($handle) {
                    fgetcsv($handle);
                    while (($line = fgetcsv($handle)) !== false) {
                        if (! empty(array_filter($line))) {
                            $count++;
                        }
                    }
                    fclose($handle);
                }
            } else {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(
                    $extension === 'xlsx' ? 'Xlsx' : 'Xls'
                );
                $reader->setReadDataOnly(true);
                $reader->setReadEmptyCells(false);

                $spreadsheet = $reader->load($path);
                $worksheet = $spreadsheet->getActiveSheet();
                $count = $worksheet->getHighestRow() - 1;

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $worksheet, $reader);
            }

            return max(0, $count);
        } catch (\Throwable $e) {
            Log::warning('Failed to count rows efficiently: '.$e->getMessage().' - Using fallback estimation');

            $fileSize = $file->getSize();
            $bytesPerRow = $extension === 'csv' ? 300 : 500;
            $estimated = max(1, (int) ($fileSize / $bytesPerRow));
            Log::info("Estimated rows based on file size: {$estimated} rows");

            return $estimated;
        }
    }
}
