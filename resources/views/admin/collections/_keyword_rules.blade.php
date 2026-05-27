@php
    $rules = isset($collection)
        ? (is_array($collection->auto_rules) ? $collection->auto_rules : [])
        : [];
    $keywordsEnabled = old('keyword_auto_assign', \App\Support\CollectionKeywordRules::isEnabled($rules));
    $keywordsText = old('match_keywords', isset($collection)
        ? \App\Support\CollectionKeywordRules::keywordsDisplayString($collection)
        : '');
@endphp

<div class="border-b border-gray-200 pb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"></path>
        </svg>
        Keyword auto-assign
    </h3>

    <div class="rounded-lg bg-violet-50 border border-violet-100 px-4 py-3 text-sm text-violet-900 mb-4">
        Khi bật, sản phẩm mới (hoặc cập nhật tên/mô tả/meta keywords) có chứa <strong>một trong các từ khóa</strong> sẽ được <strong>tự thêm</strong> vào collection này.
        Collection site-wide (<code class="text-xs">shop_id</code> trống) áp dụng cho mọi shop. Chỉ auto-add, không tự gỡ.
    </div>

    <label class="flex items-start gap-3 cursor-pointer mb-4">
        <input type="checkbox" name="keyword_auto_assign" value="1"
               class="mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
               {{ $keywordsEnabled ? 'checked' : '' }}>
        <span>
            <span class="block text-sm font-semibold text-gray-900">Bật gán sản phẩm theo keyword</span>
            <span class="block text-xs text-gray-500 mt-0.5">Dùng được với cả collection Manual và Automatic.</span>
        </span>
    </label>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Keywords</label>
        <textarea name="match_keywords" rows="5"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-mono text-sm"
                  placeholder="melanin&#10;black girl&#10;african american">{{ $keywordsText }}</textarea>
        <p class="text-xs text-gray-500 mt-2">Mỗi dòng một từ/cụm từ, hoặc phân tách bằng dấu phẩy. Quét trong: tên, mô tả, meta keywords.</p>
        @error('match_keywords')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
