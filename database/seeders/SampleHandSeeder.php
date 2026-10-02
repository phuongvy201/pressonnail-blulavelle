<?php

namespace Database\Seeders;

use App\Models\ApiUploadAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Registers pre-made "sample hand" images so customers can preview the nail design
 * without supplying a photo of their own hand.
 *
 * Workflow:
 *  1. Drop JPG/PNG files into public/images/virtual-nail/sample-hands/ (anything matching
 *     the patterns below — name = skin tone + side, e.g. light-right.jpg, medium-left.png).
 *  2. Run: php artisan db:seed --class=SampleHandSeeder
 *  3. The seeder copies each file to storage/app/private/api-uploads/sample-hands/
 *     (so the worker can read it during AI processing) and creates a public-facing
 *     ApiUploadAsset row with purpose = virtual_try_on_sample_hand, status = ready.
 *
 * Notes:
 *  - Files are idempotent — re-running the seeder updates existing rows by filename.
 *  - Files in the source folder that are not registered get a row inserted on first run;
 *    files removed from disk are NOT purged from the DB automatically (so manual review).
 */
class SampleHandSeeder extends Seeder
{
    /** Source folder for sample hand photos. Files here are also served directly by the web server as previews. */
    private const SOURCE_DIR = 'images/virtual-nail/sample-hands';

    /** Destination folder on the private local disk — used by ProcessNailTryOnJob. */
    private const STORAGE_DIR = 'api-uploads/sample-hands';

    /** Hand-side keywords inferred from the filename. Anything else falls back to 'right'. */
    private const SIDE_KEYWORDS = [
        'left' => 'left',
        'right' => 'right',
        'lhand' => 'left',
        'rhand' => 'right',
    ];

    /** Skin-tone keywords inferred from the filename (used in the human-readable label). */
    private const TONE_KEYWORDS = [
        'light' => 'Light',
        'fair' => 'Light',
        'pale' => 'Light',
        'medium' => 'Medium',
        'tan' => 'Medium',
        'olive' => 'Olive',
        'deep' => 'Deep',
        'dark' => 'Deep',
        'brown' => 'Deep',
        'ebony' => 'Deep',
    ];

    public function run(): void
    {
        $sourceAbsolute = public_path(self::SOURCE_DIR);
        if (! is_dir($sourceAbsolute)) {
            $this->command?->warn(sprintf(
                'Sample hand folder missing: %s — create it and drop JPG/PNG files for the seeder to register.',
                $sourceAbsolute,
            ));

            return;
        }

        $files = $this->discoverImages($sourceAbsolute);
        if ($files === []) {
            $this->command?->warn('No sample hand images found in '.self::SOURCE_DIR);

            return;
        }

        $registeredRows = 0;
        $skipped = 0;

        foreach ($files as $absolutePath) {
            $filename = basename($absolutePath);
            $stored = $this->copyToPrivateDisk($absolutePath, $filename);
            if ($stored === null) {
                $skipped++;
                continue;
            }

            $meta = $this->inspectImage($stored['absolute'], $stored['bytes']);
            if ($meta === null) {
                $skipped++;
                continue;
            }

            $label = $this->humanLabel($filename);
            $handSide = $this->inferHandSide($filename);
            $publicId = 'asset_sample_'.Str::lower(Str::slug(pathinfo($filename, PATHINFO_FILENAME), '_'));

            ApiUploadAsset::query()->updateOrCreate(
                ['public_id' => $publicId],
                [
                    'purpose' => ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND,
                    'mime_type' => $meta['mime'],
                    'expected_size' => $meta['size'],
                    'checksum_sha256' => $meta['checksum'],
                    'disk' => config('api_v1.upload_disk', 'local'),
                    'path' => $stored['relative'],
                    'width' => $meta['width'],
                    'height' => $meta['height'],
                    'byte_size' => $meta['size'],
                    'status' => ApiUploadAsset::STATUS_READY,
                    'upload_token' => $label, // re-used as the human-readable label
                    'guest_token' => $handSide, // re-used as left/right hand-side hint
                    'upload_expires_at' => null,
                    'completed_at' => now(),
                ],
            );

            $registeredRows++;
            $this->command?->info(sprintf(
                'Sample hand ready: %s (%s, %s) — %s',
                $filename,
                $meta['width'].'x'.$meta['height'],
                $meta['size'].' bytes',
                $publicId,
            ));
        }

        $this->command?->info(sprintf(
            'SampleHandSeeder complete — registered %d, skipped %d.',
            $registeredRows,
            $skipped,
        ));
    }

    /**
     * @return list<string>
     */
    private function discoverImages(string $absoluteDir): array
    {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $found = [];

        $iterator = new \FilesystemIterator(
            $absoluteDir,
            \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO
        );

        foreach ($iterator as $info) {
            if (! $info->isFile() || ! $info->isReadable()) {
                continue;
            }
            $ext = strtolower($info->getExtension());
            if (! in_array($ext, $allowed, true)) {
                continue;
            }
            $found[] = $info->getPathname();
        }

        sort($found);

        return $found;
    }

    /**
     * @return array{absolute: string, relative: string, bytes: string}|null
     */
    private function copyToPrivateDisk(string $sourceAbsolute, string $filename): ?array
    {
        $disk = Storage::disk(config('api_v1.upload_disk', 'local'));
        $relative = self::STORAGE_DIR.'/'.$filename;
        $bytes = @file_get_contents($sourceAbsolute);

        if ($bytes === false || $bytes === '') {
            $this->warn('Could not read '.$sourceAbsolute);

            return null;
        }

        if (! $disk->put($relative, $bytes)) {
            $this->warn('Could not write '.$relative.' to private disk');

            return null;
        }

        $absolute = $disk->path($relative);

        return [
            'absolute' => $absolute,
            'relative' => $relative,
            'bytes' => $bytes,
        ];
    }

    /**
     * @return array{mime: string, size: int, width: int, height: int, checksum: string}|null
     */
    private function inspectImage(string $absolute, string $bytes): ?array
    {
        $size = strlen($bytes);
        if ($size < 1024) {
            $this->warn($absolute.' is too small ('.$size.' bytes) — skipping.');

            return null;
        }

        $info = @getimagesize($absolute);
        if (! is_array($info)) {
            $this->warn($absolute.' is not a recognizable image — skipping.');

            return null;
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        if ($width < 256 || $height < 256) {
            $this->warn($absolute.' is too small ('.$width.'x'.$height.') — minimum 256x256 required.');

            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = (string) ($finfo->buffer($bytes) ?: '');
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($detected, $allowed, true)) {
            $this->warn($absolute.' has unsupported MIME type '.$detected);

            return null;
        }

        return [
            'mime' => $detected,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'checksum' => hash('sha256', $bytes),
        ];
    }

    private function humanLabel(string $filename): string
    {
        $base = (string) pathinfo($filename, PATHINFO_FILENAME);
        $base = str_replace(['-', '_'], ' ', $base);

        $tone = null;
        foreach (self::TONE_KEYWORDS as $needle => $label) {
            if (stripos($base, $needle) !== false) {
                $tone = $label;
                break;
            }
        }

        $side = null;
        foreach (self::SIDE_KEYWORDS as $needle => $label) {
            if (stripos($base, $needle) !== false) {
                $side = ucfirst($label);
                break;
            }
        }

        $parts = array_filter([$tone, $side]);
        if ($parts === []) {
            return 'Sample hand';
        }

        return implode(' — ', $parts).' hand';
    }

    private function inferHandSide(string $filename): string
    {
        $base = strtolower((string) pathinfo($filename, PATHINFO_FILENAME));
        foreach (self::SIDE_KEYWORDS as $needle => $label) {
            if (str_contains($base, $needle)) {
                return $label;
            }
        }

        return 'right';
    }

    private function warn(string $message): void
    {
        $this->command?->warn('  '.$message);
    }
}