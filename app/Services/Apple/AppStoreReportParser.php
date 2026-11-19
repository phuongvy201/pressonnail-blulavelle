<?php

namespace App\Services\Apple;

use RuntimeException;

class AppStoreReportParser
{
    /**
     * @return list<array{stat_date: string, download_type: string, source_type: string, campaign: string, territory: string, device: string, counts: int}>
     */
    public static function rows(string $body): array
    {
        $text = self::inflate($body);
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;
        $lines = preg_split("/\r\n|\n|\r/", trim($text)) ?: [];
        if (count($lines) < 2) {
            return [];
        }

        $headers = array_map(
            fn (string $header): string => strtolower(trim($header)),
            str_getcsv((string) array_shift($lines), "\t")
        );
        $index = array_flip($headers);
        if (! isset($index['date'], $index['counts'])) {
            throw new RuntimeException('App Store report is missing the Date or Counts column.');
        }

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $columns = str_getcsv($line, "\t");
            $date = trim((string) ($columns[$index['date']] ?? ''));
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            $rows[] = [
                'stat_date' => $date,
                'download_type' => self::column($columns, $index, 'download type'),
                'source_type' => self::column($columns, $index, 'source type'),
                'campaign' => self::column($columns, $index, 'campaign'),
                'territory' => self::column($columns, $index, 'territory'),
                'device' => self::column($columns, $index, 'device'),
                'counts' => (int) ($columns[$index['counts']] ?? 0),
            ];
        }

        return $rows;
    }

    private static function inflate(string $body): string
    {
        if (str_starts_with($body, "\x1f\x8b")) {
            $text = gzdecode($body);
            if ($text === false) {
                throw new RuntimeException('App Store report could not be decompressed.');
            }

            return $text;
        }

        return $body;
    }

    /**
     * @param  list<string|null>  $columns
     * @param  array<string, int>  $index
     */
    private static function column(array $columns, array $index, string $name): string
    {
        if (! isset($index[$name])) {
            return '';
        }

        return mb_substr(trim((string) ($columns[$index[$name]] ?? '')), 0, 120);
    }
}
