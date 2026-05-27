<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class CreatorPolicyPageController extends Controller
{
    public function show(string $slug): View
    {
        $allowed = config('creator.affiliate_policy_slugs', []);
        if (! in_array($slug, $allowed, true)) {
            abort(404);
        }

        $page = Page::query()
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        $page->incrementViews();

        return view('creator.pages.show', compact('page'));
    }
}
