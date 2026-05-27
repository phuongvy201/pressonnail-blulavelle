<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\ProductsImport;
use App\Models\ProductTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductImportController extends Controller
{
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

        return view('admin.products.import', compact('templates'));
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

        try {
            // Generate unique progress key
            $progressKey = 'import_progress_' . uniqid();

            $file = $request->file('file');
            [$importPath, $deleteImportFile] = $this->resolveImportFilePath($file);

            if ($importPath === null) {
                throw new \RuntimeException('Cannot read uploaded import file.');
            }

            $extension = strtolower((string) $file->getClientOriginalExtension());

            try {
                // Count total rows (excluding header) - memory efficient
                $totalRows = $this->countRowsFromPath($importPath, $extension, $file);

                // Ensure totalRows is at least 1
                if ($totalRows < 1) {
                    $totalRows = 1;
                }

                Log::info("Starting import", [
                    'total_rows' => $totalRows,
                    'progress_key' => $progressKey,
                    'file_size' => $file->getSize(),
                    'file_type' => $extension,
                    'import_path' => $importPath,
                ]);

                // Create import instance with progress key
                $import = new ProductsImport(auth()->user(), $progressKey);
                $import->setTotalRows($totalRows);

                // Maatwebsite Excel on Windows needs a real file path (not UploadedFile — getRealPath() is empty).
                $importTarget = $importPath;

            // If AJAX request, return progress key immediately
            if ($request->ajax() || $request->wantsJson()) {
                // Return progress key immediately so frontend can start polling
                // Import will run synchronously but progress will be updated via cache
                $response = response()->json([
                    'success' => true,
                    'progress_key' => $progressKey,
                    'completed' => false, // Will be updated via polling
                    'message' => 'Import started. Please wait...',
                ]);

                // If FastCGI is available, finish request and continue processing
                if (function_exists('fastcgi_finish_request')) {
                    $response->sendHeaders();
                    $response->sendContent();
                    fastcgi_finish_request();

                    // Continue import in background
                    try {
                        Excel::import($import, $importTarget);
                        $errors = $import->getErrors();
                        $successCount = $import->getSuccessCount();
                        $import->markCompleted();
                    } catch (\Throwable $e) {
                        $import->markFailed($e->getMessage());
                        Log::error("Import failed: " . $e->getMessage());
                    } finally {
                        if ($deleteImportFile) {
                            @unlink($importPath);
                        }
                    }
                    return $response;
                } else {
                    // For non-FastCGI, run import synchronously but return response first
                    // Start import in a way that allows progress updates
                    try {
                        Excel::import($import, $importTarget);
                        $errors = $import->getErrors();
                        $successCount = $import->getSuccessCount();
                        $import->markCompleted();

                        // Update response with final results
                        return response()->json([
                            'success' => true,
                            'progress_key' => $progressKey,
                            'completed' => true,
                            'success_count' => $successCount,
                            'error_count' => count($errors),
                            'errors' => array_slice($errors, 0, 10),
                            'message' => "Successfully imported {$successCount} products!" . (count($errors) > 0 ? " (" . count($errors) . " errors)" : ''),
                        ]);
                    } catch (\Throwable $e) {
                        $import->markFailed($e->getMessage());

                        return response()->json([
                            'success' => false,
                            'progress_key' => $progressKey,
                            'error' => 'Import failed: ' . $e->getMessage(),
                        ], 500);
                    } finally {
                        if ($deleteImportFile) {
                            @unlink($importPath);
                        }
                    }
                }
            }

            // Regular form submission (non-AJAX)
            Excel::import($import, $importTarget);

            $errors = $import->getErrors();
            $successCount = $import->getSuccessCount();

            // Mark as completed
            $import->markCompleted();

            if (count($errors) > 0) {
                $errorMessage = "Imported {$successCount} products successfully. Errors: " . implode(', ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $errorMessage .= " (and " . (count($errors) - 5) . " more errors)";
                }

                return redirect()->route('admin.products.index')
                    ->with('error', $errorMessage);
            }

            return redirect()->route('admin.products.index')
                ->with('success', "Successfully imported {$successCount} products!");
            } finally {
                if ($deleteImportFile && isset($importPath) && is_string($importPath) && file_exists($importPath)) {
                    @unlink($importPath);
                }
            }
        } catch (\Throwable $e) {
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

        // Add timestamp to help debug
        $progress['fetched_at'] = now()->toIso8601String();

        return response()->json($progress);
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
