<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Support\ApiV1\ApiResponse;
use App\Support\ApiV1\PayloadNormalizer;
use Illuminate\Http\JsonResponse;

trait WrapsStorefrontJson
{
    /**
     * @param  list<string>  $preserveHeaders
     */
    protected function wrapStorefront(
        JsonResponse $response,
        array $preserveHeaders = ['X-Guest-Cart-Token', 'Idempotency-Replayed', 'Idempotency-Key']
    ): JsonResponse {
        $status = $response->getStatusCode();
        $body = $response->getData(true);

        if (! is_array($body)) {
            return $this->withPreservedHeaders(
                ApiResponse::success($body, null, $status),
                $response,
                $preserveHeaders
            );
        }

        $success = array_key_exists('success', $body)
            ? (bool) $body['success']
            : $status < 400;

        if (! $success) {
            $fields = [];
            if (isset($body['errors']) && is_array($body['errors'])) {
                $fields = $body['errors'];
            } elseif (isset($body['fields']) && is_array($body['fields'])) {
                $fields = $body['fields'];
            }

            return $this->withPreservedHeaders(
                ApiResponse::error(
                    $this->mapStorefrontErrorCode($status, $body),
                    (string) ($body['message'] ?? $body['error']['message'] ?? 'Request failed.'),
                    $status >= 400 ? $status : 400,
                    $fields
                ),
                $response,
                $preserveHeaders
            );
        }

        $meta = null;
        if (isset($body['message']) && is_string($body['message']) && $body['message'] !== '') {
            $meta = ['message' => $body['message']];
        }

        unset($body['success'], $body['message']);

        // Prefer nested data when it's the only meaningful payload key.
        if (array_key_exists('data', $body) && count($body) === 1) {
            $data = $body['data'];
        } else {
            $data = $body;
        }

        $data = PayloadNormalizer::moneyToMinor($data);

        return $this->withPreservedHeaders(
            ApiResponse::success($data, $meta, $status),
            $response,
            $preserveHeaders
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    protected function mapStorefrontErrorCode(int $status, array $body): string
    {
        if (isset($body['error']['code']) && is_string($body['error']['code'])) {
            return $body['error']['code'];
        }

        if (isset($body['code']) && is_string($body['code'])) {
            return $body['code'];
        }

        return match ($status) {
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMITED',
            default => 'HTTP_ERROR',
        };
    }

    /**
     * @param  list<string>  $preserveHeaders
     */
    protected function withPreservedHeaders(
        JsonResponse $out,
        JsonResponse $source,
        array $preserveHeaders
    ): JsonResponse {
        foreach ($preserveHeaders as $header) {
            if ($source->headers->has($header)) {
                $out->headers->set($header, $source->headers->get($header));
            }
        }

        return $out;
    }
}
