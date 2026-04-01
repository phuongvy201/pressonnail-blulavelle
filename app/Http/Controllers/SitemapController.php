<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    private const CACHE_KEY = 'http.sitemap.xml.v3';

    public function index(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, 3600, fn () => $this->buildXml());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function buildXml(): string
    {
        $parts = [];
        $parts[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $parts[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $add = function (string $loc, $lastmod = null, string $changefreq = 'weekly', string $priority = '0.5') use (&$parts): void {
            if ($loc === '') {
                return;
            }
            $parts[] = '  <url>';
            $parts[] = '    <loc>'.htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';
            if ($lastmod instanceof \DateTimeInterface) {
                $parts[] = '    <lastmod>'.$lastmod->format('Y-m-d').'</lastmod>';
            }
            $parts[] = '    <changefreq>'.htmlspecialchars($changefreq, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</changefreq>';
            $parts[] = '    <priority>'.htmlspecialchars($priority, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</priority>';
            $parts[] = '  </url>';
        };

        $addRoute = function (string $name, array $parameters = [], $lastmod = null, string $changefreq = 'weekly', string $priority = '0.5') use ($add): void {
            if (! Route::has($name)) {
                return;
            }
            $add(route($name, $parameters), $lastmod, $changefreq, $priority);
        };

        // --- Home & catalog hubs ---
        $addRoute('home', [], now(), 'daily', '1.0');
        $addRoute('products.index', [], now(), 'daily', '0.95');
        $addRoute('search', [], now(), 'weekly', '0.55');

        // --- 1) Collections: index + từng trang collection ---
        $addRoute('collections.index', [], now(), 'weekly', '0.88');
        Collection::query()
            ->active()
            ->approved()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('id')
            ->each(function (Collection $collection) use ($add): void {
                $add(route('collections.show', $collection->slug), $collection->updated_at, 'weekly', '0.86');
            });

        // --- 2) Product detail pages (ưu tiên SEO cao nhất trong nội dung sản phẩm) ---
        Product::query()
            ->availableForDisplay()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($products) use ($add): void {
                foreach ($products as $product) {
                    $add(route('products.show', $product->slug), $product->updated_at, 'daily', '0.98');
                }
            });

        // --- 3) Category (danh mục sản phẩm) ---
        Category::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('id')
            ->each(function (Category $category) use ($add): void {
                $add(route('category.show', $category->slug), $category->updated_at, 'weekly', '0.72');
            });

        // --- 4) Trang tĩnh: route cố định + CMS Page (policy, terms, privacy, v.v.) ---
        $addRoute('about', [], now(), 'monthly', '0.7');
        $addRoute('contact', [], now(), 'monthly', '0.7');
        $addRoute('shipping-delivery.index', [], now(), 'monthly', '0.65');
        $addRoute('sizing-kit.index', [], now(), 'monthly', '0.65');
        $addRoute('orders.track', [], now(), 'monthly', '0.45');
        $addRoute('bulk.order.create', [], now(), 'monthly', '0.45');
        $addRoute('seller.apply', [], now(), 'monthly', '0.5');

        Page::query()
            ->published()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereNotIn('slug', ['shipping-delivery', 'sizing-kit'])
            ->orderBy('id')
            ->each(function (Page $page) use ($add): void {
                $mod = $page->updated_at ?? $page->published_at;
                $add(route('page.show', $page->slug), $mod, 'monthly', '0.68');
            });

        // --- 5) Landing / campaign / social proof ---
        $addRoute('reviews.public', [], now(), 'weekly', '0.75');
        $addRoute('promo.offer', [], now(), 'weekly', '0.62');
        $addRoute('promo.code.create', [], now(), 'weekly', '0.58');
        $addRoute('support.ticket.create', [], now(), 'monthly', '0.5');
        $addRoute('support.request.create', [], now(), 'monthly', '0.5');

        // --- Blog (nội dung / seasonal có thể gắn qua bài viết) ---
        $addRoute('blog.index', [], now(), 'weekly', '0.78');
        Post::query()
            ->published()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('id')
            ->each(function (Post $post) use ($add): void {
                $mod = $post->updated_at ?? $post->published_at;
                $add(route('blog.show', $post->slug), $mod, 'monthly', '0.64');
            });

        PostCategory::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereHas('posts', fn ($q) => $q->published())
            ->orderBy('id')
            ->each(function (PostCategory $cat) use ($add): void {
                $add(route('blog.category', $cat->slug), $cat->updated_at, 'weekly', '0.52');
            });

        PostTag::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where('posts_count', '>', 0)
            ->orderBy('id')
            ->each(function (PostTag $tag) use ($add): void {
                $add(route('blog.tag', $tag->slug), $tag->updated_at, 'weekly', '0.48');
            });

        // --- Shops ---
        Shop::query()
            ->active()
            ->whereNotNull('shop_slug')
            ->where('shop_slug', '!=', '')
            ->orderBy('id')
            ->each(function (Shop $shop) use ($add): void {
                $add(route('shops.show', $shop->shop_slug), $shop->updated_at, 'weekly', '0.62');
            });

        $parts[] = '</urlset>';

        return implode("\n", $parts);
    }
}
