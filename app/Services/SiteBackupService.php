<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class SiteBackupService
{
    public function create(): array
    {
        $this->ensureBackupDirectory();

        $filename = 'site-backup-'.now()->format('Y-m-d-His').'.zip';
        $stagingPath = storage_path('app/tmp-backup-zip-'.Str::uuid().'.zip');
        $tempDir = storage_path('app/tmp-backup-'.Str::uuid());
        File::ensureDirectoryExists($tempDir);

        try {
            File::put($tempDir.'/manifest.json', json_encode([
                'app' => config('app.name'),
                'created_at' => now()->toIso8601String(),
                'database_driver' => DB::getDriverName(),
                'includes' => ['database.sql', 'files/'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            File::put($tempDir.'/database.sql', $this->dumpDatabase());

            if (config('backup.include_public_files', true)) {
                $this->copyPublicFiles($tempDir.'/files');
            }

            $this->zipDirectory($tempDir, $stagingPath);
        } finally {
            File::deleteDirectory($tempDir);
        }

        $size = File::size($stagingPath);
        $uploadedToS3 = false;
        $localPath = null;

        try {
            if ($this->s3Enabled()) {
                $this->uploadToS3($filename, $stagingPath);
                $uploadedToS3 = true;
                $this->pruneRemoteBackups();

                $localKeep = max(0, (int) config('backup.local_keep', 0));
                if ($localKeep > 0) {
                    $localPath = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;
                    File::move($stagingPath, $localPath);
                    $this->pruneLocalBackups();
                } else {
                    File::delete($stagingPath);
                    $this->clearLocalBackupZips();
                }
            } else {
                $localPath = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;
                File::move($stagingPath, $localPath);
                $this->pruneLocalBackups();
            }
        } finally {
            if (is_file($stagingPath)) {
                File::delete($stagingPath);
            }
        }

        return [
            'filename' => $filename,
            'path' => $localPath,
            'size' => $size,
            'uploaded_to_s3' => $uploadedToS3,
            's3_key' => $uploadedToS3 ? $this->s3Key($filename) : null,
        ];
    }

    /**
     * @return list<array{
     *   filename: string,
     *   size: int,
     *   size_label: string,
     *   modified_at: string,
     *   modified_label: string,
     *   modified_human: string,
     *   locations: list<string>,
     *   location_label: string,
     *   on_local: bool,
     *   on_s3: bool
     * }>
     */
    public function list(): array
    {
        $this->ensureBackupDirectory();

        /** @var array<string, array{filename: string, size: int, mtime: int, on_local: bool, on_s3: bool}> $map */
        $map = [];

        foreach (File::files($this->backupDirectory()) as $file) {
            if (strtolower($file->getExtension()) !== 'zip') {
                continue;
            }
            $name = $file->getFilename();
            $map[$name] = [
                'filename' => $name,
                'size' => $file->getSize(),
                'mtime' => $file->getMTime(),
                'on_local' => true,
                'on_s3' => false,
            ];
        }

        if ($this->s3Enabled()) {
            try {
                $disk = $this->s3Disk();
                $prefix = $this->s3Prefix();
                foreach ($disk->files($prefix) as $key) {
                    if (! str_ends_with(strtolower($key), '.zip')) {
                        continue;
                    }
                    $name = basename($key);
                    if (! preg_match('/^[A-Za-z0-9._-]+\.zip$/', $name)) {
                        continue;
                    }
                    $size = (int) ($disk->size($key) ?: 0);
                    $mtime = (int) ($disk->lastModified($key) ?: time());
                    if (isset($map[$name])) {
                        $map[$name]['on_s3'] = true;
                        if ($map[$name]['size'] <= 0 && $size > 0) {
                            $map[$name]['size'] = $size;
                        }
                        $map[$name]['mtime'] = max($map[$name]['mtime'], $mtime);
                    } else {
                        $map[$name] = [
                            'filename' => $name,
                            'size' => $size,
                            'mtime' => $mtime,
                            'on_local' => false,
                            'on_s3' => true,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Unable to list S3 backups', ['error' => $e->getMessage()]);
            }
        }

        return collect($map)
            ->sortByDesc('mtime')
            ->values()
            ->map(function (array $item) {
                $at = Carbon::createFromTimestamp($item['mtime'])->timezone(config('app.timezone'));
                $locations = [];
                if ($item['on_local']) {
                    $locations[] = 'local';
                }
                if ($item['on_s3']) {
                    $locations[] = 's3';
                }

                return [
                    'filename' => $item['filename'],
                    'size' => $item['size'],
                    'size_label' => $this->humanSize($item['size']),
                    'modified_at' => $at->format('Y-m-d H:i:s'),
                    'modified_label' => $at->format('d/m/Y H:i'),
                    'modified_human' => $at->locale('vi')->diffForHumans(),
                    'locations' => $locations,
                    'location_label' => $this->locationLabel($locations),
                    'on_local' => $item['on_local'],
                    'on_s3' => $item['on_s3'],
                ];
            })
            ->all();
    }

    public function policySummary(): array
    {
        $retention = config('backup.retention', []);

        return [
            'frequency' => 'Hàng ngày lúc 02:00',
            'retention_daily' => (int) ($retention['daily'] ?? 7),
            'retention_weekly' => (int) ($retention['weekly'] ?? 4),
            'retention_monthly' => (int) ($retention['monthly'] ?? 12),
            'local_keep' => (int) config('backup.local_keep', 0),
            'local_path' => $this->backupDirectory(),
            'keeps_local_copies' => ! $this->s3Enabled() || (int) config('backup.local_keep', 0) > 0,
            's3_enabled' => $this->s3Enabled(),
            's3_bucket' => $this->s3Enabled()
                ? (string) config('filesystems.disks.'.config('backup.s3.disk', 's3_backups').'.bucket')
                : null,
            's3_prefix' => $this->s3Enabled() ? $this->s3Prefix() : null,
        ];
    }

    public function pathFor(string $filename): string
    {
        return $this->resolveZipPath($filename)['path'];
    }

    public function restoreByFilename(string $filename): void
    {
        $resolved = $this->resolveZipPath($filename);

        try {
            $this->restoreFromZip($resolved['path']);
        } finally {
            if ($resolved['ephemeral'] && is_file($resolved['path'])) {
                File::delete($resolved['path']);
            }
        }
    }

    /**
     * @return array{path: string, ephemeral: bool}
     */
    private function resolveZipPath(string $filename): array
    {
        $safe = $this->assertSafeFilename($filename);
        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$safe;
        if (is_file($path)) {
            return ['path' => $path, 'ephemeral' => false];
        }

        if ($this->s3Enabled() && $this->s3Disk()->exists($this->s3Key($safe))) {
            return ['path' => $this->downloadFromS3ToTemp($safe), 'ephemeral' => true];
        }

        throw new RuntimeException('Backup file not found.');
    }

    public function downloadResponse(string $filename): StreamedResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $safe = $this->assertSafeFilename($filename);
        $local = $this->backupDirectory().DIRECTORY_SEPARATOR.$safe;

        if (is_file($local)) {
            return response()->download($local);
        }

        if ($this->s3Enabled()) {
            $key = $this->s3Key($safe);
            if ($this->s3Disk()->exists($key)) {
                return $this->s3Disk()->download($key, $safe);
            }
        }

        throw new RuntimeException('Backup file not found.');
    }

    public function delete(string $filename): void
    {
        $safe = $this->assertSafeFilename($filename);
        $local = $this->backupDirectory().DIRECTORY_SEPARATOR.$safe;
        $deleted = false;

        if (is_file($local)) {
            File::delete($local);
            $deleted = true;
        }

        if ($this->s3Enabled()) {
            $key = $this->s3Key($safe);
            if ($this->s3Disk()->exists($key)) {
                $this->s3Disk()->delete($key);
                $deleted = true;
            }
        }

        if (! $deleted) {
            throw new RuntimeException('Backup file not found.');
        }
    }

    public function restoreFromZip(string $zipPath): void
    {
        if (! is_file($zipPath)) {
            throw new RuntimeException('Backup zip not found.');
        }

        $tempDir = storage_path('app/tmp-restore-'.Str::uuid());
        File::ensureDirectoryExists($tempDir);

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new RuntimeException('Unable to open backup zip.');
            }
            $zip->extractTo($tempDir);
            $zip->close();

            $sqlFile = $tempDir.'/database.sql';
            if (! is_file($sqlFile)) {
                throw new RuntimeException('Backup is missing database.sql.');
            }

            $this->restoreDatabase((string) File::get($sqlFile));

            if (config('backup.include_public_files', true) && is_dir($tempDir.'/files')) {
                $this->restorePublicFiles($tempDir.'/files');
            }
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    public function storeUploadedZip($uploadedFile): string
    {
        $this->ensureBackupDirectory();
        $filename = 'site-backup-upload-'.now()->format('Y-m-d-His').'.zip';
        $localPath = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;
        $uploadedFile->move($this->backupDirectory(), $filename);

        if ($this->s3Enabled()) {
            $this->uploadToS3($filename, $localPath);
            $this->pruneRemoteBackups();

            if ((int) config('backup.local_keep', 0) <= 0) {
                File::delete($localPath);
                $this->clearLocalBackupZips();
            } else {
                $this->pruneLocalBackups();
            }
        } else {
            $this->pruneLocalBackups();
        }

        return $filename;
    }

    public function backupDirectory(): string
    {
        return (string) config('backup.disk_path', storage_path('app/backups'));
    }

    public function s3Enabled(): bool
    {
        if (! (bool) config('backup.s3.enabled', false)) {
            return false;
        }

        $diskName = (string) config('backup.s3.disk', 's3_backups');
        $cfg = config('filesystems.disks.'.$diskName, []);

        return ! empty($cfg['key']) && ! empty($cfg['secret']) && ! empty($cfg['bucket']);
    }

    private function dumpDatabase(): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => $this->dumpMysql(),
            'sqlite' => $this->dumpSqlite(),
            default => throw new RuntimeException("Backup does not support [{$driver}] databases."),
        };
    }

    private function dumpMysql(): string
    {
        $tables = array_map(
            static fn ($row) => array_values((array) $row)[0],
            DB::select('SHOW TABLES')
        );

        $sql = "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $create = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $createSql = $create->{'Create Table'} ?? $create->{'Create View'} ?? null;
            if (! $createSql) {
                continue;
            }

            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n\n";

            $rows = DB::table($table)->get();
            foreach ($rows->chunk(80) as $chunk) {
                $values = $chunk->map(function ($row) {
                    $vals = collect((array) $row)->map(fn ($value) => $this->sqlLiteral($value))->implode(', ');

                    return '('.$vals.')';
                })->implode(",\n");

                $columns = collect((array) $chunk->first())->keys()
                    ->map(fn ($col) => '`'.str_replace('`', '``', $col).'`')
                    ->implode(', ');

                $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES\n{$values};\n\n";
            }
        }

        return $sql."SET FOREIGN_KEY_CHECKS=1;\n";
    }

    private function dumpSqlite(): string
    {
        $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
            ->pluck('name')
            ->all();

        $sql = "PRAGMA foreign_keys=OFF;\n\n";

        foreach ($tables as $table) {
            $create = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
            if (! $create?->sql) {
                continue;
            }

            $sql .= "DROP TABLE IF EXISTS \"{$table}\";\n{$create->sql};\n\n";

            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $vals = collect((array) $row)->map(fn ($value) => $this->sqlLiteral($value))->implode(', ');
                $columns = collect((array) $row)->keys()
                    ->map(fn ($col) => '"'.str_replace('"', '""', $col).'"')
                    ->implode(', ');
                $sql .= "INSERT INTO \"{$table}\" ({$columns}) VALUES ({$vals});\n";
            }
            $sql .= "\n";
        }

        return $sql."PRAGMA foreign_keys=ON;\n";
    }

    private function restoreDatabase(string $sql): void
    {
        $driver = DB::getDriverName();
        $statements = $this->splitSqlStatements($sql);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
            foreach ($statements as $statement) {
                DB::unprepared($statement);
            }
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1');

            return;
        }

        if ($driver === 'sqlite') {
            DB::unprepared('PRAGMA foreign_keys = OFF');
            foreach ($statements as $statement) {
                DB::unprepared($statement);
            }
            DB::unprepared('PRAGMA foreign_keys = ON');

            return;
        }

        throw new RuntimeException("Restore does not support [{$driver}] databases.");
    }

    /**
     * @return list<string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $quote = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;

            if ($inString) {
                if ($char === $quote && ($sql[$i - 1] ?? '') !== '\\') {
                    $inString = false;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $quote = $char;
                continue;
            }

            if ($char === ';') {
                $trimmed = trim($buffer);
                if ($trimmed !== '' && $trimmed !== ';') {
                    $statements[] = rtrim($trimmed, ';');
                }
                $buffer = '';
            }
        }

        $trimmed = trim($buffer);
        if ($trimmed !== '') {
            $statements[] = rtrim($trimmed, ';');
        }

        return array_values(array_filter($statements));
    }

    private function sqlLiteral(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $string = (string) $value;
        if (! mb_check_encoding($string, 'UTF-8') || str_contains($string, "\0")) {
            return "X'".bin2hex($string)."'";
        }

        return DB::getPdo()->quote($string);
    }

    private function copyPublicFiles(string $destination): void
    {
        $source = storage_path('app/public');
        if (! is_dir($source)) {
            return;
        }
        File::copyDirectory($source, $destination);
    }

    private function restorePublicFiles(string $source): void
    {
        $destination = storage_path('app/public');
        File::ensureDirectoryExists($destination);
        File::copyDirectory($source, $destination);
    }

    private function zipDirectory(string $source, string $zipPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create backup zip. Enable the PHP zip extension.');
        }

        $source = realpath($source) ?: $source;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $path = $file->getRealPath();
            $relative = ltrim(str_replace('\\', '/', substr($path, strlen($source))), '/');
            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($path, $relative);
            }
        }

        $zip->close();
    }

    private function uploadToS3(string $filename, string $localPath): void
    {
        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Unable to read backup zip for S3 upload.');
        }

        try {
            $this->s3Disk()->writeStream($this->s3Key($filename), $stream, [
                'visibility' => 'private',
                'ContentType' => 'application/zip',
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function downloadFromS3ToTemp(string $filename): string
    {
        $local = storage_path('app/tmp-s3-restore-'.Str::uuid().'-'.$filename);
        $stream = $this->s3Disk()->readStream($this->s3Key($filename));
        if ($stream === false) {
            throw new RuntimeException('Unable to download backup from S3.');
        }

        try {
            $out = fopen($local, 'wb');
            if ($out === false) {
                throw new RuntimeException('Unable to write temporary restore file.');
            }
            stream_copy_to_stream($stream, $out);
            fclose($out);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $local;
    }

    /**
     * Keep newest N local zips (after optional S3 upload).
     */
    private function pruneLocalBackups(): void
    {
        if ($this->s3Enabled()) {
            $keep = max(0, (int) config('backup.local_keep', 0));
            if ($keep === 0) {
                $this->clearLocalBackupZips();

                return;
            }

            $files = collect(File::files($this->backupDirectory()))
                ->filter(fn ($file) => strtolower($file->getExtension()) === 'zip')
                ->sortByDesc(fn ($file) => $file->getMTime())
                ->values();

            foreach ($files->slice($keep) as $file) {
                File::delete($file->getPathname());
            }

            return;
        }

        // Without S3, apply GFS on local files.
        $this->pruneByGfs(
            collect(File::files($this->backupDirectory()))
                ->filter(fn ($file) => strtolower($file->getExtension()) === 'zip')
                ->map(fn ($file) => [
                    'id' => $file->getPathname(),
                    'mtime' => $file->getMTime(),
                ])
                ->all(),
            function (string $path): void {
                File::delete($path);
            }
        );
    }

    private function clearLocalBackupZips(): void
    {
        if (! is_dir($this->backupDirectory())) {
            return;
        }

        foreach (File::files($this->backupDirectory()) as $file) {
            if (strtolower($file->getExtension()) === 'zip') {
                File::delete($file->getPathname());
            }
        }
    }

    private function pruneRemoteBackups(): void
    {
        if (! $this->s3Enabled()) {
            return;
        }

        $disk = $this->s3Disk();
        $prefix = $this->s3Prefix();
        $items = [];

        foreach ($disk->files($prefix) as $key) {
            if (! str_ends_with(strtolower($key), '.zip')) {
                continue;
            }
            $items[] = [
                'id' => $key,
                'mtime' => (int) ($disk->lastModified($key) ?: 0),
            ];
        }

        $this->pruneByGfs($items, function (string $key) use ($disk): void {
            $disk->delete($key);
        });
    }

    /**
     * Grandfather-father-son retention over a list of backups.
     *
     * @param  list<array{id: string, mtime: int}>  $items
     * @param  callable(string): void  $delete
     */
    private function pruneByGfs(array $items, callable $delete): void
    {
        $dailyKeep = max(0, (int) config('backup.retention.daily', 7));
        $weeklyKeep = max(0, (int) config('backup.retention.weekly', 4));
        $monthlyKeep = max(0, (int) config('backup.retention.monthly', 12));

        $sorted = collect($items)->sortByDesc('mtime')->values();
        if ($sorted->isEmpty()) {
            return;
        }

        $keep = [];
        $now = now()->timezone(config('app.timezone'));

        // Daily: newest N
        foreach ($sorted->take($dailyKeep) as $item) {
            $keep[$item['id']] = true;
        }

        // Weekly: one newest per ISO week, up to weeklyKeep weeks looking back
        $weeksKept = 0;
        $seenWeeks = [];
        foreach ($sorted as $item) {
            if ($weeksKept >= $weeklyKeep) {
                break;
            }
            $at = Carbon::createFromTimestamp($item['mtime'])->timezone(config('app.timezone'));
            if ($at->lt($now->copy()->subWeeks($weeklyKeep + 1))) {
                continue;
            }
            $weekKey = $at->format('o-W');
            if (isset($seenWeeks[$weekKey])) {
                continue;
            }
            $seenWeeks[$weekKey] = true;
            $keep[$item['id']] = true;
            $weeksKept++;
        }

        // Monthly: one newest per calendar month, up to monthlyKeep months
        $monthsKept = 0;
        $seenMonths = [];
        foreach ($sorted as $item) {
            if ($monthsKept >= $monthlyKeep) {
                break;
            }
            $at = Carbon::createFromTimestamp($item['mtime'])->timezone(config('app.timezone'));
            if ($at->lt($now->copy()->subMonthsNoOverflow($monthlyKeep + 1))) {
                continue;
            }
            $monthKey = $at->format('Y-m');
            if (isset($seenMonths[$monthKey])) {
                continue;
            }
            $seenMonths[$monthKey] = true;
            $keep[$item['id']] = true;
            $monthsKept++;
        }

        foreach ($sorted as $item) {
            if (! isset($keep[$item['id']])) {
                $delete($item['id']);
            }
        }
    }

    private function s3Disk(): Filesystem
    {
        return Storage::disk((string) config('backup.s3.disk', 's3_backups'));
    }

    private function s3Prefix(): string
    {
        return trim((string) config('backup.s3.prefix', 'site-backups'), '/');
    }

    private function s3Key(string $filename): string
    {
        $prefix = $this->s3Prefix();

        return $prefix === '' ? $filename : $prefix.'/'.$filename;
    }

    /**
     * @param  list<string>  $locations
     */
    private function locationLabel(array $locations): string
    {
        $labels = [];
        if (in_array('s3', $locations, true)) {
            $labels[] = 'AWS S3';
        }
        if (in_array('local', $locations, true)) {
            $labels[] = 'Server';
        }

        return $labels === [] ? '—' : implode(' + ', $labels);
    }

    private function ensureBackupDirectory(): void
    {
        File::ensureDirectoryExists($this->backupDirectory());
    }

    private function assertSafeFilename(string $filename): string
    {
        if (! preg_match('/^[A-Za-z0-9._-]+\.zip$/', $filename)) {
            throw new RuntimeException('Invalid backup filename.');
        }

        return $filename;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1048576, 2).' MB';
    }
}
