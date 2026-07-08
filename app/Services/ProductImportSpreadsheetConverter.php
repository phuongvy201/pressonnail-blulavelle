<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ProductImportSpreadsheetConverter
{
    public function convertToCsv(string $sourcePath, string $extension): string
    {
        $extension = strtolower($extension);
        if ($extension === 'csv') {
            return $sourcePath;
        }

        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            throw new RuntimeException("Định dạng «{$extension}» chưa được hỗ trợ cho import queue.");
        }

        $reader = IOFactory::createReader($extension === 'xlsx' ? 'Xlsx' : 'Xls');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($sourcePath);
        $sheet = $spreadsheet->getActiveSheet();

        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $csvPath = $dir.DIRECTORY_SEPARATOR.uniqid('import_converted_', true).'.csv';
        $handle = fopen($csvPath, 'w');
        if ($handle === false) {
            $spreadsheet->disconnectWorksheets();
            throw new RuntimeException('Không tạo được file CSV chuyển đổi.');
        }

        try {
            foreach ($sheet->getRowIterator() as $row) {
                $line = [];
                foreach ($row->getCellIterator() as $cell) {
                    $line[] = $cell->getValue();
                }
                fputcsv($handle, $line);
            }
        } finally {
            fclose($handle);
            $spreadsheet->disconnectWorksheets();
        }

        return $csvPath;
    }
}
