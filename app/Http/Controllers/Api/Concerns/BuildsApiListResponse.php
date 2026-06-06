<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait BuildsApiListResponse
{
    protected function apiListMeta(LengthAwarePaginator $paginator, ?string $currency = null): array
    {
        $meta = [
            'page' => $paginator->currentPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'lastPage' => $paginator->lastPage(),
            'hasNextPage' => $paginator->hasMorePages(),
            'hasPreviousPage' => $paginator->currentPage() > 1,
        ];

        if ($currency !== null) {
            $meta['currency'] = $currency;
        }

        return $meta;
    }

    protected function apiListResponse(LengthAwarePaginator $paginator, mixed $items, ?string $currency = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'meta' => $this->apiListMeta($paginator, $currency),
            'items' => $items,
        ]);
    }

    protected function apiQueryParam(\Illuminate\Http\Request $request, string $camel, ?string $snake = null): mixed
    {
        if ($request->has($camel)) {
            return $request->input($camel);
        }

        if ($snake !== null && $request->has($snake)) {
            return $request->input($snake);
        }

        return null;
    }
}
