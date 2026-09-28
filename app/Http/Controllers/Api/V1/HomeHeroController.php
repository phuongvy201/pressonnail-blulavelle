<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ContentBlock;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HomeHeroController
{
    public function show(): JsonResponse
    {
        $slides = Cache::remember('api:v1:home:hero', now()->addMinutes(5), function (): array {
            $hero = ContentBlock::getContent('home.hero', [
                'tagline' => 'Professional Grade at Home',
                'heading' => 'Manicure in',
                'heading_highlight' => 'Minutes',
                'subheading' => 'Salon-quality press-on nails, ready in minutes.',
                'image' => 'storage/images/44ad1fa40f4f3b0b55214cf29e1dd8a2.jpg',
                'cta_primary_label' => 'Shop the Collection',
            ]);

            $fallbackImage = $this->publicImageUrl($hero['image'] ?? null);
            $slides = [];
            for ($number = 1; $number <= 3; $number++) {
                $prefix = $number === 1 ? '' : "slide{$number}_";
                $heading = trim((string) ($hero[$prefix.'heading'] ?? ''));
                if ($heading === '') {
                    continue;
                }

                $highlight = trim((string) ($hero[$prefix.'heading_highlight'] ?? ''));
                $images = $hero["slide{$number}_images"] ?? [];
                $image = is_array($images) ? $this->publicImageUrl($images[0] ?? null) : null;
                $slides[] = [
                    'id' => "home-hero-{$number}",
                    'tagline' => (string) ($hero[$prefix.'tagline'] ?? ''),
                    'title' => trim($heading.' '.$highlight),
                    'description' => (string) ($hero[$prefix.'subheading'] ?? ''),
                    'image' => $image ?: $fallbackImage,
                    'ctaLabel' => (string) ($hero['cta_primary_label'] ?? 'Shop the Collection'),
                    'collection' => trim((string) ($hero["slide{$number}_collection"] ?? '')),
                ];
            }

            return $slides;
        });

        return ApiResponse::success(['slides' => $slides])
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
