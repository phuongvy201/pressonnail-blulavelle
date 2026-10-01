<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ContentBlock;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HomeCustomerFavoritesController
{
    public function show(): JsonResponse
    {
        $data = Cache::remember('api:v1:home:customer-favorites', now()->addMinutes(5), function (): array {
            $block = ContentBlock::getContent('home.customer_favorites', [
                'eyebrow' => 'BluLavelle Community',
                'heading' => 'Customer',
                'heading_highlight' => 'Favorites',
                'view_all_label' => 'View All',
                'view_all_url' => '#',
                'bg_color' => null,
            ]);

            // Default tabs for customer favorites
            $defaultTabs = [
                ['key' => 'card1', 'label' => 'lynhtran', 'avatar_url' => null, 'image_url' => null],
                ['key' => 'card2', 'label' => 'may.nails', 'avatar_url' => null, 'image_url' => null],
                ['key' => 'card3', 'label' => 'blulavelle', 'avatar_url' => null, 'image_url' => null],
                ['key' => 'card4', 'label' => 'nailista', 'avatar_url' => null, 'image_url' => null],
                ['key' => 'card5', 'label' => 'thuyhan', 'avatar_url' => null, 'image_url' => null],
            ];

            $tabs = $block['tabs'] ?? null;
            if (! is_array($tabs) || count($tabs) === 0) {
                $tabs = $defaultTabs;
            } else {
                $tabs = collect($tabs)->map(function ($t, $i) use ($defaultTabs) {
                    $t = is_array($t) ? $t : [];
                    $key = $t['key'] ?? ($defaultTabs[$i]['key'] ?? 'card'.($i + 1));
                    $fallback = collect($defaultTabs)->firstWhere('key', $key) ?? ($defaultTabs[$i] ?? ['key' => $key, 'label' => 'user', 'avatar_url' => null, 'image_url' => null]);

                    return array_merge($fallback, $t);
                })->values()->all();
            }

            while (count($tabs) < 5) {
                $i = count($tabs);
                $tabs[] = $defaultTabs[$i] ?? ['key' => 'card'.($i + 1), 'label' => 'user', 'avatar_url' => null, 'image_url' => null];
            }

            $tabs = array_slice($tabs, 0, 5);

            $items = collect($tabs)->map(function ($t) {
                $username = trim((string) ($t['label'] ?? ''));
                if ($username !== '' && ! str_starts_with($username, '@')) {
                    $username = '@'.$username;
                }

                $avatarUrl = $this->publicImageUrl($t['avatar_url'] ?? null);
                $imageUrl = $this->publicImageUrl($t['image_url'] ?? null);
                $hasGif = ! empty($t['image_url']);

                return [
                    'username' => $username,
                    'avatar_url' => $avatarUrl,
                    'image_url' => $imageUrl,
                    'is_gif' => $hasGif,
                ];
            })->all();

            return [
                'eyebrow' => (string) ($block['eyebrow'] ?? 'BluLavelle Community'),
                'heading' => (string) ($block['heading'] ?? 'Customer'),
                'heading_highlight' => (string) ($block['heading_highlight'] ?? 'Favorites'),
                'view_all_label' => (string) ($block['view_all_label'] ?? 'View All'),
                'view_all_url' => (string) ($block['view_all_url'] ?? '#'),
                'bg_color' => $block['bg_color'] ?? null,
                'items' => $items,
            ];
        });

        return ApiResponse::success($data)
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=900');
    }

    private function publicImageUrl(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $path = trim($value);
        if (Str::startsWith($path, ['https://', 'http://'])) {
            return $path;
        }

        return asset('/'.ltrim($path, '/'));
    }
}
