<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = Validator::make([
            'search' => $this->apiQueryParam($request, 'search'),
            'categoryId' => $this->apiQueryParam($request, 'categoryId', 'category_id'),
            'sortBy' => $this->apiQueryParam($request, 'sortBy', 'sort') ?? 'newest',
        ], [
            'search' => ['nullable', 'string', 'max:200'],
            'categoryId' => ['nullable', 'integer', 'min:1'],
            'sortBy' => ['nullable', Rule::in(['newest', 'oldest', 'name', 'products'])],
        ])->validate();

        $query = ProductTemplate::query()
            ->with(['category'])
            ->withCount(['products', 'variants']);

        if (! empty($filters['categoryId'])) {
            $query->where('category_id', (int) $filters['categoryId']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        match ($filters['sortBy']) {
            'name' => $query->orderBy('name'),
            'oldest' => $query->oldest(),
            'products' => $query->orderByDesc('products_count'),
            default => $query->latest(),
        };

        $items = $query->get()
            ->map(fn (ProductTemplate $template) => $this->transformListItem($template))
            ->values();

        return response()->json([
            'success' => true,
            'total' => $items->count(),
            'items' => $items,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $template = ProductTemplate::query()
            ->with(['category', 'attributes', 'variants'])
            ->withCount('products')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformDetail($template),
        ]);
    }

    private function transformListItem(ProductTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'category_id' => $template->category_id,
            'category' => $template->category ? [
                'id' => $template->category->id,
                'name' => $template->category->name,
                'slug' => $template->category->slug ?? null,
            ] : null,
            'base_price' => (float) $template->base_price,
            'list_price' => $template->list_price !== null ? (float) $template->list_price : null,
            'primary_image' => $this->resolvePrimaryImageUrl($template->media ?? [], $template->name),
            'allow_customization' => (bool) $template->allow_customization,
            'variants_count' => (int) ($template->variants_count ?? 0),
            'products_count' => (int) ($template->products_count ?? 0),
            'created_at' => $template->created_at?->toIso8601String(),
            'updated_at' => $template->updated_at?->toIso8601String(),
        ];
    }

    private function transformDetail(ProductTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'category_id' => $template->category_id,
            'category' => $template->category ? [
                'id' => $template->category->id,
                'name' => $template->category->name,
                'slug' => $template->category->slug ?? null,
            ] : null,
            'base_price' => (float) $template->base_price,
            'list_price' => $template->list_price !== null ? (float) $template->list_price : null,
            'description' => $template->description,
            'media' => $this->transformMediaList($template->media ?? [], $template->name),
            'allow_customization' => (bool) $template->allow_customization,
            'customizations' => $this->transformCustomizations($template),
            'attributes' => $this->transformAttributes($template),
            'variants' => $template->variants
                ->map(fn ($variant) => [
                    'id' => $variant->id,
                    'variant_name' => $variant->variant_name,
                    'attributes' => $variant->attributes ?? [],
                    'price' => $variant->price !== null ? (float) $variant->price : null,
                    'list_price' => $variant->list_price !== null ? (float) $variant->list_price : null,
                    'quantity' => (int) ($variant->quantity ?? 0),
                    'media' => $this->transformMediaList($variant->media ?? [], $variant->variant_name),
                ])
                ->values()
                ->all(),
            'products_count' => (int) ($template->products_count ?? 0),
            'created_at' => $template->created_at?->toIso8601String(),
            'updated_at' => $template->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, mixed>  $media
     * @return array<int, array{url: string, alt: string, type: string, poster?: string}>
     */
    private function transformMediaList(array $media, string $fallbackAlt): array
    {
        $items = [];

        foreach ($media as $index => $item) {
            $type = $this->resolveMediaType($item);
            $url = $this->resolveMediaUrl($item, $type);

            if ($url === null) {
                continue;
            }

            $entry = [
                'url' => $url,
                'alt' => $this->resolveMediaAlt($item, $fallbackAlt, (int) $index),
                'type' => $type,
            ];

            if ($type === 'video' && is_array($item)) {
                $poster = trim((string) ($item['poster'] ?? ''));
                if ($poster !== '') {
                    $entry['poster'] = $poster;
                }
            }

            $items[] = $entry;
        }

        return $items;
    }

    /**
     * @param  array<int, mixed>  $media
     */
    private function resolvePrimaryImageUrl(array $media, string $fallbackAlt): ?string
    {
        foreach ($this->transformMediaList($media, $fallbackAlt) as $item) {
            if (($item['type'] ?? 'image') === 'video') {
                if (! empty($item['poster'])) {
                    return $item['poster'];
                }

                continue;
            }

            return $item['url'] ?? null;
        }

        return null;
    }

    /**
     * @return array{enabled: bool, fields: array<int, array<string, mixed>>}
     */
    private function transformCustomizations(ProductTemplate $template): array
    {
        if (! $template->hasCustomization()) {
            return [
                'enabled' => false,
                'fields' => [],
            ];
        }

        $fields = [];

        foreach ($template->getCustomizationTypes() as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = (string) ($row['type'] ?? 'text');
            $optionsRaw = $row['options'] ?? '';
            $options = is_string($optionsRaw)
                ? preg_split('/\r\n|\r|\n/', trim($optionsRaw), -1, PREG_SPLIT_NO_EMPTY)
                : (is_array($optionsRaw) ? $optionsRaw : []);

            $label = trim((string) ($row['label'] ?? ''));

            $fields[] = [
                'index' => $index,
                'type' => $type,
                'label' => $label !== '' ? $label : 'Option '.($index + 1),
                'placeholder' => (string) ($row['placeholder'] ?? ''),
                'price' => (float) ($row['price'] ?? 0),
                'required' => (bool) ($row['required'] ?? false),
                'options' => array_values(array_filter(array_map(
                    static fn ($opt) => trim((string) $opt),
                    $options
                ), static fn ($opt) => $opt !== '')),
            ];
        }

        return [
            'enabled' => true,
            'fields' => $fields,
        ];
    }

    /**
     * @return array<int, array{name: string, values: array<int, string>}>
     */
    private function transformAttributes(ProductTemplate $template): array
    {
        $grouped = [];

        foreach ($template->attributes as $attribute) {
            $name = trim((string) $attribute->attribute_name);
            $value = trim((string) $attribute->attribute_value);

            if ($name === '' || $value === '') {
                continue;
            }

            if (! isset($grouped[$name])) {
                $grouped[$name] = [];
            }

            if (! in_array($value, $grouped[$name], true)) {
                $grouped[$name][] = $value;
            }
        }

        return collect($grouped)
            ->map(fn (array $values, string $name) => [
                'name' => $name,
                'values' => array_values($values),
            ])
            ->values()
            ->all();
    }

    private function resolveMediaType(mixed $mediaItem): string
    {
        if (is_array($mediaItem)) {
            $type = strtolower(trim((string) ($mediaItem['type'] ?? '')));
            if ($type === 'video') {
                return 'video';
            }
        }

        if (is_string($mediaItem) && $this->looksLikeVideoUrl($mediaItem)) {
            return 'video';
        }

        return 'image';
    }

    private function resolveMediaUrl(mixed $mediaItem, ?string $type = null): ?string
    {
        $type ??= $this->resolveMediaType($mediaItem);

        if (is_string($mediaItem)) {
            $url = trim($mediaItem);

            return $url !== '' ? $url : null;
        }

        if (! is_array($mediaItem)) {
            return null;
        }

        if ($type === 'video') {
            foreach (['url', 'path'] as $key) {
                $url = trim((string) ($mediaItem[$key] ?? ''));
                if ($url !== '') {
                    return $url;
                }
            }

            return null;
        }

        foreach (['webp', 'url', 'path'] as $key) {
            $url = trim((string) ($mediaItem[$key] ?? ''));
            if ($url !== '') {
                return $url;
            }
        }

        return null;
    }

    private function looksLikeVideoUrl(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return (bool) preg_match('/\.(mp4|webm|mov|avi|ogg|ogv|m4v)$/i', $path);
    }

    private function resolveMediaAlt(mixed $mediaItem, string $fallbackAlt, int $index): string
    {
        if (is_array($mediaItem)) {
            $alt = trim((string) ($mediaItem['keywords'] ?? $mediaItem['alt'] ?? ''));
            if ($alt !== '') {
                return Str::limit($alt, 500, '');
            }
        }

        return Str::limit($fallbackAlt, 500, '');
    }

    private function apiQueryParam(Request $request, string $camel, ?string $snake = null): mixed
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
