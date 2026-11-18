<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Seo
{
    public const TITLE_LIMIT = 60;

    private const BRAND_SUFFIX = ' | BluLavelle';

    public static function documentTitle(string $metaTitle, string $sectionTitle, string $viewTitle): string
    {
        foreach ([$metaTitle, $sectionTitle, $viewTitle] as $candidate) {
            $candidate = self::plain($candidate);
            if ($candidate === '') {
                continue;
            }
            if (! str_contains(mb_strtolower($candidate), 'blulavelle')) {
                $candidate .= self::BRAND_SUFFIX;
            }

            return self::clampTitle($candidate);
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
        if ($name === '') {
            return 'Press-On Nails'.self::BRAND_SUFFIX;
        }

        $shop = self::plain((string) ($product->shop?->shop_name ?? ''));
        if ($shop !== '') {
            $withShop = $name.' | '.$shop.self::BRAND_SUFFIX;
            if (mb_strlen($withShop) <= self::TITLE_LIMIT) {
                return $withShop;
            }
        }

        return self::clampTitle($name.self::BRAND_SUFFIX);
    }

    public static function listingTitle(string $name): string
    {
        $name = self::plain($name);
        if ($name === '' || str_contains(mb_strtolower($name), 'blulavelle')) {
            return self::clampTitle($name);
        }
        if (mb_strlen($name.self::BRAND_SUFFIX) >= 30) {
            return $name;
        }
        if (! preg_match('/press-?on/i', $name)) {
            $expanded = $name.' Press-On Nails';
            if (mb_strlen($expanded.self::BRAND_SUFFIX) <= self::TITLE_LIMIT) {
                return $expanded;
            }
        }

        return $name;
    }

    public static function clampTitle(string $title): string
    {
        $title = self::plain($title);
        if (mb_strlen($title) <= self::TITLE_LIMIT) {
            return $title;
        }

        $suffix = '';
        $body = $title;
        if (str_ends_with(mb_strtolower($title), mb_strtolower(self::BRAND_SUFFIX))) {
            $suffix = self::BRAND_SUFFIX;
            $body = rtrim(mb_substr($title, 0, mb_strlen($title) - mb_strlen($suffix)));
        }

        $room = self::TITLE_LIMIT - mb_strlen($suffix) - 1;
        if ($room < 8) {
            return rtrim(mb_substr($title, 0, self::TITLE_LIMIT - 1)).'…';
        }

        $slice = rtrim(mb_substr($body, 0, $room));
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space >= (int) floor($room * 0.6)) {
            $slice = rtrim(mb_substr($slice, 0, $space));
        }
        if ($slice === '') {
            $slice = rtrim(mb_substr($body, 0, $room));
        }

        return $slice.'…'.$suffix;
    }

    public static function catalogDescription(string $key): string
    {
        return self::catalogDescriptions()[$key] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public static function catalogDescriptions(): array
    {
        return [
            'products' => 'Shop every handmade press-on nail set on BluLavelle. Filter by artist, shape, and style, then size your reusable set at home.',
            'contact' => 'Contact BluLavelle about an order, a sizing kit, or a handmade set. Email support@blulavelle.com or call +1 205-389-9573.',
            'sizing-kit' => 'Order a BluLavelle sizing kit and match each nail before you buy a set. The kit helps you find a custom fit for reusable press-on nails.',
            'become-a-seller' => 'Apply to sell handmade press-on nails on BluLavelle. Independent artists get a shop page, product tools, and orders fulfilled from our warehouses.',
            'reviews' => 'Read customer reviews of handmade press-on nails on BluLavelle. See notes on fit, wear, and the artists who made the reusable sets.',
            'collections' => 'Browse BluLavelle press-on nail collections, from stained glass to seasonal art. Each collection is handmade by an independent artist.',
            'shops' => 'Meet the independent press-on nail artists on BluLavelle and shop their handmade reusable sets, sized at home with the sizing kit.',
            'sitemap' => 'Browse the BluLavelle sitemap for help, shipping, returns, collections, categories, nail artist shops, and the press-on nail catalog.',
            'about-us' => 'BluLavelle sells handmade stained-glass and custom-sized press-on nails from independent artists, shipped from our US and UK warehouses.',
            'affiliate-attribution-cookie-policy' => 'How BluLavelle credits an affiliate sale: the last creator link wins, how long the cookie lasts, and what happens when a shopper clicks two links.',
            'affiliate-commission-payout-policy' => 'BluLavelle affiliate commission tiers, how a qualifying sale is counted, when payouts are sent, and which orders are left out of commission.',
            'affiliate-privacy-policy' => 'Privacy practices for the BluLavelle creator portal: what we collect from affiliates, how long we keep it, and how to ask for a correction or deletion.',
            'affiliate-program-terms' => 'Rules for BluLavelle creators and affiliates, including honest promotion, banned claims, when a commission counts, and how the program can end.',
            'help-center' => 'BluLavelle Help Center for sizing, shipping times, order tracking, 30-day returns, and how to reach support about a handmade press-on order.',
            'secure-payments' => 'How BluLavelle protects checkout: encrypted pages, card handling built for PCI rules, fraud checks, and the payment methods accepted on an order.',
        ];
    }

    public static function pageDescription(string $slug, string $existing = ''): string
    {
        $catalog = self::catalogDescription($slug);
        if ($catalog !== '') {
            return self::descriptionOrFallback($existing, $catalog);
        }

        return self::plain(strip_tags($existing));
    }

    public static function collectionDescription(string $name, string $existing = ''): string
    {
        $fallback = 'Shop the '.self::plain($name).' press-on nail collection at BluLavelle. Handmade reusable sets from independent artists, with a sizing kit for a custom fit.';

        return self::descriptionOrFallback($existing, $fallback);
    }

    public static function categoryDescription(string $name, string $existing = ''): string
    {
        $fallback = 'Shop '.self::plain($name).' press-on nails at BluLavelle. Handmade reusable sets from independent artists, with a sizing kit so you can match the fit at home.';

        return self::descriptionOrFallback($existing, $fallback);
    }

    public static function shopDescription(string $name, string $existing = ''): string
    {
        $existing = self::plain(strip_tags($existing));
        if (mb_strlen($existing) >= 120) {
            return $existing;
        }

        $fallback = self::plain($name).' designs handmade press-on nails on BluLavelle. Browse reusable sets from this independent artist and size them at home with the kit.';
        if ($existing === '') {
            return $fallback;
        }

        $combined = rtrim($existing, '.').'. Browse handmade reusable press-on sets from this independent artist, sized at home with the kit.';
        if (mb_strlen($combined) >= 120) {
            return $combined;
        }

        return $fallback;
    }

    public static function descriptionOrFallback(string $value, string $fallback): string
    {
        $value = self::plain(strip_tags($value));
        if (mb_strlen($value) >= 120) {
            return $value;
        }

        return self::plain($fallback);
    }

    public static function demoteExtraHeadings(string $html): string
    {
        $html = preg_replace('/<h1(\s[^>]*)?>/i', '<h2$1>', $html) ?? $html;

        return preg_replace('/<\/h1>/i', '</h2>', $html) ?? $html;
    }

    public static function rootRelativeHrefs(string $html): string
    {
        $rewritten = preg_replace_callback(
            '/\bhref=(["\'])([^"\']*)\1/i',
            static function (array $match): string {
                $href = trim($match[2]);
                if ($href === '' || preg_match('~^(#|\?|/|mailto:|tel:|javascript:|https?:|data:)~i', $href)) {
                    return $match[0];
                }

                return 'href='.$match[1].'/'.ltrim($href, '/').$match[1];
            },
            $html
        );

        return $rewritten ?? $html;
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
