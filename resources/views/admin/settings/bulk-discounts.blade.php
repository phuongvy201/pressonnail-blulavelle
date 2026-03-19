@extends('layouts.admin')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Cấu hình giảm giá theo số lượng</h1>
            <p class="text-gray-600">
                Thiết lập các bậc giảm giá theo số lượng mua cho từng dòng sản phẩm trong giỏ hàng.
                Ví dụ: mua từ 2 giảm 20%, từ 3 giảm 25%, từ 5 giảm 30%.
            </p>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow-md rounded-2xl overflow-hidden">
            <form method="POST" action="{{ route('admin.settings.bulk-discounts.update') }}" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Bậc giảm giá</h2>
                        <p class="text-sm text-gray-600">Mỗi bậc gồm: số lượng tối thiểu và phần trăm giảm.</p>
                    </div>
                    <button type="button" id="add-rule"
                            class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition font-semibold">
                        + Thêm bậc
                    </button>
                </div>

                @error('rules')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Số lượng tối thiểu</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">% giảm</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="rules-body" class="divide-y divide-gray-100">
                            @foreach(old('rules', $rules) as $i => $row)
                                <tr class="rule-row">
                                    <td class="px-4 py-3">
                                        <input type="number" min="1" max="999" step="1"
                                               name="rules[{{ $i }}][min_qty]"
                                               value="{{ $row['min_qty'] ?? '' }}"
                                               class="w-40 rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50">
                                        @error("rules.$i.min_qty")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <input type="number" min="0" max="95" step="0.1"
                                                   name="rules[{{ $i }}][percent]"
                                                   value="{{ $row['percent'] ?? '' }}"
                                                   class="w-40 rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50">
                                            <span class="text-sm text-gray-500 font-semibold">%</span>
                                        </div>
                                        @error("rules.$i.percent")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" class="remove-rule px-3 py-2 rounded-xl text-red-600 hover:bg-red-50 transition font-semibold">
                                            Xóa
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 text-sm">
                    <div class="font-bold mb-1">Gợi ý</div>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Nếu có nhiều bậc trùng <code>min_qty</code>, hệ thống sẽ lấy % lớn nhất.</li>
                        <li>Bậc sẽ được tự động sắp xếp theo <code>min_qty</code> tăng dần.</li>
                        <li>% giảm được áp dụng cho từng dòng sản phẩm dựa trên số lượng của dòng đó.</li>
                    </ul>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                        Quay lại
                    </a>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var body = document.getElementById('rules-body');
            var addBtn = document.getElementById('add-rule');
            if (!body || !addBtn) return;

            function reindex() {
                var rows = body.querySelectorAll('tr.rule-row');
                rows.forEach(function(row, idx) {
                    var min = row.querySelector('input[name*="[min_qty]"]');
                    var pct = row.querySelector('input[name*="[percent]"]');
                    if (min) min.name = 'rules[' + idx + '][min_qty]';
                    if (pct) pct.name = 'rules[' + idx + '][percent]';
                });
            }

            function bindRemove(btn) {
                btn.addEventListener('click', function() {
                    var tr = btn.closest('tr');
                    if (tr) tr.remove();
                    reindex();
                });
            }

            body.querySelectorAll('.remove-rule').forEach(bindRemove);

            addBtn.addEventListener('click', function() {
                var idx = body.querySelectorAll('tr.rule-row').length;
                var tr = document.createElement('tr');
                tr.className = 'rule-row';
                tr.innerHTML = '' +
                    '<td class="px-4 py-3">' +
                        '<input type="number" min="1" max="999" step="1" name="rules[' + idx + '][min_qty]" value="" class="w-40 rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50">' +
                    '</td>' +
                    '<td class="px-4 py-3">' +
                        '<div class="flex items-center gap-2">' +
                            '<input type="number" min="0" max="95" step="0.1" name="rules[' + idx + '][percent]" value="" class="w-40 rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50">' +
                            '<span class="text-sm text-gray-500 font-semibold">%</span>' +
                        '</div>' +
                    '</td>' +
                    '<td class="px-4 py-3 text-right">' +
                        '<button type="button" class="remove-rule px-3 py-2 rounded-xl text-red-600 hover:bg-red-50 transition font-semibold">Xóa</button>' +
                    '</td>';
                body.appendChild(tr);
                bindRemove(tr.querySelector('.remove-rule'));
                reindex();
            });
        })();
    </script>
@endsection

