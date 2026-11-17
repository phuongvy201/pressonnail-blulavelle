<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Shop;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display the specified page
     */
    public function show(?string $slug = null)
    {
        $slug = trim((string) $slug);
        if ($slug === '' || str_contains($slug, '/')) {
            abort(404);
        }

        // Redirect to dedicated routes
        if ($slug === 'shipping-delivery') {
            return redirect()->route('shipping-delivery.index', status: 301);
        }
        if ($slug === 'sizing-kit') {
            return redirect()->route('sizing-kit.index', status: 301);
        }
        if ($slug === 'contact-us-2') {
            return redirect()->route('pages.show', 'contact-us', status: 301);
        }
        if ($slug === 'order-tracking') {
            return redirect()->route('orders.track', status: 301);
        }
        if ($slug === 'free-return') {
            return redirect()->route('pages.show', 'returns-exchanges-policy', status: 301);
        }

        $page = Page::where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Increment views
        $page->incrementViews();

        // Get child pages if any
        $childPages = $page->children()
            ->published()
            ->get();

        if ($slug === 'sitemap') {
            $directory = [
                'pages' => Page::query()->published()->orderBy('title')->get(['title', 'slug']),
                'collections' => Collection::query()->active()->approved()->orderBy('name')->get(['name', 'slug']),
                'categories' => Category::query()->whereNotNull('slug')->where('slug', '!=', '')->orderBy('name')->get(['name', 'slug']),
                'shops' => Shop::query()->active()->whereNotNull('shop_slug')->where('shop_slug', '!=', '')->orderBy('shop_name')->get(['shop_name', 'shop_slug']),
            ];

            return view('pages.directory', compact('page', 'directory'));
        }

        return view('pages.show', compact('page', 'childPages'));
    }
}
