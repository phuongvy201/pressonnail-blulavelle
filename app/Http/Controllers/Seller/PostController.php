<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Admin xem tất cả posts, Seller chỉ xem của mình
        $query = Post::with(['category', 'tags', 'shop', 'user']);

        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        // Filter by status (Admin only)
        if ($user->hasRole('admin') && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        $posts = $query->latest()->paginate(20)->withQueryString();

        return view('seller.posts.index', compact('posts'));
    }

    /**
     * Approve post (Admin only)
     */
    public function approve(Post $post)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $post->update([
            'status' => 'published',
            'published_at' => $post->published_at ?? now(), // Set published_at nếu chưa có
        ]);

        return back()->with('success', 'Post approved and published successfully!');
    }

    /**
     * Reject post (Admin only)
     */
    public function reject(Post $post)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $post->update(['status' => 'draft']);

        return back()->with('success', 'Post rejected and set to draft.');
    }

    public function create()
    {
        $categories = PostCategory::orderBy('name')->get();
        $tags = PostTag::orderBy('name')->get();
        $shop = auth()->user()->shop;

        return view('seller.posts.create', compact('categories', 'tags', 'shop'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string',
                'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'gallery.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'post_category_id' => 'nullable|exists:post_categories,id',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:post_tags,id',
                'status' => 'nullable|in:published,draft,scheduled',
                'published_at' => 'nullable|date',
                'type' => 'required|in:article,video,gallery,product_review',
                'featured' => 'boolean',
                'allow_comments' => 'boolean',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'meta_keywords' => 'nullable|string',
            ]);

            $shop = auth()->user()->shop;
            if (!$shop && !auth()->user()->hasRole('admin')) {
                return back()
                    ->withInput($request->all())
                    ->with('error', 'Bạn cần có shop để tạo bài viết. Vui lòng tạo shop trước.');
            }

            $validated['user_id'] = auth()->id();
            $validated['shop_id'] = $shop?->id;

            try {
                $validated['slug'] = Post::generateSlug($validated['title']);
            } catch (\Exception $e) {
                return back()
                    ->withInput($request->all())
                    ->with('error', 'Không thể tạo slug cho tiêu đề. Vui lòng thử lại với tiêu đề khác.');
            }

            $validated['featured'] = $request->has('featured');
            $validated['allow_comments'] = $request->has('allow_comments');

            // Set default status to published if not provided or empty
            if (!$request->has('status') || empty($validated['status'])) {
                $validated['status'] = 'published';
            }

            // Set published_at to now if not provided and status is published
            if (!isset($validated['published_at']) && $validated['status'] === 'published') {
                $validated['published_at'] = now();
            }

            // Handle featured image upload to S3
            if ($request->hasFile('featured_image')) {
                try {
                    $imageFile = $request->file('featured_image');
                    $imageName = 'post_featured_' . time() . '_' . Str::random(10) . '.' . $imageFile->getClientOriginalExtension();
                    $imagePath = 'posts/featured/' . $imageName;
                    Storage::disk('s3')->put($imagePath, file_get_contents($imageFile));
                    $validated['featured_image'] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $imagePath;
                } catch (\Exception $e) {
                    return back()
                        ->withInput($request->all())
                        ->with('error', 'Không thể tải lên hình ảnh đại diện. Vui lòng thử lại.');
                }
            }

            // Handle gallery upload to S3
            if ($request->hasFile('gallery')) {
                try {
                    $galleryPaths = [];
                    foreach ($request->file('gallery') as $image) {
                        $imageName = 'post_gallery_' . time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                        $imagePath = 'posts/gallery/' . $imageName;
                        Storage::disk('s3')->put($imagePath, file_get_contents($image));
                        $galleryPaths[] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $imagePath;
                    }
                    $validated['gallery'] = $galleryPaths;
                } catch (\Exception $e) {
                    return back()
                        ->withInput($request->all())
                        ->with('error', 'Không thể tải lên một số hình ảnh trong thư viện. Vui lòng thử lại.');
                }
            }

            // Create post
            try {
                $post = Post::create($validated);
            } catch (\Exception $e) {
                return back()
                    ->withInput($request->all())
                    ->with('error', 'Không thể tạo bài viết. Vui lòng kiểm tra lại thông tin và thử lại.');
            }

            // Attach tags
            try {
                if ($request->has('tags')) {
                    $post->tags()->attach($request->tags);
                }
            } catch (\Exception $e) {
                // Tags attachment failed but post was created, log error but don't fail
                Log::warning('Failed to attach tags to post: ' . $e->getMessage());
            }

            // Calculate reading time
            try {
                $post->updateReadingTime();
            } catch (\Exception $e) {
                // Reading time calculation failed but post was created, log error but don't fail
                Log::warning('Failed to calculate reading time for post: ' . $e->getMessage());
            }

            return redirect()->route('admin.posts.index')
                ->with('success', 'Bài viết đã được tạo thành công!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Laravel validation errors are automatically handled
            throw $e;
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error creating post: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token'])
            ]);

            return back()
                ->withInput($request->all())
                ->with('error', 'Đã xảy ra lỗi không mong muốn. Vui lòng thử lại sau.');
        }
    }

    public function edit(Post $post)
    {
        // Check if user can edit this post
        if ($post->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $categories = PostCategory::orderBy('name')->get();
        $tags = PostTag::orderBy('name')->get();
        $shop = auth()->user()->shop;

        return view('seller.posts.edit', compact('post', 'categories', 'tags', 'shop'));
    }

    public function update(Request $request, Post $post)
    {
        // Check if user can edit this post
        if ($post->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'post_category_id' => 'nullable|exists:post_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:post_tags,id',
            'status' => 'required|in:published,draft,scheduled',
            'published_at' => 'nullable|date',
            'type' => 'required|in:article,video,gallery,product_review',
            'featured' => 'boolean',
            'allow_comments' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        if ($validated['title'] !== $post->title) {
            $validated['slug'] = Post::generateSlug($validated['title'], $post->id);
        }

        $validated['featured'] = $request->has('featured');
        $validated['allow_comments'] = $request->has('allow_comments');

        // Set published_at to now if not provided and status is published
        if (!isset($validated['published_at']) && $validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        // Set status to pending if seller is publishing
        if ($validated['status'] === 'published' && !auth()->user()->hasRole('admin') && $post->status !== 'published') {
            $validated['status'] = 'pending';
        }

        // Handle featured image update - upload to S3
        if ($request->hasFile('featured_image')) {
            // Delete old image from S3 if exists
            if ($post->featured_image && str_contains($post->featured_image, 's3.us-east-1.amazonaws.com')) {
                $oldPath = str_replace('https://s3.us-east-1.amazonaws.com/image.bluprinter/', '', $post->featured_image);
                Storage::disk('s3')->delete($oldPath);
            }

            $imageFile = $request->file('featured_image');
            $imageName = 'post_featured_' . time() . '_' . Str::random(10) . '.' . $imageFile->getClientOriginalExtension();
            $imagePath = 'posts/featured/' . $imageName;
            Storage::disk('s3')->put($imagePath, file_get_contents($imageFile));
            $validated['featured_image'] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $imagePath;
        }

        // Handle gallery update - upload to S3
        if ($request->hasFile('gallery')) {
            // Delete old gallery images from S3
            if ($post->gallery) {
                foreach ($post->gallery as $image) {
                    if (str_contains($image, 's3.us-east-1.amazonaws.com')) {
                        $oldPath = str_replace('https://s3.us-east-1.amazonaws.com/image.bluprinter/', '', $image);
                        Storage::disk('s3')->delete($oldPath);
                    }
                }
            }

            $galleryPaths = [];
            foreach ($request->file('gallery') as $image) {
                $imageName = 'post_gallery_' . time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $imagePath = 'posts/gallery/' . $imageName;
                Storage::disk('s3')->put($imagePath, file_get_contents($image));
                $galleryPaths[] = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $imagePath;
            }
            $validated['gallery'] = $galleryPaths;
        }

        $post->update($validated);

        // Sync tags
        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        } else {
            $post->tags()->detach();
        }

        // Update reading time
        $post->updateReadingTime();

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post updated successfully!');
    }

    public function destroy(Post $post)
    {
        // Check if user can delete this post
        if ($post->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        // Delete featured image from S3
        if ($post->featured_image && str_contains($post->featured_image, 's3.us-east-1.amazonaws.com')) {
            $oldPath = str_replace('https://s3.us-east-1.amazonaws.com/image.bluprinter/', '', $post->featured_image);
            Storage::disk('s3')->delete($oldPath);
        }

        // Delete gallery images from S3
        if ($post->gallery) {
            foreach ($post->gallery as $image) {
                if (str_contains($image, 's3.us-east-1.amazonaws.com')) {
                    $oldPath = str_replace('https://s3.us-east-1.amazonaws.com/image.bluprinter/', '', $image);
                    Storage::disk('s3')->delete($oldPath);
                }
            }
        }

        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post deleted successfully!');
    }

    /**
     * Upload image for TinyMCE editor - upload to S3
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:2048'
        ]);

        if ($request->hasFile('file')) {
            $imageFile = $request->file('file');
            $imageName = 'post_editor_' . time() . '_' . Str::random(10) . '.' . $imageFile->getClientOriginalExtension();
            $imagePath = 'posts/editor/' . $imageName;
            Storage::disk('s3')->put($imagePath, file_get_contents($imageFile));
            $url = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/' . $imagePath;

            return response()->json([
                'location' => $url
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
