@extends('layouts.admin')

@section('title', 'Import Reviews')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Import Reviews</h1>
            <p class="mt-1 text-sm text-gray-600">Upload file CSV hoặc Excel để thêm đánh giá sản phẩm hàng loạt</p>
        </div>
        <a href="{{ route('admin.reviews.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Về Danh sách Reviews
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-red-800">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-blue-800">{{ session('info') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-amber-50 to-orange-50">
                    <h3 class="text-lg font-medium text-gray-900">Upload file</h3>
                    <p class="text-sm text-gray-600 mt-1">Hỗ trợ: Excel (.xlsx, .xls) và CSV (.csv), tối đa 5MB</p>
                </div>
                <form method="POST" action="{{ route('admin.reviews.import.process') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="p-6">
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-amber-400 transition-colors bg-gray-50">
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="hidden" id="file-input" required
                                   onchange="document.getElementById('file-name').textContent = this.files[0]?.name || ''; document.getElementById('submit-btn').disabled = !this.files.length;">
                            <p class="text-gray-600 mb-4">Kéo thả file vào đây hoặc</p>
                            <button type="button" onclick="document.getElementById('file-input').click()"
                                    class="px-6 py-3 bg-amber-600 text-white font-semibold rounded-xl hover:bg-amber-700">
                                Chọn file
                            </button>
                            <p class="mt-2 text-sm text-gray-500" id="file-name"></p>
                        </div>
                        @error('file')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-6 flex justify-end">
                            <button type="submit" id="submit-btn" disabled
                                    class="px-6 py-3 bg-amber-600 text-white font-semibold rounded-xl hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                Import Reviews
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-teal-50">
                    <h3 class="text-lg font-medium text-gray-900">Tải template</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">Dùng file mẫu CSV để biết đúng định dạng cột.</p>
                    <a href="{{ route('admin.reviews.import.template') }}"
                       class="inline-flex items-center justify-center w-full px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Tải CSV mẫu
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
                    <h3 class="text-lg font-medium text-gray-900">Cột trong file</h3>
                </div>
                <div class="p-6 text-sm text-gray-700 space-y-2">
                    <p><strong>product_id</strong> hoặc <strong>product_sku</strong> — ID hoặc mã SKU sản phẩm (bắt buộc một trong hai)</p>
                    <p><strong>customer_name</strong> — Tên khách hàng (bắt buộc)</p>
                    <p><strong>customer_email</strong> — Email (tùy chọn)</p>
                    <p><strong>rating</strong> — Số sao 1–5 (mặc định 5)</p>
                    <p><strong>review_text</strong> — Nội dung đánh giá</p>
                    <p><strong>image_url</strong> — URL ảnh (để hiển thị testimonial trang chủ)</p>
                    <p><strong>title</strong> — Tiêu đề ngắn (vd: "I'm totally blown away.")</p>
                    <p><strong>is_verified_purchase</strong> — 1/0 hoặc yes/no (mặc định 1)</p>
                    <p><strong>is_approved</strong> — 1/0 hoặc yes/no (mặc định 1)</p>
                </div>
            </div>

            @if($products->isNotEmpty())
            <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Sản phẩm của bạn</h3>
                </div>
                <div class="p-6 max-h-48 overflow-y-auto">
                    <p class="text-xs text-gray-500 mb-2">Dùng ID hoặc SKU trong cột product_id / product_sku:</p>
                    <ul class="text-sm space-y-1">
                        @foreach($products->take(20) as $p)
                            <li><span class="font-mono text-gray-600">{{ $p->id }}</span> / <span class="font-mono">{{ $p->sku ?? '-' }}</span> — {{ Str::limit($p->name, 30) }}</li>
                        @endforeach
                    </ul>
                    @if($products->count() > 20)
                        <p class="text-xs text-gray-500 mt-2">... và {{ $products->count() - 20 }} sản phẩm khác</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
