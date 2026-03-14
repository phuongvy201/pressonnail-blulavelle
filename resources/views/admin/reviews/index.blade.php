@extends('layouts.admin')

@section('title', 'Danh sách Reviews')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Danh sách Reviews</h1>
            <p class="mt-1 text-sm text-gray-600">Xem, xóa, ghim review lên trang chủ</p>
        </div>
        <a href="{{ route('admin.reviews.import') }}"
           class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
            Import Reviews
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="p-4 border-b border-gray-200">
            <div class="flex flex-wrap items-center gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Tên, email, nội dung..."
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 min-w-[200px]">
                <select name="approved" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    <option value="">Tất cả duyệt</option>
                    <option value="1" {{ request('approved') === '1' ? 'selected' : '' }}>Đã duyệt</option>
                    <option value="0" {{ request('approved') === '0' ? 'selected' : '' }}>Chưa duyệt</option>
                </select>
                <select name="show_on_home" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    <option value="">Ghim trang chủ</option>
                    <option value="1" {{ request('show_on_home') === '1' ? 'selected' : '' }}>Đang ghim</option>
                    <option value="0" {{ request('show_on_home') === '0' ? 'selected' : '' }}>Chưa ghim</option>
                </select>
                <select name="per_page" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500">
                    @foreach([15, 25, 50] as $n)
                        <option value="{{ $n }}" {{ (int)request('per_page', 20) === $n ? 'selected' : '' }}>{{ $n }}/trang</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-700">Lọc</button>
            </div>
        </form>

        {{-- Bulk actions (hiện khi có ít nhất 1 checkbox chọn) --}}
        <div id="bulk-actions-bar" class="hidden px-4 py-3 bg-amber-50 border-b border-amber-200 flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-gray-700">Đã chọn: <span id="selected-count">0</span></span>
            <form id="bulk-form" method="post" class="flex flex-wrap items-center gap-2">
                @csrf
                <button type="submit" formaction="{{ route('admin.reviews.bulk-pin') }}" class="px-3 py-1.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700">Ghim trang chủ</button>
                <button type="submit" formaction="{{ route('admin.reviews.bulk-unpin') }}" class="px-3 py-1.5 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700">Bỏ ghim</button>
                <button type="submit" formaction="{{ route('admin.reviews.bulk-destroy') }}" class="px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700" onclick="return confirm('Xóa các review đã chọn?');">Xóa đã chọn</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" aria-label="Chọn tất cả">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ảnh</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Khách / Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Sao</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nội dung</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ghim</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($reviews as $review)
                        <tr class="hover:bg-gray-50" data-review-id="{{ $review->id }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="ids[]" value="{{ $review->id }}" form="bulk-form" class="review-checkbox rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                            </td>
                            <td class="px-4 py-3">
                                @if($review->image_url)
                                    @php $img = str_starts_with($review->image_url, 'http') ? $review->image_url : asset($review->image_url); @endphp
                                    <img src="{{ $img }}" alt="" class="w-12 h-12 rounded-lg object-cover border border-gray-200">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs">—</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($review->product)
                                    <a href="{{ route('products.show', $review->product->slug) }}" target="_blank" class="text-amber-600 hover:underline font-medium">
                                        {{ Str::limit($review->product->name, 35) }}
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900">{{ $review->customer_name }}</div>
                                @if($review->customer_email)
                                    <div class="text-gray-500 text-xs">{{ Str::limit($review->customer_email, 30) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="text-amber-500 font-medium">{{ $review->rating }} ★</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs">
                                @if($review->title)
                                    <div class="font-medium text-gray-900">{{ Str::limit($review->title, 40) }}</div>
                                @endif
                                <div>{{ Str::limit($review->review_text, 60) }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($review->is_approved)
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Đã duyệt</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Chưa duyệt</span>
                                @endif
                                @if($review->is_verified_purchase)
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 ml-1">Verified</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($review->show_on_home)
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Ghim</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $review->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="post" action="{{ route('admin.reviews.toggle-pin', $review) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-amber-600 hover:underline text-xs font-medium">
                                            {{ $review->show_on_home ? 'Bỏ ghim' : 'Ghim' }}
                                        </button>
                                    </form>
                                    <form method="post" action="{{ route('admin.reviews.destroy', $review) }}" class="inline" onsubmit="return confirm('Xóa review này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-xs font-medium">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-gray-500">
                                Chưa có review nào. <a href="{{ route('admin.reviews.import') }}" class="text-amber-600 hover:underline">Import Reviews</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.getElementById('select-all');
    var checkboxes = document.querySelectorAll('.review-checkbox');
    var bulkBar = document.getElementById('bulk-actions-bar');
    var countEl = document.getElementById('selected-count');

    function updateBulkBar() {
        var n = document.querySelectorAll('.review-checkbox:checked').length;
        countEl.textContent = n;
        bulkBar.classList.toggle('hidden', n === 0);
        if (selectAll) selectAll.checked = n > 0 && n === checkboxes.length;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
            updateBulkBar();
        });
    }
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateBulkBar);
    });
    updateBulkBar();
});
</script>
@endsection
