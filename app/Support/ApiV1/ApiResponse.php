<?php

namespace App\Support\ApiV1;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiResponse
{
    public static function success(mixed $data = null, mixed $meta = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status)->withHeaders(self::headers());
    }

    public static function error(
        string $code,
        string $message,
        int $status,
        array $fields = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'fields' => (object) $fields,
            ],
            'requestId' => self::requestId(),
        ], $status)->withHeaders(self::headers());
    }

    public static function fromException(Throwable $exception, Request $request): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return self::error(
                'VALIDATION_ERROR',
                'The given data was invalid.',
                422,
                $exception->errors()
            );
        }

        if ($exception instanceof AuthenticationException) {
            return self::error('UNAUTHENTICATED', 'Authentication is required.', 401);
        }

        if ($exception instanceof ModelNotFoundException) {
            return self::error('NOT_FOUND', 'The requested resource was not found.', 404);
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $code = match ($status) {
                401 => 'UNAUTHENTICATED',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                409 => 'CONFLICT',
                429 => 'RATE_LIMITED',
                default => 'HTTP_ERROR',
            };

            return self::error($code, $exception->getMessage() ?: 'Request failed.', $status);
        }

        report($exception);

        $message = config('app.debug')
            ? $exception->getMessage()
            : 'An unexpected error occurred.';

        return self::error('INTERNAL_ERROR', $message, 500);
    }

    public static function requestId(): string
    {
        return (string) (request()->attributes->get('request_id')
            ?: request()->header('X-Request-ID')
            ?: '');
    }

    public static function toMinor(float|int|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function iso(?\DateTimeInterface $value): ?string
    {
        return $value?->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    private static function headers(): array
    {
        $id = self::requestId();

        return $id !== '' ? ['X-Request-ID' => $id] : [];
    }
}
