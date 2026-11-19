@extends('layouts.admin')

@section('content')
    @php
        $campaignLabels = [
            'website_header' => 'Header',
            'website_footer' => 'Footer',
            'website_hero' => 'Hero',
            'website_hero_qr' => 'Hero QR',
            'website_tryon' => 'Virtual try-on',
            'website_mobile_menu' => 'Mobile menu',
            'order_confirmation' => 'Order confirmation',
            'order_confirmation_email' => 'Order email',
        ];
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">App Store downloads</h1>
                <p class="text-gray-600 max-w-3xl">
                    Số liệu từ App Store Connect Analytics. Apple hoàn tất số của một ngày sau khoảng 2 ngày.
                    Cột campaign khớp mã <span class="font-mono">ct</span> trên link tải app của website.
                </p>
            </div>
            @if($configured)
                <form method="POST" action="{{ route('admin.app-store.sync') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-sky-600 text-white font-semibold hover:bg-sky-700">
                        Kéo 7 ngày gần nhất
                    </button>
                </form>
            @endif
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">{{ session('error') }}</div>
        @endif

        @unless($configured)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-950">
                <p class="font-semibold mb-2">Chưa nối API.</p>
                <p class="text-sm leading-6">
                    Trong App Store Connect, vào Users and Access → Integrations → App Store Connect API, tạo key quyền Admin.
                    Điền <span class="font-mono">APPLE_CONNECT_ISSUER_ID</span>, <span class="font-mono">APPLE_CONNECT_KEY_ID</span>
                    và đường dẫn file <span class="font-mono">.p8</span> vào <span class="font-mono">APPLE_CONNECT_PRIVATE_KEY</span>.
                    Lần kéo đầu có thể phải đợi 24–48 giờ Apple tạo báo cáo.
                </p>
            </div>
        @endunless

        <p class="text-sm text-gray-500 mb-6">
            30 ngày gần nhất, từ {{ $since }}.
            @if($lastImport)
                Lần nhập cuối: {{ $lastImport->imported_at?->timezone(config('app.timezone'))->format('M j, Y H:i') }}
                ({{ $lastImport->report_name }}).
            @else
                Chưa có lần nhập nào.
            @endif
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            @forelse($byType as $type)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-sm text-gray-500">{{ $type->download_type !== '' ? $type->download_type : 'Unspecified' }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format((int) $type->total) }}</p>
                </div>
            @empty
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:col-span-3 text-gray-500">
                    Chưa có số lượt tải. Bấm kéo dữ liệu sau khi đã cấu hình key.
                </div>
            @endforelse
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Theo campaign trên website</h2>
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($byCampaign as $row)
                            <tr>
                                <td class="py-2 pr-4">
                                    <span class="font-medium text-gray-900">{{ $campaignLabels[$row->campaign] ?? $row->campaign }}</span>
                                    <span class="block text-xs text-gray-500 font-mono">{{ $row->campaign }}</span>
                                </td>
                                <td class="py-2 text-right font-semibold">{{ number_format((int) $row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-2 text-gray-500">Chưa có lượt tải gắn campaign. Báo cáo Detailed mới có cột này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Theo nguồn App Store</h2>
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($bySource as $row)
                            <tr>
                                <td class="py-2 pr-4 text-gray-800">{{ $row->source_type !== '' ? $row->source_type : 'Unspecified' }}</td>
                                <td class="py-2 text-right font-semibold">{{ number_format((int) $row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-2 text-gray-500">Chưa có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>

        <section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Theo ngày</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 pr-4 font-medium">Ngày</th>
                            <th class="py-2 text-right font-medium">Lượt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($byDate as $row)
                            <tr>
                                <td class="py-2 pr-4">{{ $row->stat_date?->format('M j, Y') }}</td>
                                <td class="py-2 text-right font-semibold">{{ number_format((int) $row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-2 text-gray-500" colspan="2">Chưa có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
