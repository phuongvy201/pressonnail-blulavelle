<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\ReviewsImport;
use App\Models\Product;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReviewImportController extends Controller
{
    /**
     * Trang form import review (Admin + Seller).
     */
    public function showImportForm()
    {
        $user = auth()->user();

        $productsQuery = Product::with('template')->orderBy('name');
        if (!$user->hasRole('admin')) {
            $productsQuery->where('user_id', $user->id);
        }
        $products = $productsQuery->get(['id', 'name', 'sku', 'slug', 'user_id']);

        return view('admin.reviews.import', compact('products'));
    }

    /**
     * Tải file CSV mẫu.
     */
    public function downloadTemplate()
    {
        $headers = [
            'product_id',
            'customer_name',
            'customer_email',
            'rating',
            'review_text',
            'image_url',
            'title',
            'is_verified_purchase',
            'is_approved',
        ];

        $sampleData = [
            [
                '1',
                'Sarah F.',
                'sarah@example.com',
                '5',
                'Salon quality at home was the dream. So many compliments!',
                'https://example.com/image.jpg',
                "I'm totally blown away.",
                '1',
                '1',
            ],
            [
                '1',
                'John D.',
                'john@example.com',
                '4',
                'Great product, fast shipping.',
                '',
                'Very satisfied',
                '1',
                '1',
            ],
        ];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        foreach ($sampleData as $row) {
            fputcsv($csv, $row);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="reviews_import_template.csv"');
    }

    /**
     * Xử lý upload file import.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $user = auth()->user();
        $import = new ReviewsImport($user);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = 'Dòng ' . ($failure->row()) . ': ' . implode(', ', $failure->errors());
            }
            return redirect()
                ->route('admin.reviews.import')
                ->with('error', 'Có lỗi khi import: ' . implode(' | ', array_slice($messages, 0, 5)));
        }

        $errors = $import->getErrors();
        $successCount = $import->getSuccessCount();
        $duplicateCount = $import->getDuplicateCount();

        if ($successCount > 0) {
            $msg = "Đã import thành công {$successCount} review.";
            if ($duplicateCount > 0) {
                $msg .= " {$duplicateCount} review đã tồn tại (bỏ qua).";
            }
            $otherErrors = count($errors) - $duplicateCount;
            if ($otherErrors > 0) {
                $nonDuplicateErrors = array_values(array_filter(
                    $errors,
                    static fn(string $e) => ! str_starts_with($e, 'Review đã tồn tại:')
                ));
                $msg .= ' ' . $otherErrors . ' dòng lỗi: ' . implode('; ', array_slice($nonDuplicateErrors, 0, 3));
            } elseif ($duplicateCount > 0) {
                $msg .= ' ' . implode('; ', array_slice($errors, 0, 3));
            }

            return redirect()
                ->route('admin.reviews.import')
                ->with('success', $msg);
        }

        if (count($errors) > 0) {
            $prefix = $duplicateCount > 0 && $duplicateCount === count($errors)
                ? "Không có review mới. {$duplicateCount} review đã tồn tại: "
                : 'Import thất bại: ';

            return redirect()
                ->route('admin.reviews.import')
                ->with('error', $prefix . implode('; ', array_slice($errors, 0, 5)));
        }

        return redirect()
            ->route('admin.reviews.import')
            ->with('info', 'File không có dòng dữ liệu hợp lệ.');
    }
}
