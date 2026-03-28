@extends('layouts.admin')

@section('title', 'Collections Management')

@section('content')
<div class="space-y-6 w-full max-w-full overflow-x-hidden">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                @if(auth()->user()->hasRole('admin'))
                    📚 Collections Management
                @else
                    📚 My Collections
                @endif
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                @if(auth()->user()->hasRole('admin'))
                    Review and approve collections from all shops
                @else
                    Manage your collections and use admin collections to organize your products better
                @endif
            </p>
            <p class="mt-2 text-sm">
                <a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium underline underline-offset-2">
                    Thêm nhiều sản phẩm vào collection cùng lúc
                </a>
                <span class="text-gray-500">— vào Products, chọn ô bên trái rồi «Thêm vào collection».</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-4">
            <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-semibold">
                {{ $collections->total() }} collections
            </span>
            <a href="{{ route('admin.collections.create') }}" 
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 shadow-lg transition transform hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Collection
            </a>
        </div>
    </div>
    
    <!-- Collections Grid -->
    @if($collections->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($collections as $collection)
        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 group">
            <!-- Collection Image -->
            <div class="relative h-48 bg-gradient-to-br from-purple-100 to-indigo-100 overflow-hidden">
                @if($collection->image)
                    <img src="{{ $collection->image }}" alt="{{ $collection->name }}" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <div class="text-6xl text-purple-300">📚</div>
                    </div>
                @endif
                
                <!-- Status Badges -->
                <div class="absolute top-3 left-3 flex flex-col space-y-2">
                    @if($collection->featured)
                        <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">
                            ⭐ Featured
                        </span>
                    @endif
                    
                    @if(auth()->user()->hasRole('admin'))
                        @if($collection->admin_approved)
                            <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                ✅ Approved
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-semibold">
                                ⏳ Pending
                            </span>
                        @endif
                    @else
                        @if($collection->admin_approved)
                            <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                ✅ Approved
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-semibold">
                                ⏳ Pending Approval
                            </span>
                        @endif
                    @endif
                    
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                        {{ $collection->status === 'active' ? 'bg-blue-100 text-blue-800' : 
                           ($collection->status === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                        {{ ucfirst($collection->status) }}
                    </span>
                </div>
                
                <!-- Type Badge -->
                <div class="absolute top-3 right-3">
                    <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                        {{ $collection->type === 'manual' ? '📝 Manual' : '🤖 Auto' }}
                    </span>
                </div>
            </div>
            
            <!-- Collection Info -->
            <div class="p-6">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600 transition-colors">
                            {{ $collection->name }}
                        </h3>
                        <p class="text-sm text-gray-500">{{ $collection->slug }}</p>
                        @if(!auth()->user()->hasRole('admin') && optional($collection->user)->hasRole('admin'))
                            <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                Admin Collection
                            </span>
                        @endif
                    </div>
                    @if(auth()->user()->hasRole('admin'))
                        <div class="flex items-center space-x-2 text-xs text-gray-400">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                {{ substr($collection->user->name, 0, 1) }}
                            </div>
                            @if($collection->shop)
                                <span class="text-xs text-gray-500">({{ $collection->shop->shop_name }})</span>
                            @endif
                        </div>
                    @endif
                </div>
                
                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                    {{ $collection->description ?: 'No description provided.' }}
                </p>
                
                <!-- Stats -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            {{ $collection->active_products_count }} products
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $collection->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('admin.collections.show', $collection) }}" 
                           class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-xs font-semibold transition">
                            👁️ View
                        </a>
                        @if($collection->canEdit())
                            <a href="{{ route('admin.collections.edit', $collection) }}" 
                               class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-xs font-semibold transition">
                                ✏️ Edit
                            </a>
                        @endif
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        @if(auth()->user()->hasRole('admin') && !$collection->admin_approved)
                            <!-- Admin Approval Actions -->
                            <form action="{{ route('admin.collections.approve', $collection) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="p-1.5 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition"
                                        title="Approve Collection">
                                    ✅
                                </button>
                            </form>
                            
                            <button onclick="openRejectModal({{ $collection->id }}, '{{ $collection->name }}')" 
                                    class="p-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition"
                                    title="Reject Collection">
                                ❌
                            </button>
                        @endif
                        
                        @if($collection->canEdit())
                            <form action="{{ route('admin.collections.toggle-featured', $collection) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="p-1.5 rounded-lg transition {{ $collection->featured ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                    ⭐
                                </button>
                            </form>
                            
                            @if($collection->canDelete())
                                <form action="{{ route('admin.collections.destroy', $collection) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this collection?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                                        🗑️
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    
    <!-- Pagination -->
    @if($collections->hasPages())
        <div class="bg-white px-6 py-4 rounded-xl shadow-md">
            {{ $collections->links() }}
        </div>
    @endif
    @else
        <div class="bg-white rounded-xl p-16 text-center">
            <div class="w-24 h-24 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-12 h-12 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No collections found</h3>
            <p class="text-gray-500 mb-6">Start organizing your products by creating your first collection</p>
            <a href="{{ route('admin.collections.create') }}" 
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 shadow-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create First Collection
            </a>
        </div>
    @endif
</div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <form id="rejectForm" method="POST">
                    @csrf
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Collection</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Please provide a reason for rejecting the collection "<span id="collectionName"></span>":
                        </p>
                        <textarea name="admin_notes" rows="4" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                  placeholder="Reason for rejection..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 p-6 bg-gray-50 rounded-b-xl">
                        <button type="button" onclick="closeRejectModal()" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            Reject Collection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
function openRejectModal(collectionId, collectionName) {
    document.getElementById('collectionName').textContent = collectionName;
    document.getElementById('rejectForm').action = `/admin/collections/${collectionId}/reject`;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectForm').reset();
}
</script>
@endsection
