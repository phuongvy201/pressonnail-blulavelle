@extends('layouts.admin')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Cấu hình chương trình Affiliate</h1>
            <p class="text-gray-600">
                4 tier: Basic → Silver → Gold → Diamond. Tự nâng theo <strong>số đơn paid</strong> gán affiliate trong cửa sổ 30 ngày (refresh hàng tháng).
                Không có hoạt động 60 ngày → hạ 1 tier.
            </p>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow-md rounded-2xl overflow-hidden">
            <form method="POST" action="{{ route('admin.settings.affiliate-program.update') }}" class="p-6 space-y-8">
                @csrf
                @method('PUT')

                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-1">% hoa hồng theo tier</h2>
                    <p class="text-sm text-gray-600 mb-4">Mặc định: Basic 7% · Silver 10% · Gold 12% · Diamond 15%.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach (['basic' => 'Basic', 'silver' => 'Silver', 'gold' => 'Gold', 'diamond' => 'Diamond'] as $key => $label)
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ $label }} (%)</label>
                                <input type="number" name="tier_rate_{{ $key }}" step="0.01" min="0" max="100" required
                                       value="{{ old('tier_rate_'.$key, $rates[$key]) }}"
                                       class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50">
                                @error('tier_rate_'.$key)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Quy tắc commission</h2>
                    <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-5">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="hidden" name="commission_new_customers_only" value="0">
                            <input type="checkbox"
                                   name="commission_new_customers_only"
                                   value="1"
                                   class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   @checked(old('commission_new_customers_only', $commissionNewCustomersOnly))>
                            <span>
                                <span class="block text-sm font-semibold text-gray-900">Chỉ commission cho khách hàng mới (affiliate-acquired)</span>
                                <span class="mt-1 block text-sm text-gray-600 leading-relaxed">
                                    Cookie ref giữ <strong>14 ngày</strong> (last-click). Hoa hồng khi:
                                    (1) <strong>Lần mua paid đầu tiên</strong> của khách trên shop phải qua affiliate (có <code class="bg-white px-1 rounded text-xs">affiliate_id</code>);
                                    (2) Trong 14 ngày kể từ lần mua paid đầu tiên đó, các đơn paid tiếp theo (cùng khách, có attribution) vẫn được commission.
                                    Khách đã từng mua qua ads/organic/direct trước khi qua link KOL → <strong>không</strong> commission.
                                    Nhận diện khách: <code class="bg-white px-1 rounded text-xs">user_id</code> + email checkout (guest không login vẫn có email).
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Tự động nâng tier (theo đơn)</h2>
                    <p class="text-sm text-gray-600 mb-4">
                        Đếm đơn <code class="bg-gray-100 px-1 rounded">paid</code> có <code class="bg-gray-100 px-1 rounded">affiliate_id</code> trong cửa sổ lăn (mặc định 30 ngày).
                        Cron <code class="bg-gray-100 px-1 rounded">affiliate:recalculate-tiers</code> chạy hàng tháng.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cửa sổ đánh giá (ngày)</label>
                            <input type="number" name="tier_evaluation_days" step="1" min="1" max="365" required
                                   value="{{ old('tier_evaluation_days', $tierEvaluationDays) }}"
                                   class="w-full rounded-xl border-gray-300">
                            @error('tier_evaluation_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Silver (đơn)</label>
                            <input type="number" name="tier_threshold_silver" step="1" min="1" required
                                   value="{{ old('tier_threshold_silver', $tierOrderThresholds['silver']) }}"
                                   class="w-full rounded-xl border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Gold (đơn)</label>
                            <input type="number" name="tier_threshold_gold" step="1" min="1" required
                                   value="{{ old('tier_threshold_gold', $tierOrderThresholds['gold']) }}"
                                   class="w-full rounded-xl border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Diamond (đơn)</label>
                            <input type="number" name="tier_threshold_diamond" step="1" min="1" required
                                   value="{{ old('tier_threshold_diamond', $tierOrderThresholds['diamond']) }}"
                                   class="w-full rounded-xl border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Hạ tier sau (ngày không đơn)</label>
                            <input type="number" name="tier_inactivity_days" step="1" min="1" max="3650" required
                                   value="{{ old('tier_inactivity_days', $tierInactivityDays) }}"
                                   class="w-full rounded-xl border-gray-300">
                            @error('tier_inactivity_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
                        Lưu cấu hình
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
