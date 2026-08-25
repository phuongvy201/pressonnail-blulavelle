<?php

namespace App\Jobs;

use App\Models\VirtualNailTrial;
use App\Services\VirtualNailTrialService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVirtualNailTrialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public int $trialId
    ) {}

    public function handle(VirtualNailTrialService $service): void
    {
        $trial = VirtualNailTrial::query()->with('product')->find($this->trialId);
        if (! $trial || ! $trial->product) {
            return;
        }

        if (! in_array($trial->status, [VirtualNailTrial::STATUS_PENDING, VirtualNailTrial::STATUS_PROCESSING], true)) {
            return;
        }

        $trial->markProcessing();

        $disk = Storage::disk('local');
        if (! $disk->exists($trial->hand_image_path)) {
            $trial->markFailed('Hand photo is missing.');

            return;
        }

        $absolutePath = $disk->path($trial->hand_image_path);
        $mime = mime_content_type($absolutePath) ?: 'image/jpeg';
        $uploaded = new UploadedFile($absolutePath, basename($absolutePath), $mime, null, true);

        $result = $service->tryOn(
            $uploaded,
            $trial->product,
            $trial->nail_shape,
            $trial->nail_length
        );

        if (! ($result['success'] ?? false)) {
            $trial->markFailed((string) ($result['message'] ?? 'Generation failed.'));
            Log::error('Virtual nail async trial failed.', [
                'trial_id' => $trial->id,
                'uuid' => $trial->uuid,
                'message' => $result['message'] ?? null,
            ]);

            return;
        }

        $savedPath = $service->storeResultImage($trial->uuid, (string) ($result['image'] ?? ''));
        if ($savedPath === null) {
            $trial->markFailed('Could not save the generated image.');

            return;
        }

        $trial->markCompleted($savedPath, isset($result['provider']) ? (string) $result['provider'] : null);

        Log::info('Virtual nail async trial completed.', [
            'trial_id' => $trial->id,
            'uuid' => $trial->uuid,
            'provider' => $trial->provider,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        $trial = VirtualNailTrial::query()->find($this->trialId);
        if (! $trial || $trial->status === VirtualNailTrial::STATUS_COMPLETED) {
            return;
        }

        $trial->markFailed($exception?->getMessage() ?: 'Generation failed unexpectedly.');
    }
}
