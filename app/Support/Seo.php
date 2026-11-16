<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Seo
{
    public static function documentTitle(string $metaTitle, string $sectionTitle, string $viewTitle): string
    {
        foreach ([$metaTitle, $sectionTitle, $viewTitle] as $candidate) {
            $candidate = self::plain($candidate);
            if ($candidate === '') {
                continue;
            }
            if (str_contains(mb_strtolower($candidate), 'blulavelle')) {
                return $candidate;
            }

            return $candidate.' | BluLavelle';
        }

        return 'BluLavelle | Handcrafted Stained Glass Press-On Nails';
    }

    public static function productTitle(Product $product): string
    {
        $name = self::plain((string) $product->name);
        $shop = self::plain((string) ($product->shop?->shop_name ?? ''));
        $withShop = $shop !== '' ? $name.' | '.$shop.' | BluLavelle' : $name.' | BluLavelle';
        if (mb_strlen($withShop) <= 70) {
            return $withShop;
        }

        $branded = $name.' | BluLavelle';
        if (mb_strlen($branded) <= 70) {
            return $branded;
        }

        return Str::limit($name, 55, '…').' | BluLavelle';
    }

    public static function productDescription(Product $product): string
    {
        $name = self::plain((string) $product->name);
        $shop = self::plain((string) ($product->shop?->shop_name ?? ''));
        $raw = (string) ($product->description ?? '');
        if (trim($raw) === '' && $product->template) {
            $raw = (string) ($product->template->description ?? '');
        }
        $raw = self::plain(strip_tags($raw));
        $by = $shop !== '' ? ' by '.$shop : '';
        $body = $raw !== '' ? $raw : 'Reusable press-on nails with custom sizing.';
        $text = $name.$by.'. '.$body;
        if (! str_contains($text, 'BluLavelle')) {
            $text = rtrim($text, '. ').' at BluLavelle.';
        }

        return Str::limit($text, 160, '…');
    }

    public static function productImageUrl(Product $product): ?string
    {
        foreach ($product->getMergedDisplayMedia() as $item) {
            $url = self::mediaUrl($item);
            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }

    public static function mediaUrl(mixed $item): ?string
    {
        if (is_string($item)) {
            $absolute = self::publicAsset($item);

            return $absolute !== '' ? $absolute : null;
        }

        if (! is_array($item)) {
            return null;
        }

        if (strtolower((string) ($item['type'] ?? '')) === 'video') {
            $poster = $item['poster'] ?? null;

            return is_string($poster) ? self::nullablePublicAsset($poster) : null;
        }

        foreach (['webp', 'url', 'path'] as $key) {
            if (! empty($item[$key]) && is_string($item[$key])) {
                $absolute = self::nullablePublicAsset($item[$key]);
                if ($absolute !== null) {
                    return $absolute;
                }
            }
        }

        return null;
    }

    public static function canonicalFromRequest(): string
    {
        $path = trim(request()->path(), '/');

        return self::canonicalPath($path === '' ? '/' : '/'.$path).self::canonicalQuery(request()->query());
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function canonicalQuery(array $query): string
    {
        $kept = [];
        foreach ($query as $key => $value) {
            if (! is_string($key) || self::isTrackingParameter($key)) {
                continue;
            }
            if (strtolower($key) === 'page') {
                if (! is_scalar($value)) {
                    continue;
                }
                $page = (int) $value;
                if ($page > 1) {
                    $kept['page'] = $page;
                }

                continue;
            }
            $normalized = self::canonicalQueryValue($value);
            if ($normalized !== null) {
                $kept[$key] = $normalized;
            }
        }
        ksort($kept);

        return $kept === [] ? '' : '?'.http_build_query($kept);
    }

    public static function redirectTarget(Request $request): ?string
    {
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return null;
        }
        if ($request->is('api/*') || $request->is('admin/*') || $request->is('webhooks/*') || $request->is('payment/*') || $request->is('up')) {
            return null;
        }

        $path = '/'.ltrim($request->getPathInfo(), '/');
        $targetPath = null;
        if (preg_match('#^/page/([^/]+)/?$#', $path, $matches) === 1) {
            $collapsed = self::collapsedPagePath($matches[1]);
            if ($collapsed !== null) {
                $targetPath = $collapsed;
            }
        }
        if ($targetPath === null && $path !== '/' && str_ends_with($path, '/')) {
            $targetPath = rtrim($path, '/') ?: '/';
        }
        if ($targetPath === null) {
            return null;
        }

        return self::canonicalPath($targetPath).self::canonicalQuery($request->query());
    }

    public static function collapsedPagePath(string $slug): ?string
    {
        $slug = trim($slug);
        if ($slug === '' || str_contains($slug, '/')) {
            return null;
        }
        if (in_array($slug, ['shipping-delivery', 'sizing-kit'], true)) {
            return '/'.$slug;
        }
        if (self::hasDedicatedGetRoute($slug)) {
            return null;
        }

        return '/'.$slug;
    }

    private static function hasDedicatedGetRoute(string $slug): bool
    {
        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            if (trim($route->uri(), '/') === $slug) {
                return true;
            }
        }

        return false;
    }

    private static function isTrackingParameter(string $key): bool
    {
        $key = strtolower($key);

        return str_starts_with($key, 'utm_')
            || in_array($key, [
                'fbclid', 'gclid', 'gbraid', 'wbraid', 'msclkid', 'ttclid',
                'mc_cid', 'mc_eid', 'srsltid', 'yclid', '_ga', '_gl', 'ref',
            ], true);
    }

    private static function canonicalQueryValue(mixed $value): mixed
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            $text = trim((string) $value);

            return $text === '' ? null : $text;
        }
        if (! is_array($value)) {
            return null;
        }

        $items = [];
        foreach ($value as $item) {
            if (is_scalar($item) && trim((string) $item) !== '') {
                $items[] = trim((string) $item);
            }
        }

        return $items === [] ? null : $items;
    }

    public static function canonicalPath(string $path): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $path = explode('?', $path, 2)[0];
        $path = '/'.ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        return $path === '/' ? $base.'/' : $base.$path;
    }

    public static function defaultShareImage(): string
    {
        return self::publicAsset('/images/logo/BABYBLUE.png');
    }

    public static function publicAsset(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '//')) {
            return 'https:'.$path;
        }

        $base = rtrim((string) config('app.url'), '/');
        if (str_starts_with($path, '/')) {
            return $base.$path;
        }
        if (str_starts_with($path, 'storage/')) {
            return $base.'/'.$path;
        }

        return $base.'/storage/'.ltrim($path, '/');
    }

    private static function nullablePublicAsset(string $path): ?string
    {
        $absolute = self::publicAsset($path);

        return $absolute !== '' ? $absolute : null;
    }

    private static function plain(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value;
    }
}
