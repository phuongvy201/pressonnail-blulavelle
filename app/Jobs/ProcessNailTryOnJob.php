<?php

namespace App\Jobs;

use App\Models\ApiUploadAsset;
use App\Models\NailTryOn;
use App\Models\Product;
use App\Services\ApiV1\ApiUploadService;
use App\Services\VirtualNailTrialService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessNailTryOnJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public int $tryOnId)
    {
    }

    public function handle(VirtualNailTrialService $service, ApiUploadService $uploads): void
    {
        $job = NailTryOn::query()->with(['handAsset', 'product'])->find($this->tryOnId);
        if (! $job || $job->trashed()) {
            return;
        }

        if (in_array($job->status, NailTryOn::TERMINAL, true)) {
            return;
        }

        $job->update(['status' => NailTryOn::STATUS_VALIDATING_IMAGE]);

        $hand = $job->handAsset;
        if (! $hand || ! $hand->isReady() || ! $hand->path) {
            $this->failJob($job, 'ASSET_EXPIRED', 'Hand image asset is missing or expired.');

            return;
        }

        $disk = Storage::disk($hand->disk);
        if (! $disk->exists($hand->path)) {
            $this->failJob($job, 'ASSET_EXPIRED', 'Hand image file is missing.');

            return;
        }

        $absolute = $disk->path($hand->path);
        $validationCode = $this->validateHandImage($absolute, $hand);
        if ($validationCode !== null) {
            $this->failJob($job, $validationCode, $this->messageFor($validationCode));

            return;
        }

        $job->update(['status' => NailTryOn::STATUS_DETECTING_HAND]);

        // Lightweight gate before billed render — reject clearly unusable frames.
        $detectCode = $this->detectHandGate($absolute, $hand);
        if ($detectCode !== null) {
            $this->failJob($job, $detectCode, $this->messageFor($detectCode));

            return;
        }

        $product = $job->product ?: Product::query()->find($job->product_id);
        if (! $product) {
            $this->failJob($job, 'TRY_ON_NOT_AVAILABLE_FOR_PRODUCT', 'Product is no longer available.');

            return;
        }

        $job->update(['status' => NailTryOn::STATUS_RENDERING]);

        $mime = $hand->mime_type ?: (mime_content_type($absolute) ?: 'image/jpeg');
        $uploaded = new UploadedFile($absolute, basename($absolute), $mime, null, true);

        $result = $service->tryOn(
            $uploaded,
            $product,
            (string) ($job->nail_shape ?: 'Almond'),
            (string) ($job->nail_length ?: 'Medium')
        );

        if (! ($result['success'] ?? false)) {
            $this->failJob(
                $job,
                'AI_PROCESSING_FAILED',
                (string) ($result['message'] ?? 'AI processing failed.')
            );

            return;
        }

        $imagePayload = (string) ($result['image'] ?? '');
        $binary = $this->decodeImagePayload($imagePayload);
        if ($binary === null) {
            $this->failJob($job, 'AI_PROCESSING_FAILED', 'Could not decode AI result image.');

            return;
        }

        $output = $uploads->createOutputFromBinary(
            $binary,
            str_starts_with($imagePayload, 'data:image/png') ? 'image/png' : 'image/jpeg',
            $job->user_id,
            $job->guest_token
        );

        $job->update([
            'status' => NailTryOn::STATUS_SUCCEEDED,
            'output_asset_id' => $output->id,
            'provider' => isset($result['provider']) ? (string) $result['provider'] : null,
            'error_code' => null,
            'error_message' => null,
            'completed_at' => now(),
        ]);

        Log::info('Nail try-on succeeded', [
            'try_on_id' => $job->public_id,
            'product_id' => $job->product_id,
            'provider' => $job->provider,
            // Do not log signed URLs or image bytes.
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        $job = NailTryOn::query()->find($this->tryOnId);
        if (! $job || in_array($job->status, NailTryOn::TERMINAL, true)) {
            return;
        }

        $this->failJob($job, 'AI_PROCESSING_FAILED', $exception?->getMessage() ?: 'Generation failed unexpectedly.');
    }

    private function failJob(NailTryOn $job, string $code, string $message): void
    {
        $job->update([
            'status' => NailTryOn::STATUS_FAILED,
            'error_code' => $code,
            'error_message' => Str::limit($message, 500),
            'completed_at' => now(),
        ]);

        Log::warning('Nail try-on failed', [
            'try_on_id' => $job->public_id,
            'error_code' => $code,
        ]);
    }

    private function validateHandImage(string $path, ApiUploadAsset $hand): ?string
    {
        $size = @filesize($path) ?: 0;
        if ($size < 1024) {
            return 'UNSUPPORTED_IMAGE';
        }

        $info = @getimagesize($path);
        if (! is_array($info)) {
            return 'UNSUPPORTED_IMAGE';
        }

        $w = (int) $info[0];
        $h = (int) $info[1];
        if ($w < 256 || $h < 256) {
            return 'UNSUPPORTED_IMAGE';
        }

        // Very dark / very bright heuristics via GD average luma.
        if (function_exists('imagecreatefromstring')) {
            $binary = @file_get_contents($path);
            if (is_string($binary) && $binary !== '') {
                $img = @imagecreatefromstring($binary);
                if ($img !== false) {
                    $sample = $this->averageLuma($img);
                    imagedestroy($img);
                    if ($sample !== null && $sample < 18) {
                        return 'IMAGE_TOO_DARK';
                    }
                }
            }
        }

        return null;
    }

    private function detectHandGate(string $path, ApiUploadAsset $hand): ?string
    {
        // Placeholder structural checks until a dedicated detector is wired.
        // Extreme aspect ratios often mean cropped/partial hands.
        if ($hand->width && $hand->height) {
            $ratio = $hand->width / max(1, $hand->height);
            if ($ratio > 3.5 || $ratio < 0.28) {
                return 'HAND_PARTIALLY_OUTSIDE_FRAME';
            }
        }

        return null;
    }

    private function averageLuma(\GdImage $img): ?float
    {
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 1 || $h < 1) {
            return null;
        }

        $stepX = max(1, (int) floor($w / 32));
        $stepY = max(1, (int) floor($h / 32));
        $sum = 0;
        $n = 0;
        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $sum += (0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b);
                $n++;
            }
        }

        return $n > 0 ? $sum / $n : null;
    }

    private function decodeImagePayload(string $payload): ?string
    {
        if (str_starts_with($payload, 'data:image')) {
            $parts = explode(',', $payload, 2);
            if (count($parts) !== 2) {
                return null;
            }
            $decoded = base64_decode($parts[1], true);

            return $decoded === false ? null : $decoded;
        }

        if (str_starts_with($payload, 'http://') || str_starts_with($payload, 'https://')) {
            try {
                $body = \Illuminate\Support\Facades\Http::timeout(60)->get($payload)->body();

                return $body !== '' ? $body : null;
            } catch (\Throwable) {
                return null;
            }
        }

        $decoded = base64_decode($payload, true);

        return $decoded === false ? null : $decoded;
    }

    private function messageFor(string $code): string
    {
        return match ($code) {
            'HAND_NOT_FOUND' => 'No hand was detected. Retake with your hand clearly in frame.',
            'MULTIPLE_HANDS_FOUND' => 'Multiple hands detected. Use a single hand.',
            'NAILS_NOT_VISIBLE' => 'Nails are not clearly visible.',
            'IMAGE_TOO_DARK' => 'Image is too dark. Retake in better lighting.',
            'IMAGE_TOO_BLURRY' => 'Image is too blurry. Hold steady and retake.',
            'HAND_PARTIALLY_OUTSIDE_FRAME' => 'Hand appears cropped. Keep the full hand in frame.',
            'UNSUPPORTED_IMAGE' => 'Unsupported or invalid image.',
            'TRY_ON_NOT_AVAILABLE_FOR_PRODUCT' => 'Try-on is not available for this product.',
            'ASSET_EXPIRED' => 'Asset expired.',
            default => 'AI processing failed.',
        };
    }
}
