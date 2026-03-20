<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $locale === 'vi' ? 'Hóa đơn' : 'Order Receipt' }} - {{ $order->order_number }}</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#059669",
                        "background-light": "#ecfdf5",
                        "background-dark": "#064e3b",
                    },
                    fontFamily: {
                        display: ["Public Sans", "system-ui", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px",
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Public Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; padding: 0 !important; }
            .shadow-xl { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-background-light text-slate-900 min-h-screen">
@php
    $brand = config('app.name', 'PressOnNail');
    $itemsSum = (float) ($order->items?->sum('total_price') ?? 0);
    $orderSubtotal = (float) ($order->subtotal ?? 0);
    $bulkDiscount = max(0, $itemsSum - $orderSubtotal);
@endphp
<div class="max-w-[850px] mx-auto p-6 md:p-12">
    {{-- Header / actions --}}
    <div class="flex items-center justify-between mb-8 no-print">
        <div class="flex items-center gap-2">
            <div class="bg-primary p-1.5 rounded-lg text-white">
                <span class="material-symbols-outlined text-2xl">grid_view</span>
            </div>
            <h1 class="text-xl font-bold tracking-tight">{{ $brand }}</h1>
        </div>
        <div class="flex gap-3">
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-primary/10 text-primary rounded-xl font-semibold text-sm hover:bg-primary/20 transition-colors">
                <span class="material-symbols-outlined text-sm">print</span>
                {{ $locale === 'vi' ? 'In' : 'Print' }}
            </button>
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-xl font-semibold text-sm hover:bg-primary/90 transition-colors shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined text-sm">download</span>
                {{ $locale === 'vi' ? 'Lưu PDF' : 'Save PDF' }}
            </button>
        </div>
    </div>

    {{-- Receipt card --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden">
        {{-- Brand / order header --}}
        <div class="p-8 border-b border-dashed border-slate-200">
            <div class="flex flex-col md:flex-row justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-bold mb-1">
                        {{ $locale === 'vi' ? 'Hóa đơn' : 'Receipt' }}
                    </h2>
                    <p class="text-slate-500">#{{ $order->order_number }}</p>
                </div>
                <div class="text-left md:text-right">
                    <p class="font-semibold text-primary">{{ $brand }}</p>
                    <p class="text-sm text-slate-500">{{ $order->customer_email }}</p>
                </div>
            </div>
        </div>

        {{-- Meta info --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-8 bg-slate-50/50">
            <div>
                <p class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-1">
                    {{ $locale === 'vi' ? 'Ngày thanh toán' : 'Date Paid' }}
                </p>
                <p class="font-medium">{{ $order->created_at->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-1">
                    {{ $locale === 'vi' ? 'Thanh toán' : 'Payment' }}
                </p>
                <p class="font-medium flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">credit_card</span>
                    {{ strtoupper($order->payment_method) }}
                </p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-1">
                    {{ $locale === 'vi' ? 'Trạng thái' : 'Status' }}
                </p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-tighter
                    @if($order->payment_status === 'paid') bg-green-100 text-green-700 @else bg-amber-100 text-amber-700 @endif">
                    {{ $order->payment_status === 'paid'
                        ? ($locale === 'vi' ? 'Đã thanh toán' : 'Paid')
                        : ($locale === 'vi' ? 'Chờ thanh toán' : 'Pending') }}
                </span>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-1">
                    {{ $locale === 'vi' ? 'Tiền tệ' : 'Currency' }}
                </p>
                <p class="font-medium">{{ $currency ?? 'USD' }}</p>
            </div>
        </div>

        {{-- Shipping / billing --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8 border-t border-slate-200">
            <div>
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">
                    {{ $locale === 'vi' ? 'Địa chỉ giao hàng' : 'Shipping Address' }}
                </h3>
                <div class="text-slate-700 space-y-1">
                    <p class="font-bold text-slate-900">{{ $order->customer_name }}</p>
                    <p>{{ $order->shipping_address }}</p>
                    <p>{{ $order->city }}, {{ $order->state }} {{ $order->postal_code }}</p>
                    <p>{{ $order->country }}</p>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">
                    {{ $locale === 'vi' ? 'Thông tin thanh toán' : 'Billing Details' }}
                </h3>
                <div class="text-slate-700 space-y-1">
                    <p class="font-bold text-slate-900">{{ $order->customer_name }}</p>
                    <p>{{ $order->shipping_address }}</p>
                    <p>{{ $order->city }}, {{ $order->state }} {{ $order->postal_code }}</p>
                    <p>{{ $order->country }}</p>
                </div>
            </div>
        </div>

        {{-- Items table --}}
        <div class="px-8 pb-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="border-b border-slate-200">
                        <th class="py-4 font-bold text-sm uppercase tracking-wider text-slate-400">
                            {{ $locale === 'vi' ? 'Sản phẩm' : 'Product' }}
                        </th>
                        <th class="py-4 font-bold text-sm uppercase tracking-wider text-slate-400 text-center">
                            {{ $locale === 'vi' ? 'SL' : 'Qty' }}
                        </th>
                        <th class="py-4 font-bold text-sm uppercase tracking-wider text-slate-400 text-right">
                            {{ $locale === 'vi' ? 'Đơn giá' : 'Price' }}
                        </th>
                        <th class="py-4 font-bold text-sm uppercase tracking-wider text-slate-400 text-right">
                            {{ $locale === 'vi' ? 'Tổng' : 'Amount' }}
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($order->items as $item)
                        @php
                            $product = $item->product;
                            $imageUrl = null;
                            if ($product) {
                                $media = $product->getEffectiveMedia();
                                if ($media && count($media) > 0) {
                                    $imageUrl = is_string($media[0]) ? $media[0] : ($media[0]['url'] ?? $media[0]['path'] ?? reset($media[0]) ?? null);
                                }
                            }
                            $optionsText = '';
                            if (!empty($item->product_options) && is_array($item->product_options)) {
                                $clean = function ($s) {
                                    $s = (string) $s;
                                    $s = str_replace(['/', '(', ')', '\\'], [' ', '', '', ' '], $s);
                                    return trim(preg_replace('/\s+/', ' ', $s));
                                };
                                $skipIdKey = function ($k) {
                                    if (!is_string($k)) return false;
                                    $lower = strtolower($k);
                                    return $lower === 'id' || str_ends_with($lower, '_id');
                                };
                                $parts = [];
                                foreach ($item->product_options as $key => $val) {
                                    if ($skipIdKey($key)) continue;
                                    if (is_array($val)) {
                                        if (isset($val['value'])) {
                                            $parts[] = is_scalar($val['value']) ? $clean($val['value']) : '';
                                        } elseif (isset($val['label'])) {
                                            $parts[] = $clean($val['label']);
                                        } else {
                                            $pairs = [];
                                            foreach ($val as $k => $v) {
                                                if ($skipIdKey($k) || !is_scalar($v)) continue;
                                                $l = is_string($k) ? str_replace('_', ' ', ucfirst($k)) . ': ' : '';
                                                $pairs[] = $l . $clean($v);
                                            }
                                            if (!empty($pairs)) $parts[] = implode(' • ', $pairs);
                                        }
                                    } else {
                                        $label = is_string($key) && !is_numeric($key) ? str_replace('_', ' ', ucfirst($key)) . ': ' : '';
                                        $parts[] = $label . (is_scalar($val) ? $clean($val) : '');
                                    }
                                }
                                $optionsText = implode(' • ', array_filter($parts));
                            }
                        @endphp
                        <tr>
                            <td class="py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-lg bg-slate-100 flex-shrink-0 flex items-center justify-center overflow-hidden">
                                        @if($imageUrl)
                                            <img src="{{ $imageUrl }}" alt="{{ $item->product_name }}" class="object-cover w-full h-full">
                                        @else
                                            <span class="material-symbols-outlined text-slate-400">image</span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold">{{ $item->product_name }}</p>
                                        @if($optionsText)
                                            <p class="text-xs text-slate-500 italic">{{ $optionsText }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-5 text-center font-medium">{{ $item->quantity }}</td>
                            <td class="py-5 text-right font-medium">
                                {{ \App\Services\CurrencyService::formatPrice($item->unit_price, $currency ?? 'USD') }}
                            </td>
                            <td class="py-5 text-right font-bold">
                                {{ \App\Services\CurrencyService::formatPrice($item->total_price, $currency ?? 'USD') }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Exchange rate + totals --}}
        <div class="p-8 bg-slate-50 border-t border-slate-200">
            <div class="flex flex-col md:flex-row justify-between gap-8">
                <div class="max-w-xs">
                    <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">
                        {{ $locale === 'vi' ? 'Ghi chú' : 'Note' }}
                    </p>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        {{ $locale === 'vi'
                            ? 'Sản phẩm sẽ được gửi đi trong 2–3 ngày làm việc. Bạn sẽ nhận email kèm mã tracking khi đơn được gửi.'
                            : 'Items will be shipped within 2–3 business days. You will receive an email with your tracking number once your package is on its way.' }}
                    </p>
                    @if($order->notes)
                        <p class="text-xs text-slate-500 leading-relaxed mt-3">
                            <span class="font-semibold">{{ $locale === 'vi' ? 'Ghi chú của bạn: ' : 'Your note: ' }}</span>{{ $order->notes }}
                        </p>
                    @endif
                </div>
                <div class="w-full md:w-64 space-y-3">
                    @if(($currency ?? 'USD') !== 'USD' && isset($currencyRate))
                        <div class="text-xs text-slate-500 bg-white border border-slate-200 rounded-lg p-2.5">
                            <div class="flex justify-between">
                                <span>{{ $locale === 'vi' ? 'Tỷ giá:' : 'Exchange rate:' }}</span>
                                <span class="font-semibold">1 USD = {{ number_format($currencyRate, 4) }} {{ $currency }}</span>
                            </div>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">{{ $locale === 'vi' ? 'Tạm tính' : 'Subtotal' }}</span>
                        <span class="font-medium">
                            {{ \App\Services\CurrencyService::formatPrice($convertedSubtotal ?? $order->subtotal, $currency ?? 'USD') }}
                        </span>
                    </div>
                    @if($bulkDiscount > 0)
                    <div class="flex justify-between text-sm text-emerald-600 font-medium">
                        <span>{{ $locale === 'vi' ? 'Giảm theo số lượng' : 'Volume discount' }}</span>
                        <span>-{{ \App\Services\CurrencyService::formatPrice($bulkDiscount, $currency ?? 'USD') }}</span>
                    </div>
                    @endif
                    @if($order->promo_code && (float)($order->discount_amount ?? 0) > 0)
                    <div class="flex justify-between text-sm text-emerald-600">
                        <span>{{ $locale === 'vi' ? 'Mã giảm giá' : 'Promo' }} ({{ $order->promo_code }})</span>
                        <span class="font-medium">-{{ \App\Services\CurrencyService::formatPrice($order->discount_amount, $currency ?? 'USD') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">{{ $locale === 'vi' ? 'Vận chuyển' : 'Shipping' }}</span>
                        <span class="font-medium">
                            {{ \App\Services\CurrencyService::formatPrice($convertedShipping ?? $order->shipping_cost, $currency ?? 'USD') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">{{ $locale === 'vi' ? 'Thuế' : 'Tax' }}</span>
                        <span class="font-medium">
                            {{ \App\Services\CurrencyService::formatPrice($convertedTax ?? $order->tax_amount, $currency ?? 'USD') }}
                        </span>
                    </div>
                    @if($order->tip_amount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">{{ $locale === 'vi' ? 'Tiền tip' : 'Tips' }}</span>
                            <span class="font-medium">
                                {{ \App\Services\CurrencyService::formatPrice($convertedTip ?? $order->tip_amount, $currency ?? 'USD') }}
                            </span>
                        </div>
                    @endif
                    <div class="pt-3 border-t border-slate-200 flex justify-between items-end">
                        <span class="font-bold text-lg">{{ $locale === 'vi' ? 'Tổng thanh toán' : 'Total Paid' }}</span>
                        <span class="font-bold text-2xl text-primary">
                            {{ \App\Services\CurrencyService::formatPrice($convertedTotal ?? $order->total_amount, $currency ?? 'USD') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer message --}}
        <div class="p-8 border-t border-dashed border-slate-200 flex flex-col items-center text-center gap-4">
            <div class="w-16 h-1 bg-primary rounded-full opacity-30"></div>
            <p class="text-sm font-medium text-slate-600">
                {{ $locale === 'vi' ? 'Cảm ơn bạn đã chọn PressOnNail!' : 'Thank you for choosing PressOnNail!' }}
            </p>
            <p class="text-xs text-slate-400 max-w-sm">
                {{ $locale === 'vi'
                    ? 'Nếu cần hỗ trợ về đơn hàng, vui lòng liên hệ kèm mã đơn: ' . $order->order_number
                    : 'For questions about your order, please contact support with your transaction ID: ' . $order->order_number }}
            </p>
        </div>
    </div>

    <div class="mt-8 flex justify-center no-print">
        <a href="{{ route('products.index') }}" class="text-sm font-semibold text-slate-500 hover:text-primary flex items-center gap-1 transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            {{ $locale === 'vi' ? 'Quay lại cửa hàng' : 'Return to Store' }}
        </a>
    </div>
</div>
</body>
</html>
