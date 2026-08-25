<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Product;
use App\Models\VirtualNailTrial;
use App\Support\VirtualNailSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VirtualNailTrialService
{
    public function isConfigured(): bool
    {
        if (! VirtualNailSettings::enabled()) {
            return false;
        }

        return $this->isChatgpt2apiConfigured() || $this->isImageApiConfigured();
    }

    public function isChatgpt2apiConfigured(): bool
    {
        return trim((string) config('virtual_nail.chatgpt2api.auth_key', '')) !== ''
            && trim((string) config('virtual_nail.chatgpt2api.base_url', '')) !== '';
    }

    public function isImageApiConfigured(): bool
    {
        return trim((string) config('virtual_nail.image_api.api_key', '')) !== ''
            && trim((string) config('virtual_nail.image_api.base_url', '')) !== '';
    }

    /** @deprecated Use isImageApiConfigured() */
    public function isDevQuotaConfigured(): bool
    {
        return $this->isImageApiConfigured();
    }

    /**
     * @return array{success: bool, image?: string, mime?: string, message?: string, provider?: string}
     */
    public function tryOn(UploadedFile $handImage, Product $product, string $nailShape, string $nailLength): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Virtual nail trial is not configured.',
            ];
        }

        $handContents = $this->readUploadedImageContents($handImage);
        if ($handContents === null) {
            Log::warning('Virtual nail trial: empty or unreadable hand image upload.', [
                'product_id' => $product->id,
                'original_name' => $handImage->getClientOriginalName(),
                'mime' => $handImage->getMimeType(),
                'size' => $handImage->getSize(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not read the hand photo. Please try again with another image.',
            ];
        }

        $context = [
            'handContents' => $handContents,
            'handFilename' => $this->uploadedImageFilename($handImage),
            'handMime' => $handImage->getMimeType() ?: 'image/jpeg',
            'productImageUrl' => $this->resolveProductImageUrl($product),
            'prompt' => $this->buildPrompt($product, $nailShape, $nailLength),
            'productId' => $product->id,
        ];

        Log::info('Virtual nail tryOn: start', [
            'step' => 1,
            'product_id' => $product->id,
            'shape' => $nailShape,
            'length' => $nailLength,
            'hand_bytes' => strlen($handContents),
            'hand_filename' => $context['handFilename'],
            'hand_mime' => $context['handMime'],
            'product_image_url' => $context['productImageUrl'],
            'prompt_len' => mb_strlen($context['prompt']),
            'prompt_preview' => Str::limit($context['prompt'], 200),
        ]);

        $preferBrowser = VirtualNailSettings::preferBrowserPool();
        $browserOk = $this->isChatgpt2apiConfigured();
        $imageApiOk = $this->isImageApiConfigured();

        Log::info('Virtual nail tryOn: provider routing', [
            'step' => 2,
            'product_id' => $product->id,
            'prefer_browser_pool' => $preferBrowser,
            'chatgpt2api_configured' => $browserOk,
            'image_api_configured' => $imageApiOk,
        ]);

        if ($preferBrowser && $browserOk && $this->isBrowserPoolHealthy()) {
            Log::info('Virtual nail tryOn: browser pool healthy — using chatgpt2api', [
                'step' => 3,
                'product_id' => $product->id,
            ]);

            $result = $this->tryOnViaChatgpt2api($context);
            if ($result['success']) {
                return $result;
            }

            if ($imageApiOk && $this->shouldFallbackToImageApi($result)) {
                Log::warning('Virtual nail: chatgpt2api failed — falling back to image edits API.', [
                    'product_id' => $product->id,
                    'browser_message' => $result['message'] ?? null,
                    'browser_status' => $result['status'] ?? null,
                ]);

                return $this->tryOnViaImageEdits($context);
            }

            return $this->publicFailure($result);
        }

        if ($imageApiOk) {
            Log::info('Virtual nail tryOn: using image edits API (hand photo)', [
                'step' => 3,
                'product_id' => $product->id,
                'browser_ok' => $browserOk,
                'prefer_browser_pool' => $preferBrowser,
            ]);

            return $this->tryOnViaImageEdits($context);
        }

        if ($preferBrowser && $browserOk) {
            Log::info('Virtual nail tryOn: chatgpt2api (pool health unknown)', [
                'step' => 3,
                'product_id' => $product->id,
            ]);

            return $this->tryOnViaChatgpt2api($context);
        }

        return [
            'success' => false,
            'message' => 'Virtual nail trial is not configured.',
        ];
    }

    /**
     * chatgpt2api health: GET /health?format=json — healthy when active accounts > 0.
     */
    public function isBrowserPoolHealthy(): bool
    {
        $baseUrl = (string) config('virtual_nail.chatgpt2api.base_url');
        $root = preg_replace('#/v1/?$#', '', $baseUrl) ?: $baseUrl;
        $healthUrl = rtrim($root, '/').'/health?format=json';
        $timeout = max(2, (int) config('virtual_nail.chatgpt2api.health_timeout', 5));

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($healthUrl);

            if (! $response->ok()) {
                Log::warning('Virtual nail: chatgpt2api health check non-OK.', [
                    'url' => $healthUrl,
                    'status' => $response->status(),
                ]);

                return false;
            }

            $data = $response->json();
            $healthy = (bool) data_get($data, 'healthy', false);
            $active = (int) data_get($data, 'accounts.active', 0);

            return $healthy && $active > 0;
        } catch (\Throwable $e) {
            Log::warning('Virtual nail: chatgpt2api health check failed.', [
                'url' => $healthUrl,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  array{handContents: string, handFilename: string, handMime: string, productImageUrl: ?string, prompt: string, productId: int}  $context
     * @return array{success: bool, image?: string, mime?: string, message?: string, provider?: string, status?: int, fallback?: bool}
     */
    private function tryOnViaChatgpt2api(array $context): array
    {
        $baseUrl = (string) config('virtual_nail.chatgpt2api.base_url');
        $authKey = (string) config('virtual_nail.chatgpt2api.auth_key');
        $model = (string) config('virtual_nail.chatgpt2api.model');
        $timeout = (int) config('virtual_nail.chatgpt2api.timeout');
        $endpoint = $baseUrl.'/images/edits';

        try {
            $request = Http::withToken($authKey)
                ->timeout($timeout)
                ->attach(
                    'image',
                    $context['handContents'],
                    $context['handFilename'],
                    ['Content-Type' => $context['handMime']]
                );

            $fields = [
                'model' => $model,
                'prompt' => $context['prompt'],
                'n' => 1,
            ];

            if (! empty($context['productImageUrl'])) {
                $fields['image_url'] = $context['productImageUrl'];
            }

            $response = $request->post($endpoint, $fields);
        } catch (\Throwable $e) {
            Log::error('Virtual nail chatgpt2api request failed.', [
                'product_id' => $context['productId'],
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'fallback' => true,
                'message' => 'Could not connect to browser AI pool.',
                'provider' => 'chatgpt2api',
            ];
        }

        if (! $response->ok()) {
            $body = $response->body();
            Log::error('Virtual nail chatgpt2api HTTP error.', [
                'product_id' => $context['productId'],
                'status' => $response->status(),
                'body_preview' => Str::limit($body, 2000),
            ]);

            return [
                'success' => false,
                'fallback' => $this->isPoolExhaustedResponse($response->status(), $body),
                'status' => $response->status(),
                'message' => 'AI processing failed via browser pool.',
                'provider' => 'chatgpt2api',
                'body' => $body,
            ];
        }

        return $this->parseImageResponse($response->json(), $context['productId'], 'chatgpt2api');
    }

    /**
     * New API: POST /v1/images/edits (multipart — giữ ảnh tay + sửa theo prompt)
     * @see https://docs.newapi.pro/en/docs/api/ai-model/images/openai/post-v1-images-edits
     *
     * @param  array{handContents: string, handFilename: string, handMime: string, productImageUrl: ?string, prompt: string, productId: int}  $context
     * @return array{success: bool, image?: string, mime?: string, message?: string, provider?: string}
     */
    private function tryOnViaImageEdits(array $context): array
    {
        $configuredModel = (string) config('virtual_nail.image_api.model');
        $model = $this->resolveNewApiEditModel($configuredModel);

        $result = $this->dispatchImageEditRequest($context, $model);

        if (! ($result['success'] ?? false)
            && $this->isGptImageEditModel($model)
            && ($result['retry_with_dalle2'] ?? false)) {
            Log::error('Virtual nail image edits: gpt-image rejected by provider — retrying with dall-e-2', [
                'product_id' => $context['productId'],
                'configured_model' => $model,
                'http_status' => $result['http_status'] ?? null,
                'api_message' => $result['api_message'] ?? null,
            ]);

            return $this->dispatchImageEditRequest($context, 'dall-e-2');
        }

        unset($result['retry_with_dalle2'], $result['http_status'], $result['api_message']);

        return $result;
    }

    /**
     * @param  array{handContents: string, handFilename: string, handMime: string, productImageUrl: ?string, prompt: string, productId: int}  $context
     * @return array{success: bool, image?: string, mime?: string, message?: string, provider?: string, retry_with_dalle2?: bool, http_status?: int, api_message?: string}
     */
    private function dispatchImageEditRequest(array $context, string $model): array
    {
        $apiKey = (string) config('virtual_nail.image_api.api_key');
        $baseUrl = rtrim((string) config('virtual_nail.image_api.base_url'), '/');
        $timeout = (int) config('virtual_nail.image_api.timeout');
        $configuredSize = (string) config('virtual_nail.image_api.size', '1024x1024');
        $endpoint = $baseUrl.'/images/edits';
        $productId = $context['productId'];
        $editSize = $this->resolveNewApiEditSize($configuredSize, $model);
        $useGptImageFlow = $this->isGptImageEditModel($model);

        $prepared = $this->prepareImageEditAssets($context['handContents'], $editSize, $useGptImageFlow);
        if ($prepared === null) {
            Log::error('Virtual nail image edits: could not prepare hand PNG', [
                'product_id' => $productId,
                'model' => $model,
            ]);

            return [
                'success' => false,
                'message' => 'Could not prepare the hand photo for editing. Please try another image.',
                'provider' => 'image_api',
            ];
        }

        $size = $useGptImageFlow ? $editSize : ($prepared['side'].'x'.$prepared['side']);
        $prompt = $this->buildImageEditPrompt($context);
        $promptLimit = $this->resolveNewApiEditPromptLimit($model);
        if (mb_strlen($prompt) > $promptLimit) {
            $prompt = mb_substr($prompt, 0, $promptLimit);
        }

        Log::error('Virtual nail image edits: request', [
            'step' => 'IE-1',
            'product_id' => $productId,
            'endpoint' => $endpoint,
            'model' => $model,
            'size' => $size,
            'image_bytes' => strlen($prepared['image']),
            'image_side' => $prepared['side'],
            'source_dims' => $prepared['source_w'].'x'.$prepared['source_h'],
            'mask_bytes' => $prepared['mask'] !== null ? strlen($prepared['mask']) : 0,
            'flow' => $useGptImageFlow ? 'gpt-image' : 'dall-e-2',
            'prompt_len' => mb_strlen($prompt),
        ]);

        $startedAt = microtime(true);

        try {
            $request = Http::withToken($apiKey)
                ->timeout($timeout)
                ->acceptJson();

            if ($useGptImageFlow) {
                $request = $request->attach('image[]', $prepared['image'], 'hand.png', ['Content-Type' => 'image/png']);
                if (! empty($context['productImageUrl'])) {
                    $referenceImage = $this->downloadReferenceImage($context['productImageUrl']);
                    if ($referenceImage !== null) {
                        $referenceSquare = $this->prepareSquarePng($referenceImage['contents'], $prepared['side']);
                        if ($referenceSquare !== null) {
                            $request = $request->attach(
                                'image[]',
                                $referenceSquare,
                                'design.png',
                                ['Content-Type' => 'image/png']
                            );
                        }
                    }
                }
            } else {
                $request = $request->attach('image', $prepared['image'], 'hand.png', ['Content-Type' => 'image/png']);
                if ($prepared['mask'] !== null) {
                    $request = $request->attach('mask', $prepared['mask'], 'mask.png', ['Content-Type' => 'image/png']);
                }
            }

            $responseFormat = $this->resolveNewApiEditResponseFormat(
                (string) config('virtual_nail.image_api.response_format', 'b64_json')
            );

            $postFields = [
                'prompt' => $prompt,
                'model' => $model,
                'n' => 1,
                'size' => $size,
                'response_format' => $responseFormat,
            ];

            $response = $request->post($endpoint, $postFields);
        } catch (\Throwable $e) {
            Log::error('Virtual nail image edits: HTTP exception', [
                'product_id' => $productId,
                'endpoint' => $endpoint,
                'model' => $model,
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not connect to image API. Please try again later.',
                'provider' => 'image_api',
            ];
        }

        $body = $response->body();
        $status = $response->status();

        if (! $response->ok()) {
            $apiMessage = (string) data_get(json_decode($body, true), 'error.message', '');

            Log::error('Virtual nail image edits: provider rejected request', [
                'step' => 'IE-2',
                'product_id' => $productId,
                'model' => $model,
                'status' => $status,
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'api_message' => Str::limit($apiMessage, 500),
                'body_preview' => Str::limit($body, 2000),
            ]);

            $failure = [
                'success' => false,
                'message' => $this->imageEditFailureMessage($status, $body, $model),
                'provider' => 'image_api',
                'http_status' => $status,
                'api_message' => $apiMessage,
            ];

            if ($this->isGptImageEditsRejected($status, $body, $model)) {
                $failure['retry_with_dalle2'] = true;
            }

            return $failure;
        }

        Log::warning('Virtual nail image edits: success', [
            'step' => 'IE-2',
            'product_id' => $productId,
            'model' => $model,
            'status' => $status,
            'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return $this->parseImageResponse($response->json(), $productId, 'image_api');
    }

    private function isGptImageEditsRejected(int $status, string $body, string $model): bool
    {
        if (! $this->isGptImageEditModel($model)) {
            return false;
        }

        if ($status === 503) {
            return true;
        }

        $apiCode = (string) data_get(json_decode($body, true), 'error.code', '');

        return $apiCode === 'model_not_found'
            || ($status === 500 && str_contains($body, 'bad_response_status_code'));
    }

    /**
     * @param  array{handContents: string, handFilename: string, handMime: string, productImageUrl: ?string, prompt: string, productId: int}  $context
     */
    private function buildImageEditPrompt(array $context): string
    {
        $lines = [trim((string) $context['prompt'])];

        if (! empty($context['productImageUrl'])) {
            $lines[] = 'Reference press-on nail design (match colors, pattern, finish exactly): '.$context['productImageUrl'];
        }

        $lines[] = 'Keep the same hand, skin tone, pose, and background. Only change the nail surfaces.';

        return trim(implode("\n", array_filter($lines)));
    }

    /**
     * Use the configured model as-is. DevQuote channels vary per account (do not force dall-e-2).
     *
     * @see https://docs.newapi.pro/en/docs/api/ai-model/images/openai/post-v1-images-edits
     */
    private function resolveNewApiEditModel(string $model): string
    {
        $model = strtolower(trim($model));

        return $model !== '' ? $model : 'gpt-image-2';
    }

    private function isGptImageEditModel(string $model): bool
    {
        return str_starts_with(strtolower(trim($model)), 'gpt-image');
    }

    private function imageEditFailureMessage(int $status, string $body, string $model): string
    {
        $apiMessage = (string) data_get(json_decode($body, true), 'error.message', '');
        $apiCode = (string) data_get(json_decode($body, true), 'error.code', '');

        if ($status === 503 || $apiCode === 'model_not_found') {
            return 'Image model "'.$model.'" is not available on the configured API provider. Ask the provider to enable /images/edits for this model, or switch to chatgpt2api.';
        }

        if ($status === 500 && str_contains($body, 'bad_response_status_code') && $this->isGptImageEditModel($model)) {
            return 'Your API provider accepts "'.$model.'" for text-to-image but rejected /images/edits (hand-photo try-on). Retrying with dall-e-2, or enable chatgpt2api.';
        }

        return 'AI image editing failed. Please try another photo or design.';
    }

    private function resolveNewApiEditSize(string $configured, string $model): string
    {
        $normalized = strtolower(trim($configured));

        if ($this->isGptImageEditModel($model)) {
            $gptAllowed = ['1024x1024', '1536x1024', '1024x1536', 'auto'];

            return in_array($normalized, $gptAllowed, true) ? $normalized : '1024x1024';
        }

        $allowed = ['256x256', '512x512', '1024x1024'];

        return in_array($normalized, $allowed, true) ? $normalized : '1024x1024';
    }

    private function resolveNewApiEditPromptLimit(string $model): int
    {
        return $this->isGptImageEditModel($model) ? 32000 : 1000;
    }

    private function resolveNewApiEditResponseFormat(string $configured): string
    {
        $normalized = strtolower(trim($configured));

        return in_array($normalized, ['url', 'b64_json'], true) ? $normalized : 'b64_json';
    }

    /**
     * @return array{contents: string, filename: string, mime: string}|null
     */
    private function downloadReferenceImage(string $url): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        try {
            $response = Http::timeout(30)->get($url);
            if (! $response->ok()) {
                return null;
            }

            $contents = $response->body();
            if ($contents === '') {
                return null;
            }

            $path = parse_url($url, PHP_URL_PATH);
            $filename = is_string($path) ? basename($path) : 'design.jpg';
            if ($filename === '' || $filename === '.') {
                $filename = 'design.jpg';
            }

            $mime = (string) ($response->header('Content-Type') ?: 'image/jpeg');
            $mime = strtok($mime, ';') ?: 'image/jpeg';

            return [
                'contents' => $contents,
                'filename' => $filename,
                'mime' => $mime,
            ];
        } catch (\Throwable $e) {
            Log::warning('Virtual nail image edits: could not download reference image', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Square PNG for /images/edits — pad (letterbox) to preserve the full hand, then downscale.
     *
     * @return array{image: string, mask: ?string, side: int, source_w: int, source_h: int}|null
     */
    private function prepareImageEditAssets(string $contents, string $configuredSize, bool $useGptImageFlow = false): ?array
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            return null;
        }

        $source = @imagecreatefromstring($contents);
        if ($source === false) {
            return null;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);

            return null;
        }

        $padded = $this->padImageToSquare($source, $srcW, $srcH);
        imagedestroy($source);
        if ($padded === null) {
            return null;
        }

        $padW = imagesx($padded);
        $padH = imagesy($padded);

        $requested = (int) explode('x', strtolower($configuredSize))[0];
        $sidesToTry = array_values(array_unique(array_filter([1024, 512, 256, $requested])));
        rsort($sidesToTry, SORT_NUMERIC);

        $maxBytes = $useGptImageFlow ? (25 * 1024 * 1024) : (4 * 1024 * 1024);

        foreach ($sidesToTry as $side) {
            $square = imagecreatetruecolor($side, $side);
            if ($square === false) {
                continue;
            }

            imagealphablending($square, false);
            imagesavealpha($square, true);
            $transparent = imagecolorallocatealpha($square, 0, 0, 0, 127);
            imagefilledrectangle($square, 0, 0, $side, $side, $transparent);
            imagealphablending($square, true);
            imagecopyresampled($square, $padded, 0, 0, 0, 0, $side, $side, $padW, $padH);
            imagealphablending($square, false);
            imagesavealpha($square, true);

            $imagePng = null;
            for ($compression = 6; $compression <= 9; $compression++) {
                ob_start();
                imagepng($square, null, $compression);
                $png = (string) ob_get_clean();
                if ($png !== '' && strlen($png) < $maxBytes) {
                    $imagePng = $png;
                    break;
                }
            }
            imagedestroy($square);

            if ($imagePng === null) {
                continue;
            }

            $maskPng = null;
            if (! $useGptImageFlow) {
                $maskPng = $this->buildDalleEditMaskPng($side);
                if ($maskPng === null) {
                    continue;
                }
            }

            imagedestroy($padded);

            return [
                'image' => $imagePng,
                'mask' => $maskPng,
                'side' => $side,
                'source_w' => $srcW,
                'source_h' => $srcH,
            ];
        }

        imagedestroy($padded);

        return null;
    }

    /**
     * @return \GdImage|null
     */
    private function padImageToSquare(\GdImage $source, int $srcW, int $srcH): ?\GdImage
    {
        $padSide = max($srcW, $srcH);
        $padded = imagecreatetruecolor($padSide, $padSide);
        if ($padded === false) {
            return null;
        }

        imagealphablending($padded, false);
        imagesavealpha($padded, true);
        $white = imagecolorallocate($padded, 255, 255, 255);
        imagefilledrectangle($padded, 0, 0, $padSide, $padSide, $white);
        imagealphablending($padded, true);

        $destX = (int) floor(($padSide - $srcW) / 2);
        $destY = (int) floor(($padSide - $srcH) / 2);
        imagecopy($padded, $source, $destX, $destY, 0, 0, $srcW, $srcH);

        return $padded;
    }

    private function prepareSquarePng(string $contents, int $side): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            return null;
        }

        $source = @imagecreatefromstring($contents);
        if ($source === false) {
            return null;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);

            return null;
        }

        $padded = $this->padImageToSquare($source, $srcW, $srcH);
        imagedestroy($source);
        if ($padded === null) {
            return null;
        }

        $padW = imagesx($padded);
        $padH = imagesy($padded);
        $square = imagecreatetruecolor($side, $side);
        if ($square === false) {
            imagedestroy($padded);

            return null;
        }

        imagealphablending($square, false);
        imagesavealpha($square, true);
        $white = imagecolorallocate($square, 255, 255, 255);
        imagefilledrectangle($square, 0, 0, $side, $side, $white);
        imagealphablending($square, true);
        imagecopyresampled($square, $padded, 0, 0, 0, 0, $side, $side, $padW, $padH);
        imagedestroy($padded);

        ob_start();
        imagepng($square, null, 8);
        $png = (string) ob_get_clean();
        imagedestroy($square);

        return $png !== '' ? $png : null;
    }

    /**
     * DALL-E 2 mask: same dimensions as image; fully transparent = edit entire frame.
     */
    private function buildDalleEditMaskPng(int $side): ?string
    {
        $mask = imagecreatetruecolor($side, $side);
        if ($mask === false) {
            return null;
        }

        imagealphablending($mask, false);
        imagesavealpha($mask, true);
        $clear = imagecolorallocatealpha($mask, 0, 0, 0, 127);
        imagefilledrectangle($mask, 0, 0, $side, $side, $clear);

        ob_start();
        imagepng($mask, null, 9);
        $maskPng = (string) ob_get_clean();
        imagedestroy($mask);

        return $maskPng !== '' ? $maskPng : null;
    }

    /**
     * @return array{success: bool, image?: string, mime?: string, message?: string, provider: string}
     */
    private function parseImageResponse(mixed $data, int $productId, string $provider): array
    {
        if (! is_array($data)) {
            return [
                'success' => false,
                'message' => 'Invalid response from AI service.',
                'provider' => $provider,
            ];
        }

        $b64 = (string) data_get($data, 'data.0.b64_json', '');
        $url = (string) data_get($data, 'data.0.url', '');

        if ($b64 !== '') {
            return [
                'success' => true,
                'image' => 'data:image/png;base64,'.$b64,
                'mime' => 'image/png',
                'provider' => $provider,
            ];
        }

        if ($url !== '') {
            return [
                'success' => true,
                'image' => $url,
                'mime' => 'image/png',
                'provider' => $provider,
            ];
        }

        Log::warning('Virtual nail empty image payload.', [
            'product_id' => $productId,
            'provider' => $provider,
            'keys' => array_keys($data),
        ]);

        return [
            'success' => false,
            'message' => 'AI did not return an image. Please try again.',
            'provider' => $provider,
        ];
    }

    /**
     * @param  array{success: bool, message?: string, fallback?: bool, status?: int, body?: string}  $result
     */
    private function shouldFallbackToImageApi(array $result): bool
    {
        if (! empty($result['fallback'])) {
            return true;
        }

        return $this->isPoolExhaustedResponse(
            (int) ($result['status'] ?? 0),
            (string) ($result['body'] ?? $result['message'] ?? '')
        );
    }

    private function isPoolExhaustedResponse(int $status, string $body): bool
    {
        $lower = mb_strtolower($body);

        // Transient upstream / pool issues — try image edits API instead.
        if (in_array($status, [429, 502, 503, 504], true) || $status >= 500) {
            return true;
        }

        return str_contains($lower, 'insufficient_quota')
            || str_contains($lower, 'no available image quota')
            || str_contains($lower, 'no available')
            || str_contains($lower, 'no account in the pool')
            || str_contains($lower, 'upstream_error')
            || str_contains($lower, 'server_error')
            || str_contains($lower, 'timeout')
            || str_contains($lower, 'timed out')
            || str_contains($lower, '超时');
    }

    /**
     * @param  array{success: bool, message?: string}  $result
     * @return array{success: bool, message: string}
     */
    private function publicFailure(array $result): array
    {
        return [
            'success' => false,
            'message' => $result['message'] ?? 'AI processing failed. Please try another photo or design.',
        ];
    }

    /**
     * @return array{contents: string, filename: string, mime: string}|null
     */
    private function downloadImageBinary(string $url): ?array
    {
        try {
            $response = Http::timeout(30)->get($url);
            if (! $response->ok()) {
                return null;
            }

            $contents = $response->body();
            if ($contents === '') {
                return null;
            }

            $mime = (string) ($response->header('Content-Type') ?: 'image/jpeg');
            $mime = strtok($mime, ';') ?: 'image/jpeg';
            $ext = match (true) {
                str_contains($mime, 'png') => 'png',
                str_contains($mime, 'webp') => 'webp',
                default => 'jpg',
            };

            return [
                'contents' => $contents,
                'filename' => 'product.'.$ext,
                'mime' => $mime,
            ];
        } catch (\Throwable $e) {
            Log::warning('Virtual nail: failed to download product image for DevQuota.', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function resolveProductImageUrl(Product $product): ?string
    {
        $media = $product->getEffectiveMedia();
        if (empty($media)) {
            return null;
        }

        $first = $media[0];
        $path = is_string($first)
            ? $first
            : ($first['url'] ?? $first['path'] ?? null);

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string, image: string|null, price: float, badge: ?string, category: ?string}>
     */
    public function listProductsForPicker(array $options = []): array
    {
        $search = isset($options['search']) ? trim((string) $options['search']) : null;
        $limit = max(1, min((int) ($options['limit'] ?? config('virtual_nail.picker_limit', 48)), 48));
        $sort = (string) ($options['sort'] ?? 'popular');
        $collectionId = isset($options['collection_id']) ? (int) $options['collection_id'] : 0;

        $query = Product::query()
            ->availableForDisplay()
            ->select(['products.id', 'products.name', 'products.slug', 'products.price', 'products.media', 'products.created_at', 'products.template_id'])
            ->with(['template:id,media,category_id', 'template.category:id,name'])
            ->withCount('virtualNailTrials as trials_count');

        if ($search !== null && $search !== '') {
            $query->where('products.name', 'like', '%'.$search.'%');
        }

        if ($collectionId > 0) {
            $query->whereHas('collections', fn ($q) => $q
                ->where('collections.id', $collectionId)
                ->active()
                ->approved());
        }

        match ($sort) {
            'newest' => $query->latest('products.id'),
            'price_asc' => $query->orderBy('products.price'),
            'price_desc' => $query->orderByDesc('products.price'),
            default => $query->orderByDesc('trials_count')->orderByDesc('products.id'),
        };

        return $query
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => $this->serializePickerProduct($product))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function pickerMeta(): array
    {
        $collections = Collection::query()
            ->select(['id', 'name', 'slug'])
            ->active()
            ->approved()
            ->whereHas('products', fn ($q) => $q->availableForDisplay())
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(48)
            ->get()
            ->map(fn ($collection) => [
                'id' => (int) $collection->id,
                'name' => (string) $collection->name,
                'slug' => (string) $collection->slug,
            ])
            ->values()
            ->all();

        return [
            'collections' => $collections,
            'trending_searches' => config('virtual_nail.trending_searches', []),
            'sorts' => [
                ['value' => 'popular', 'label' => 'Most popular'],
                ['value' => 'newest', 'label' => 'Newest'],
                ['value' => 'price_asc', 'label' => 'Price: low to high'],
                ['value' => 'price_desc', 'label' => 'Price: high to low'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function productSearchSuggestions(?string $query, int $limit = 8): array
    {
        $limit = max(1, min($limit, 12));
        $term = trim((string) $query);

        if ($term === '') {
            $trending = collect(config('virtual_nail.trending_searches', []))
                ->filter()
                ->take($limit)
                ->values()
                ->all();

            if ($trending !== []) {
                return $trending;
            }
        }

        $names = Product::query()
            ->availableForDisplay()
            ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->unique()
            ->values()
            ->all();

        if ($names !== []) {
            return $names;
        }

        return collect(config('virtual_nail.trending_searches', []))
            ->filter(fn ($item) => $term === '' || stripos((string) $item, $term) !== false)
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, name: string, slug: string, image: string|null, price: float, badge: ?string, category: ?string}
     */
    private function serializePickerProduct(Product $product): array
    {
        $trialsCount = (int) ($product->trials_count ?? 0);
        $badge = null;

        if ($product->created_at && $product->created_at->greaterThan(now()->subDays(30))) {
            $badge = 'new';
        } elseif ($trialsCount >= 8) {
            $badge = 'bestseller';
        } elseif ($trialsCount >= 3) {
            $badge = 'trending';
        }

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'image' => $this->resolveProductImageUrl($product),
            'price' => (float) ($product->price ?? 0),
            'badge' => $badge,
            'category' => $product->template?->category?->name,
        ];
    }

    /**
     * @return array{shapes: list<string>, lengths: list<string>}
     */
    public function styleOptionsForProduct(?Product $product = null): array
    {
        $defaultShapes = collect(VirtualNailSettings::shapes())->filter()->values();
        $defaultLengths = collect(VirtualNailSettings::lengths())->filter()->values();

        if (! $product) {
            return [
                'shapes' => $defaultShapes->all(),
                'lengths' => $defaultLengths->all(),
            ];
        }

        $fromVariants = $this->extractStyleOptionsFromVariants($product);

        $shapes = $fromVariants['shapes']->isNotEmpty()
            ? $this->orderOptionsByConfig($fromVariants['shapes'], $defaultShapes)
            : $defaultShapes;

        $lengths = $fromVariants['lengths']->isNotEmpty()
            ? $this->orderOptionsByConfig($fromVariants['lengths'], $defaultLengths)
            : $defaultLengths;

        return [
            'shapes' => $shapes->values()->all(),
            'lengths' => $lengths->values()->all(),
        ];
    }

    public function resolveProductPayload(?Product $product): ?array
    {
        if (! $product) {
            return null;
        }

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'image' => $this->resolveProductImageUrl($product),
            'price' => (float) ($product->price ?? 0),
        ];
    }

    public function normalizeShape(string $value, ?Product $product = null): string
    {
        return $this->pickAllowedOption(
            $value,
            $this->styleOptionsForProduct($product)['shapes'],
            (string) \App\Support\VirtualNailSettings::defaultShape()
        );
    }

    public function normalizeLength(string $value, ?Product $product = null): string
    {
        return $this->pickAllowedOption(
            $value,
            $this->styleOptionsForProduct($product)['lengths'],
            (string) \App\Support\VirtualNailSettings::defaultLength()
        );
    }

    /**
     * @param  list<string>  $allowed
     */
    public function pickAllowedOption(string $value, array $allowed, string $fallback): string
    {
        $input = mb_strtolower(trim($value));
        if ($input === '') {
            return $fallback;
        }

        foreach ($allowed as $option) {
            if (mb_strtolower((string) $option) === $input) {
                return (string) $option;
            }
        }

        return $fallback;
    }

    private function buildPrompt(Product $product, string $nailShape, string $nailLength): string
    {
        return VirtualNailSettings::buildPrompt(
            $nailShape,
            $nailLength,
            trim((string) $product->name)
        );
    }

    /**
     * @return array{shapes: \Illuminate\Support\Collection<int, string>, lengths: \Illuminate\Support\Collection<int, string>}
     */
    private function extractStyleOptionsFromVariants(Product $product): array
    {
        $shapes = collect();
        $lengths = collect();

        $variants = $product->variants;
        if (! $product->relationLoaded('variants')) {
            $product->load('variants');
            $variants = $product->variants;
        }
        if ($product->template) {
            if (! $product->template->relationLoaded('variants')) {
                $product->template->load('variants');
            }
            if ($product->template->variants?->isNotEmpty()) {
                $variants = $variants->concat($product->template->variants);
            }
        }

        foreach ($variants as $variant) {
            $attrs = $variant->getRawOriginal('attributes');
            if (is_string($attrs)) {
                $attrs = json_decode($attrs, true);
            }
            if (! is_array($attrs)) {
                continue;
            }

            foreach ($attrs as $key => $val) {
                if ($val === null || $val === '') {
                    continue;
                }

                $this->accumulateStyleOption($shapes, $lengths, (string) $key, trim((string) $val));
            }
        }

        return [
            'shapes' => $this->cleanShapeOptions($shapes),
            'lengths' => $this->cleanLengthOptions($lengths),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $shapes
     * @param  \Illuminate\Support\Collection<int, string>  $lengths
     */
    private function accumulateStyleOption($shapes, $lengths, string $key, string $value): void
    {
        if ($value === '') {
            return;
        }

        $parsed = $this->parseShapeLengthValue($value);
        if ($parsed !== null) {
            if ($parsed['shape'] !== '') {
                $shapes->push($parsed['shape']);
            }
            if ($parsed['length'] !== '') {
                $lengths->push($parsed['length']);
            }

            return;
        }

        $keyLower = mb_strtolower(trim($key));

        if ($this->isLengthAttributeKey($keyLower)) {
            $lengths->push($this->normalizeLengthToken($value));

            return;
        }

        if ($this->isShapeAttributeKey($keyLower)) {
            $shapes->push($this->normalizeShapeToken($value));

            return;
        }

        if ($this->looksLikeLengthToken($value)) {
            $lengths->push($this->normalizeLengthToken($value));

            return;
        }

        if ($this->looksLikeShapeToken($value)) {
            $shapes->push($this->normalizeShapeToken($value));
        }
    }

    /**
     * @return array{shape: string, length: string}|null
     */
    private function parseShapeLengthValue(string $value): ?array
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        if ($value === '') {
            return null;
        }

        $lengthPattern = 'Short|Medium|Long|Extra\s*Long';

        if (preg_match('/^('.$lengthPattern.')\s*[-–—]\s*(.+)$/iu', $value, $matches)) {
            return [
                'length' => $this->normalizeLengthToken($matches[1]),
                'shape' => $this->normalizeShapeToken($matches[2]),
            ];
        }

        if (preg_match('/^(.+?)\s*[-–—]\s*('.$lengthPattern.')$/iu', $value, $matches)) {
            return [
                'length' => $this->normalizeLengthToken($matches[2]),
                'shape' => $this->normalizeShapeToken($matches[1]),
            ];
        }

        if (preg_match('/^('.$lengthPattern.')\s*[\/|,]\s*(.+)$/iu', $value, $matches)) {
            return [
                'length' => $this->normalizeLengthToken($matches[1]),
                'shape' => $this->normalizeShapeToken($matches[2]),
            ];
        }

        if (preg_match('/^(.+?)\s*[\/|,]\s*('.$lengthPattern.')$/iu', $value, $matches)) {
            return [
                'length' => $this->normalizeLengthToken($matches[2]),
                'shape' => $this->normalizeShapeToken($matches[1]),
            ];
        }

        return null;
    }

    private function isShapeAttributeKey(string $keyLower): bool
    {
        return in_array($keyLower, ['shape', 'nail shape', 'nail_shape'], true);
    }

    private function isLengthAttributeKey(string $keyLower): bool
    {
        return in_array($keyLower, ['length', 'nail length', 'nail_length', 'size'], true);
    }

    private function looksLikeLengthToken(string $value): bool
    {
        $lower = mb_strtolower(trim($value));

        return in_array($lower, ['short', 'medium', 'long', 'extra long'], true);
    }

    private function looksLikeShapeToken(string $value): bool
    {
        if ($this->looksLikeLengthToken($value) || str_contains($value, ' - ')) {
            return false;
        }

        $known = collect(VirtualNailSettings::shapes())->map(fn ($s) => mb_strtolower((string) $s));

        return $known->contains(mb_strtolower(trim($value)));
    }

    private function normalizeLengthToken(string $value): string
    {
        $lower = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));

        return match ($lower) {
            'short' => 'Short',
            'medium' => 'Medium',
            'long' => 'Long',
            'extra long' => 'Extra Long',
            default => trim($value),
        };
    }

    private function normalizeShapeToken(string $value): string
    {
        $trimmed = trim($value);
        $lower = mb_strtolower($trimmed);

        foreach (VirtualNailSettings::shapes() as $shape) {
            if (mb_strtolower((string) $shape) === $lower) {
                return (string) $shape;
            }
        }

        return ucwords(mb_strtolower($trimmed));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $options
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function cleanShapeOptions($options)
    {
        return $options
            ->map(function ($value) {
                $parsed = $this->parseShapeLengthValue((string) $value);

                return $parsed ? $parsed['shape'] : $this->normalizeShapeToken((string) $value);
            })
            ->filter(fn ($value) => $value !== '' && ! $this->looksLikeLengthToken($value))
            ->unique(fn ($value) => mb_strtolower($value))
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $options
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function cleanLengthOptions($options)
    {
        return $options
            ->map(function ($value) {
                $parsed = $this->parseShapeLengthValue((string) $value);

                return $parsed ? $parsed['length'] : $this->normalizeLengthToken((string) $value);
            })
            ->filter(fn ($value) => $value !== '' && $this->looksLikeLengthToken($value))
            ->unique(fn ($value) => mb_strtolower($value))
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $options
     * @param  \Illuminate\Support\Collection<int, string>  $configOrder
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function orderOptionsByConfig($options, $configOrder)
    {
        $lowerMap = $options->keyBy(fn ($value) => mb_strtolower($value));

        $ordered = $configOrder
            ->filter(fn ($value) => $lowerMap->has(mb_strtolower((string) $value)))
            ->values();

        $extras = $options->filter(function ($value) use ($configOrder) {
            return ! $configOrder->contains(fn ($cfg) => mb_strtolower((string) $cfg) === mb_strtolower($value));
        })->values();

        return $ordered->concat($extras);
    }

    private function readUploadedImageContents(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        $contents = $file->getContent();
        if (is_string($contents) && $contents !== '') {
            return $contents;
        }

        $path = $file->getPathname();
        if (is_string($path) && $path !== '' && is_readable($path)) {
            $fromDisk = @file_get_contents($path);

            return (is_string($fromDisk) && $fromDisk !== '') ? $fromDisk : null;
        }

        return null;
    }

    private function uploadedImageFilename(UploadedFile $file): string
    {
        $name = trim((string) $file->getClientOriginalName());
        if ($name !== '') {
            return $name;
        }

        $ext = strtolower((string) $file->extension());
        if ($ext === '') {
            $ext = 'jpg';
        }

        return 'hand.'.$ext;
    }

    public function storeHandImage(string $uuid, UploadedFile $handImage): ?string
    {
        $ext = strtolower((string) $handImage->extension()) ?: 'jpg';
        $path = 'virtual-nail-trials/'.$uuid.'/hand.'.$ext;

        $stored = Storage::disk('local')->put($path, $handImage->getContent());
        if (! $stored) {
            return null;
        }

        return $path;
    }

    public function storeResultImage(string $uuid, string $imagePayload): ?string
    {
        $binary = $this->decodeImagePayload($imagePayload);
        if ($binary === null) {
            return null;
        }

        $path = 'virtual-nail-trials/'.$uuid.'/result.png';
        $stored = Storage::disk('local')->put($path, $binary);

        return $stored ? $path : null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function listPendingForOwner(?int $userId, ?string $sessionId, int $limit = 10)
    {
        $query = VirtualNailTrial::query()
            ->with(['product:id,name,slug'])
            ->whereIn('status', [VirtualNailTrial::STATUS_PENDING, VirtualNailTrial::STATUS_PROCESSING])
            ->latest();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($sessionId !== null && $sessionId !== '') {
            $query->where('session_id', $sessionId);
        } else {
            return collect();
        }

        return $query
            ->limit(max(1, min($limit, 20)))
            ->get()
            ->map(fn (VirtualNailTrial $trial) => [
                'id' => $trial->uuid,
                'status' => $trial->status,
                'product_name' => $trial->product?->name,
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function listHistoryForOwner(?int $userId, ?string $sessionId, int $limit = 20)
    {
        $query = VirtualNailTrial::query()
            ->with(['product:id,name,slug'])
            ->latest();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($sessionId !== null && $sessionId !== '') {
            $query->where('session_id', $sessionId);
        } else {
            return collect();
        }

        return $query
            ->limit(max(1, min($limit, 50)))
            ->get()
            ->map(fn (VirtualNailTrial $trial) => $this->serializeTrial($trial));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeTrial(VirtualNailTrial $trial, bool $includeImages = true): array
    {
        $product = $trial->relationLoaded('product') ? $trial->product : null;

        $payload = [
            'id' => $trial->uuid,
            'status' => $trial->status,
            'nail_shape' => $trial->nail_shape,
            'nail_length' => $trial->nail_length,
            'provider' => $trial->provider,
            'error_message' => $trial->error_message,
            'created_at' => $trial->created_at?->toIso8601String(),
            'completed_at' => $trial->completed_at?->toIso8601String(),
            'product' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'url' => route('products.show', $product->slug),
            ] : null,
        ];

        if ($includeImages) {
            $payload['result_url'] = $trial->status === VirtualNailTrial::STATUS_COMPLETED
                ? route('api.virtual-nail.trials.result', $trial->uuid)
                : null;
            $payload['hand_url'] = route('api.virtual-nail.trials.hand', $trial->uuid);
        }

        return $payload;
    }

    private function decodeImagePayload(string $payload): ?string
    {
        if (str_starts_with($payload, 'data:')) {
            $comma = strpos($payload, ',');
            if ($comma === false) {
                return null;
            }
            $decoded = base64_decode(substr($payload, $comma + 1), true);

            return is_string($decoded) && $decoded !== '' ? $decoded : null;
        }

        if (filter_var($payload, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(30)->get($payload);
                if ($response->successful()) {
                    return $response->body();
                }
            } catch (\Throwable) {
                return null;
            }
        }

        $decoded = base64_decode($payload, true);

        return is_string($decoded) && $decoded !== '' ? $decoded : null;
    }
}
