<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of posts
     */
    public function index(Request $request)
    {
        $query = Post::with(['category', 'tags', 'shop', 'user'])
            ->published();

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by tag
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // Filter by shop
        if ($request->filled('shop')) {
            $query->where('shop_id', $request->shop);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            case 'trending':
                $query->orderBy('likes', 'desc');
                break;
            case 'oldest':
                $query->oldest('published_at');
                break;
            case 'latest':
            default:
                $query->latest('published_at');
                break;
        }

        // Sticky posts first
        $query->orderBy('sticky', 'desc');

        $posts = $query->paginate(12)->withQueryString();

        // Get featured posts for sidebar
        $featuredPosts = Post::with(['category', 'shop'])
            ->published()
            ->featured()
            ->latest('published_at')
            ->limit(5)
            ->get();

        // Get categories with post counts
        $categories = PostCategory::withCount(['posts' => function ($q) {
            $q->published();
        }])
            ->having('posts_count', '>', 0)
            ->orderBy('name')
            ->get();

        // Get popular tags
        $popularTags = PostTag::where('posts_count', '>', 0)
            ->orderBy('posts_count', 'desc')
            ->limit(20)
            ->get();

        return view('posts.index', compact('posts', 'featuredPosts', 'categories', 'popularTags'));
    }

    /**
     * Display the specified post
     */
    public function show(string $slug)
    {
        $post = Post::with(['category', 'tags', 'shop', 'user'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Increment views
        $post->incrementViews();

        // Get related posts
        $relatedPosts = Post::with(['category', 'shop'])
            ->published()
            ->where('id', '!=', $post->id)
            ->when($post->post_category_id, function ($q) use ($post) {
                $q->where('post_category_id', $post->post_category_id);
            })
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('posts.show', compact('post', 'relatedPosts'));
    }

    /**
     * Display posts by category
     */
    public function category(string $slug)
    {
        $category = PostCategory::where('slug', $slug)->firstOrFail();

        $posts = Post::with(['category', 'tags', 'shop'])
            ->published()
            ->where('post_category_id', $category->id)
            ->latest('published_at')
            ->paginate(12);

        return view('posts.category', compact('category', 'posts'));
    }

    /**
     * Display posts by tag
     */
    public function tag(string $slug)
    {
        $tag = PostTag::where('slug', $slug)->firstOrFail();

        $posts = $tag->publishedPosts()
            ->with(['category', 'shop'])
            ->paginate(12);

        return view('posts.tag', compact('tag', 'posts'));
    }
}
