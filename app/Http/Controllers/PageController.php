<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display the specified page
     */
    public function show(string $slug)
    {
        // Redirect to dedicated routes
        if ($slug === 'shipping-delivery') {
            return redirect()->route('shipping-delivery.index');
        }
        if ($slug === 'sizing-kit') {
            return redirect()->route('sizing-kit.index');
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

        return view('pages.show', compact('page', 'childPages'));
    }
}
