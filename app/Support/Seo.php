<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    public static function metaDescription(string $value): string
    {
        $value = self::plain($value);
        if ($value === '') {
            $value = 'Premium press-on nails with free shipping. Discover reusable, salon-quality nails at BluLavelle.';
        }
        if (mb_strlen($value) <= 160) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, 157)).'...';
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

    public static function preferredProductSlug(Product $product): string
    {
        $winner = self::duplicateIndex()['redirect'][$product->id] ?? null;

        return is_string($winner) && $winner !== '' ? $winner : (string) $product->slug;
    }

    public static function productHeading(Product $product): string
    {
        $heading = self::duplicateIndex()['heading'][$product->id] ?? null;

        return is_string($heading) && $heading !== '' ? $heading : self::plain((string) $product->name);
    }

    /**
     * @return list<int>
     */
    public static function redirectedProductIds(): array
    {
        return array_map('intval', array_keys(self::duplicateIndex()['redirect']));
    }

    /**
     * @param  list<array{id:int,name:string,slug:string,shop?:string,shop_id?:int|null,price:mixed,description?:string}>  $rows
     * @return array{redirect: array<int, string>, heading: array<int, string>}
     */
    public static function resolveDuplicateProductHeadings(array $rows): array
    {
        $redirect = [];
        $heading = [];
        $groups = [];
        foreach ($rows as $row) {
            $key = self::compareKey((string) ($row['name'] ?? ''));
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($key === '' || $slug === '') {
                continue;
            }
            $row['slug'] = $slug;
            $groups[$key][] = $row;
        }

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }
            $contentGroups = [];
            foreach ($group as $row) {
                $contentKey = implode('|', [
                    (string) ($row['shop_id'] ?? ''),
                    number_format((float) ($row['price'] ?? 0), 2, '.', ''),
                    self::compareKey(strip_tags((string) ($row['description'] ?? ''))),
                ]);
                $contentGroups[$contentKey][] = $row;
            }

            $remaining = [];
            foreach ($contentGroups as $siblings) {
                usort($siblings, fn (array $a, array $b): int => ((int) $a['id']) <=> ((int) $b['id']));
                $winner = $siblings[0];
                $remaining[] = $winner;
                foreach (array_slice($siblings, 1) as $loser) {
                    $redirect[(int) $loser['id']] = (string) $winner['slug'];
                }
            }

            if (count($remaining) < 2) {
                continue;
            }
            usort($remaining, fn (array $a, array $b): int => ((int) $a['id']) <=> ((int) $b['id']));
            $used = [self::compareKey((string) $remaining[0]['name']) => true];
            foreach (array_slice($remaining, 1) as $row) {
                $name = self::plain((string) $row['name']);
                $shop = self::plain((string) ($row['shop'] ?? ''));
                $candidate = $shop !== '' ? $name.' by '.$shop : $name.' ('.$row['slug'].')';
                if (isset($used[self::compareKey($candidate)])) {
                    $candidate = $name.' ('.$row['slug'].')';
                }
                $used[self::compareKey($candidate)] = true;
                $heading[(int) $row['id']] = $candidate;
            }
        }

        return ['redirect' => $redirect, 'heading' => $heading];
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
        $aliases = [
            'contact-us-2' => '/contact-us',
            'order-tracking' => '/track-order',
            'free-return' => '/returns-exchanges-policy',
        ];
        if (isset($aliases[$slug])) {
            return $aliases[$slug];
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
        return self::publicAsset('/images/og/blulavelle-share.png');
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    public static function jsonLdScript(array $nodes): string
    {
        $nodes = array_values(array_filter($nodes));
        if ($nodes === []) {
            return '';
        }

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $nodes],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
        );

        return '<script type="application/ld+json">'.$json.'</script>';
    }

    /**
     * @return array<string, mixed>
     */
    public static function organizationNode(): array
    {
        $home = self::canonicalPath('/');

        return [
            '@type' => 'Organization',
            '@id' => $home.'#organization',
            'name' => 'BluLavelle',
            'url' => $home,
            'logo' => self::defaultShareImage(),
            'email' => 'support@blulavelle.com',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '5900 Balcones Dr Ste 100',
                'addressLocality' => 'Austin',
                'addressRegion' => 'TX',
                'postalCode' => '78731',
                'addressCountry' => 'US',
            ],
            'sameAs' => [
                'https://www.instagram.com/blulavelle/',
                'https://www.facebook.com/profile.php?id=61571564261584',
                'https://www.trustpilot.com/review/blulavelle.com',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function websiteNode(): array
    {
        $home = self::canonicalPath('/');

        return [
            '@type' => 'WebSite',
            '@id' => $home.'#website',
            'url' => $home,
            'name' => 'BluLavelle',
            'publisher' => ['@id' => $home.'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => self::canonicalPath('/search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  array<int, array{name?: string, url?: string}>  $breadcrumbs
     * @return array<int, array<string, mixed>>
     */
    public static function productNodes(Product $product, array $breadcrumbs = []): array
    {
        $url = self::canonicalPath('/products/'.ltrim((string) $product->slug, '/'));
        $name = self::plain((string) $product->name);
        $shop = self::plain((string) ($product->shop?->shop_name ?? ''));
        $description = self::schemaDescription($product);
        $node = [
            '@type' => 'Product',
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'brand' => [
                '@type' => 'Brand',
                'name' => $shop !== '' ? $shop : 'BluLavelle',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => strtoupper(currency()),
                'price' => number_format((float) ($product->price ?? $product->base_price ?? 0), 2, '.', ''),
                'availability' => self::productInStock($product)
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];
        $image = self::productImageUrls($product);
        if ($image !== []) {
            $node['image'] = $image;
        }
        $sku = self::plain((string) ($product->sku ?? ''));
        if ($sku !== '') {
            $node['sku'] = $sku;
        }
        $rating = self::productRating($product);
        if ($rating !== null) {
            $node['aggregateRating'] = $rating;
        }

        if ($breadcrumbs === []) {
            $breadcrumbs = [
                ['name' => 'Home', 'url' => self::canonicalPath('/')],
                ['name' => 'Products', 'url' => self::canonicalPath('/products')],
                ['name' => $name, 'url' => $url],
            ];
        }

        return [$node, self::breadcrumbNode($breadcrumbs, $url)];
    }

    /**
     * @param  array<int, array{name?: string, url?: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbNode(array $items, string $fallbackUrl): array
    {
        $list = [];
        $position = 1;
        $lastIndex = count($items) - 1;
        foreach ($items as $index => $item) {
            $name = self::plain((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $entry = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $name,
            ];
            $itemUrl = trim((string) ($item['url'] ?? ''));
            if ($itemUrl === '' && $index === $lastIndex) {
                $itemUrl = $fallbackUrl;
            }
            if ($itemUrl !== '') {
                $entry['item'] = $itemUrl;
            }
            $list[] = $entry;
            $position++;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
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

    private static function schemaDescription(Product $product): string
    {
        $raw = (string) ($product->description ?? '');
        if (trim($raw) === '' && $product->template) {
            $raw = (string) ($product->template->description ?? '');
        }
        $raw = self::plain(strip_tags($raw));
        if ($raw === '') {
            return self::productDescription($product);
        }

        return Str::limit($raw, 500, '…');
    }

    /**
     * @return array<int, string>
     */
    private static function productImageUrls(Product $product): array
    {
        $urls = [];
        foreach ($product->getMergedDisplayMedia() as $item) {
            $url = self::mediaUrl($item);
            if ($url === null || in_array($url, $urls, true)) {
                continue;
            }
            $urls[] = $url;
            if (count($urls) >= 4) {
                break;
            }
        }

        return $urls;
    }

    private static function productInStock(Product $product): bool
    {
        if ((int) $product->quantity > 0) {
            return true;
        }
        if ($product->relationLoaded('variants')) {
            return $product->variants->contains(fn ($variant) => (int) $variant->quantity > 0);
        }
        if (! $product->exists) {
            return false;
        }

        return $product->hasStock();
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function productRating(Product $product): ?array
    {
        if (! $product->exists) {
            return null;
        }

        $count = (int) $product->approvedReviews()->count();
        if ($count < 1) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format(round((float) $product->approvedReviews()->avg('rating'), 1), 1, '.', ''),
            'reviewCount' => (string) $count,
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }

    /**
     * @return array{redirect: array<int, string>, heading: array<int, string>}
     */
    private static function duplicateIndex(): array
    {
        $index = Cache::remember('seo.duplicate-product-headings.v1', 3600, function (): array {
            $rows = Product::query()
                ->availableForDisplay()
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->with('shop:id,shop_name')
                ->get(['id', 'name', 'slug', 'shop_id', 'price', 'description'])
                ->map(fn (Product $product): array => [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'slug' => (string) $product->slug,
                    'shop_id' => $product->shop_id,
                    'shop' => (string) ($product->shop?->shop_name ?? ''),
                    'price' => $product->price,
                    'description' => (string) $product->description,
                ])
                ->all();

            return self::resolveDuplicateProductHeadings($rows);
        });

        return is_array($index) ? $index : ['redirect' => [], 'heading' => []];
    }

    private static function compareKey(string $value): string
    {
        return mb_strtolower(self::plain($value));
    }

    private static function plain(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value;
    }
}
