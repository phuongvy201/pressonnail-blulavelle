@extends('layouts.admin')

@section('title', auth()->user()->hasRole('admin') ? 'All Blog Posts' : 'My Posts')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                @if(auth()->user()->hasRole('admin'))
                    All Blog Posts
                    <span class="text-base font-normal text-gray-500">({{ $posts->total() }} total)</span>
                @else
                    My Blog Posts
                @endif
            </h1>
            @if(auth()->user()->hasRole('admin'))
                <p class="text-gray-600 mt-1">Manage and approve posts from all sellers</p>
            @else
                <p class="text-gray-600 mt-1">
                    Your posts need admin approval before appearing on the website
                    @php
                        $pendingCount = Auth::user()->posts()->where('status', 'pending')->count();
                    @endphp
                    @if($pendingCount > 0)
                        <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                            {{ $pendingCount }} pending approval
                        </span>
                    @endif
                </p>
            @endif
        </div>
        <a href="{{ route('admin.posts.create') }}" class="px-6 py-3 bg-[#F0427C] text-white rounded-lg hover:bg-[#d6386a] transition">
            + New Post
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(auth()->user()->hasRole('admin'))
        <!-- Status Filter Tabs for Admin -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-4 px-6" aria-label="Tabs">
                    <a href="{{ route('admin.posts.index') }}" 
                       class="py-4 px-1 border-b-2 font-medium text-sm {{ !request('status') ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        All ({{ $posts->total() }})
                    </a>
                    <a href="{{ route('admin.posts.index', ['status' => 'pending']) }}" 
                       class="py-4 px-1 border-b-2 font-medium text-sm {{ request('status') == 'pending' ? 'border-yellow-600 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        Pending Approval
                    </a>
                    <a href="{{ route('admin.posts.index', ['status' => 'published']) }}" 
                       class="py-4 px-1 border-b-2 font-medium text-sm {{ request('status') == 'published' ? 'border-green-600 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        Published
                    </a>
                    <a href="{{ route('admin.posts.index', ['status' => 'draft']) }}" 
                       class="py-4 px-1 border-b-2 font-medium text-sm {{ request('status') == 'draft' ? 'border-gray-600 text-gray-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        Drafts
                    </a>
                </nav>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    @if(auth()->user()->hasRole('admin'))
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                    @endif
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stats</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($posts as $post)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($post->featured_image)
                                    <img src="{{ $post->featured_image_url }}" alt="" class="w-12 h-12 object-cover rounded mr-3">
                                @endif
                                <div>
                                    <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="font-medium text-gray-900 hover:text-[#005366]">
                                        {{ Str::limit($post->title, 50) }}
                                    </a>
                                    @if($post->featured)<span class="ml-2 text-xs text-yellow-600">★ Featured</span>@endif
                                    @if($post->sticky)<span class="ml-2 text-xs text-blue-600">📌 Sticky</span>@endif
                                </div>
                            </div>
                        </td>
                        @if(auth()->user()->hasRole('admin'))
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <p class="font-medium text-gray-900">{{ $post->user->name }}</p>
                                    @if($post->shop)
                                        <p class="text-xs text-gray-500">{{ $post->shop->shop_name }}</p>
                                    @endif
                                </div>
                            </td>
                        @endif
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $post->category->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $post->status === 'published' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $post->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $post->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $post->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : '' }}">
                                {{ ucfirst($post->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="space-y-1">
                                <div>👁️ {{ number_format($post->views) }}</div>
                                <div>❤️ {{ number_format($post->likes) }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $post->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if(auth()->user()->hasRole('admin') && $post->status === 'pending')
                                    <form action="{{ route('admin.posts.approve', $post) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-800 font-semibold">✓ Approve</button>
                                    </form>
                                    <form action="{{ route('admin.posts.reject', $post) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:text-orange-800 font-semibold">✕ Reject</button>
                                    </form>
                                    <span class="text-gray-300">|</span>
                                @endif
                                <a href="{{ route('admin.posts.edit', $post) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                                <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $posts->links() }}
    </div>
</div>
@endsection

