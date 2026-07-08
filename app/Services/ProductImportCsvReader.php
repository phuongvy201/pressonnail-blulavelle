<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class ProductImportCsvReader
{
    /**
     * Đọc CSV theo generator — không nạp toàn bộ file vào RAM.
     *
     * @return \Generator<int, array<string, string>>
     */
    public function rows(string $path): \Generator
    {
        if (! is_readable($path)) {
            throw new RuntimeException("Không đọc được file import: {$path}");
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("Không mở được file import: {$path}");
        }

        try {
            $headerLine = fgetcsv($handle);
            if ($headerLine === false) {
                return;
            }

            $headers = array_map(
                fn ($h) => Str::slug(trim((string) $h), '_'),
                $headerLine
            );

            while (($line = fgetcsv($handle)) !== false) {
                if ($line === [null] || empty(array_filter($line, fn ($v) => trim((string) $v) !== ''))) {
                    continue;
                }

                $row = [];
                foreach ($headers as $index => $key) {
                    if ($key === '') {
                        continue;
                    }
                    $row[$key] = trim((string) ($line[$index] ?? ''));
                }

                yield $row;
            }
        } finally {
            fclose($handle);
        }
    }

    public function countDataRows(string $path): int
    {
        $count = 0;
        foreach ($this->rows($path) as $_row) {
            $count++;
        }

        return $count;
    }
}
