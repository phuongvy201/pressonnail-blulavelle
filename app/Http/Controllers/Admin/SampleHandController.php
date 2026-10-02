<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiUploadAsset;
use App\Services\ApiV1\ApiUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin interface for managing sample hand images that customers can use
 * to preview nail designs without uploading their own photo.
 *
 * Sample hands live in two places:
 *  - public/images/virtual-nail/sample-hands/  → served directly as previewUrl
 *  - storage/app/private/api-uploads/sample-hands/ → used by the AI worker
 */
class SampleHandController extends Controller
{
    /** Source folder served publicly as previews. */
    private const PUBLIC_DIR = 'images/virtual-nail/sample-hands';

    /** Private folder on S3 accessed by the AI worker. */
    private const S3_DIR = 'api-uploads/sample-hands';

    public function __construct(private ApiUploadService $uploads)
    {
    }

    public function index(): View
    {
        $samples = ApiUploadAsset::query()
            ->where('purpose', ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND)
            ->orderByDesc('id')
            ->get()
            ->map(fn (ApiUploadAsset $asset) => $this->buildRow($asset))
            ->values();

        return view('admin.sample-hands.index', [
            'samples' => $samples,
            'stats' => [
                'total' => $samples->count(),
                'ready' => $samples->where('status', ApiUploadAsset::STATUS_READY)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            'label' => ['required', 'string', 'max:120'],
            'hand_side' => ['required', 'string', 'in:left,right'],
        ]);

        $file = $validated['file'];
        $originalName = $file->getClientOriginalName();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // ── Determine mime type — infer from extension first (temp file may be gone); getMimeType() as fallback ──
        $mimeType = in_array($extension, ['jpg', 'jpeg'], true)
            ? 'image/jpeg'
            : ($extension === 'png' ? 'image/png' : ($extension === 'webp' ? 'image/webp' : null));

        if ($mimeType === null) {
            try {
                $mimeType = $file->getMimeType();
            } catch (\Throwable) {
                $mimeType = 'application/octet-stream';
            }
        }

        // ── Persist the public copy ──────────────────────────────────────────
        $publicDir = public_path(self::PUBLIC_DIR);
        if (! is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        // Build a safe filename: normalised lowercase, spaces → hyphens, preserved extension
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^a-z0-9_-]+/', '-', strtolower($baseName));
        $safeBase = trim($safeBase, '-');
        if ($safeBase === '') {
            $safeBase = Str::random(8);
        }

        $publicFilename = $safeBase.'.'.$extension;
        $publicPath = $publicDir.'/'.$publicFilename;
        $file->move($publicDir, $publicFilename);

        // ── Copy to S3 private storage (worker reads from here) ───────────────
        $s3Path = self::S3_DIR.'/'.$publicFilename;
        $binary = file_get_contents($publicPath);
        Storage::disk('s3')->put($s3Path, $binary, [
            'ContentType' => $mimeType,
            'visibility' => 'private',
        ]);

        $byteSize = strlen($binary);
        $checksum = hash('sha256', $binary);

        ApiUploadAsset::query()->create([
            'public_id' => 'asset_'.Str::random(24),
            'user_id' => null,
            'guest_token' => $validated['hand_side'], // repurposed: stores hand side
            'purpose' => ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND,
            'mime_type' => $mimeType,
            'expected_size' => $byteSize,
            'checksum_sha256' => $checksum,
            'disk' => 's3',
            'path' => $s3Path,
            'byte_size' => $byteSize,
            'status' => ApiUploadAsset::STATUS_READY,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('admin.sample-hands.index')
            ->with('success', 'Sample hand "'.$validated['label'].'" uploaded successfully.');
    }

    public function update(Request $request, string $publicId): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'hand_side' => ['required', 'string', 'in:left,right'],
        ]);

        $asset = ApiUploadAsset::query()
            ->where('public_id', $publicId)
            ->where('purpose', ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND)
            ->firstOrFail();

        $asset->update([
            'guest_token' => $validated['hand_side'],
        ]);

        // Label is derived; we store it in a config/setting keyed by public_id.
        // For simplicity, store as a JSON map in a settings row.
        $labels = $this->getSampleLabels();
        $labels[$publicId] = [
            'label' => $validated['label'],
            'hand_side' => $validated['hand_side'],
            'updated_at' => now()->toDateTimeString(),
        ];
        $this->saveSampleLabels($labels);

        return redirect()
            ->route('admin.sample-hands.index')
            ->with('success', 'Sample hand updated.');
    }

    public function destroy(string $publicId): RedirectResponse
    {
        $asset = ApiUploadAsset::query()
            ->where('public_id', $publicId)
            ->where('purpose', ApiUploadAsset::PURPOSE_VIRTUAL_TRY_ON_SAMPLE_HAND)
            ->firstOrFail();

        $filename = basename($asset->path ?? '');

        // Remove public file
        if ($filename) {
            $publicFile = public_path(self::PUBLIC_DIR.'/'.$filename);
            if (is_file($publicFile)) {
                unlink($publicFile);
            }

            // Remove S3 copy
            if ($asset->disk === 's3' && $asset->path) {
                Storage::disk('s3')->delete($asset->path);
            } else {
                // Legacy local fallback
                $privateFile = Storage::disk('local')->path($asset->path ?? '');
                if ($privateFile && is_file($privateFile)) {
                    unlink($privateFile);
                }
            }
        }

        // Remove DB record
        $asset->delete();

        // Remove label from settings
        $labels = $this->getSampleLabels();
        unset($labels[$publicId]);
        $this->saveSampleLabels($labels);

        return redirect()
            ->route('admin.sample-hands.index')
            ->with('success', 'Sample hand deleted.');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   id: string,
     *   label: string,
     *   handSide: string,
     *   previewUrl: string,
     *   status: string,
     *   byteSize: int,
     *   createdAt: string,
     *   updatedAt: string,
     * }
     */
    private function buildRow(ApiUploadAsset $asset): array
    {
        $filename = basename($asset->path ?? '');
        $previewUrl = null;
        if ($filename) {
            $publicPath = public_path(self::PUBLIC_DIR.'/'.$filename);
            if (is_file($publicPath)) {
                $previewUrl = asset(self::PUBLIC_DIR.'/'.$filename);
            } else {
                // Fallback: signed URL from private storage
                $previewUrl = $this->uploads->temporaryUrl($asset, 60);
            }
        }

        $labels = $this->getSampleLabels();
        $meta = $labels[$asset->public_id] ?? [];
        $label = $meta['label'] ?? $this->inferLabelFromFilename($filename);
        $handSide = $meta['hand_side'] ?? ($asset->guest_token ?? 'right');

        return [
            'id' => $asset->public_id,
            'label' => $label,
            'handSide' => $handSide,
            'previewUrl' => $previewUrl,
            'status' => $asset->status,
            'byteSize' => $asset->byte_size ?? 0,
            'filename' => $filename,
            'createdAt' => $asset->created_at?->format('Y-m-d H:i') ?? '—',
            'updatedAt' => $asset->updated_at?->format('Y-m-d H:i') ?? '—',
        ];
    }

    private function inferLabelFromFilename(string $filename): string
    {
        $name = strtolower(pathinfo($filename, PATHINFO_FILENAME));

        $tones = [
            'light' => 'Light', 'fair' => 'Light', 'pale' => 'Light',
            'medium' => 'Medium', 'tan' => 'Medium',
            'olive' => 'Olive',
            'deep' => 'Deep', 'dark' => 'Deep', 'brown' => 'Deep', 'ebony' => 'Deep',
        ];

        $sides = [
            'left' => 'left', 'right' => 'right', 'lhand' => 'left', 'rhand' => 'right',
        ];

        $tone = 'Custom';
        $side = 'right';

        foreach ($tones as $kw => $label) {
            if (str_contains($name, $kw)) {
                $tone = $label;
                break;
            }
        }

        foreach ($sides as $kw => $label) {
            if (str_contains($name, $kw)) {
                $side = $label;
                break;
            }
        }

        return $tone.' — '.ucfirst($side).' hand';
    }

    /** @return array<string, array{label: string, hand_side: string, updated_at: string}> */
    private function getSampleLabels(): array
    {
        $raw = config('virtual_nail.sample_hand_labels', '[]');
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, array{label: string, hand_side: string, updated_at: string}> $labels */
    private function saveSampleLabels(array $labels): void
    {
        config(['virtual_nail.sample_hand_labels' => json_encode($labels)]);
        \App\Support\Settings::set('virtual_nail.sample_hand_labels', json_encode($labels));
    }
}
