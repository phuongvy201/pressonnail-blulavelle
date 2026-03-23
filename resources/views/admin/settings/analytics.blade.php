@extends('layouts.admin')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Cấu hình Tracking & Pixels</h1>
                    <p class="text-gray-600">
                        Thay đổi ID tích hợp (Meta Pixel, TikTok Pixel, Google Tag Manager, Google Ads) trực tiếp trong admin.
                        Để trống một trường sẽ quay về giá trị mặc định đang cấu hình trong hệ thống.
                    </p>
                </div>
               
            </div>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow-md rounded-2xl overflow-hidden">
            <form method="POST" action="{{ route('admin.settings.analytics.update') }}" class="p-6 space-y-8" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="meta_pixel_id" class="block text-sm font-semibold text-gray-900 mb-1">
                            Meta Pixel ID
                        </label>
                        <input
                            type="text"
                            name="meta_pixel_id"
                            id="meta_pixel_id"
                            value="{{ old('meta_pixel_id', $settings['meta_pixel_id']) }}"
                            placeholder="{{ $defaults['meta_pixel_id'] }}"
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                        >
                        @error('meta_pixel_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">
                            Ví dụ: <code>{{ $defaults['meta_pixel_id'] }}</code>
                        </p>
                    </div>

                    <div>
                        <label for="tiktok_pixel_id" class="block text-sm font-semibold text-gray-900 mb-1">
                            TikTok Pixel ID
                        </label>
                        <input
                            type="text"
                            name="tiktok_pixel_id"
                            id="tiktok_pixel_id"
                            value="{{ old('tiktok_pixel_id', $settings['tiktok_pixel_id']) }}"
                            placeholder="{{ $defaults['tiktok_pixel_id'] }}"
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                        >
                        @error('tiktok_pixel_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">
                            Ví dụ: <code>{{ $defaults['tiktok_pixel_id'] }}</code>
                        </p>
                    </div>

                    <div>
                        <label for="tiktok_test_event_code" class="block text-sm font-semibold text-gray-900 mb-1">
                            TikTok Test Event Code
                        </label>
                        <input
                            type="text"
                            name="tiktok_test_event_code"
                            id="tiktok_test_event_code"
                            value="{{ old('tiktok_test_event_code', $settings['tiktok_test_event_code']) }}"
                            placeholder="{{ $defaults['tiktok_test_event_code'] }}"
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                        >
                        @error('tiktok_test_event_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">
                            Để trống nếu đang chạy sự kiện live.
                        </p>
                    </div>

                    <div>
                        <label for="google_tag_manager_id" class="block text-sm font-semibold text-gray-900 mb-1">
                            Google Tag Manager ID
                        </label>
                        <input
                            type="text"
                            name="google_tag_manager_id"
                            id="google_tag_manager_id"
                            value="{{ old('google_tag_manager_id', $settings['google_tag_manager_id']) }}"
                            placeholder="{{ $defaults['google_tag_manager_id'] }}"
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                        >
                        @error('google_tag_manager_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">
                            Ví dụ: <code>{{ $defaults['google_tag_manager_id'] }}</code>
                        </p>
                    </div>

                    <div>
                        <label for="google_ads_id" class="block text-sm font-semibold text-gray-900 mb-1">
                            Google Ads / gtag ID
                        </label>
                        <input
                            type="text"
                            name="google_ads_id"
                            id="google_ads_id"
                            value="{{ old('google_ads_id', $settings['google_ads_id']) }}"
                            placeholder="{{ $defaults['google_ads_id'] }}"
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                        >
                        @error('google_ads_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">
                            Ví dụ: <code>{{ $defaults['google_ads_id'] }}</code>
                        </p>
                    </div>

                    {{-- GA4 --}}
                    <div>
                        <label for="google_analytics_property_id" class="block text-sm font-semibold text-gray-900 mb-1">
                            GA4 Property ID
                        </label>
                        <input
                            type="text"
                            name="google_analytics_property_id"
                            id="google_analytics_property_id"
                            value="{{ old('google_analytics_property_id', $settings['google_analytics_property_id']) }}"
                            placeholder="{{ $defaults['google_analytics_property_id'] }}"
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                        >
                        @error('google_analytics_property_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">
                            Ví dụ: <code>G-XXXXXXXXXX</code> (lưu để trang Analytics đọc cấu hình GA4).
                        </p>
                    </div>

                    <div>
                        <label for="google_analytics_credentials" class="block text-sm font-semibold text-gray-900 mb-1">
                            GA4 Credentials JSON (Google Cloud)
                        </label>
                        <input
                            type="file"
                            name="google_analytics_credentials"
                            id="google_analytics_credentials"
                            accept=".json,application/json"
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                        >
                        @error('google_analytics_credentials')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">
                            Hoặc dùng field path bên dưới (nếu file đã nằm sẵn trong `storage/app`).
                        </p>

                        <div class="mt-3">
                            <label for="google_analytics_credentials_path" class="block text-sm font-semibold text-gray-900 mb-1">
                                Credentials path (storage/app)
                            </label>
                            <input
                                type="text"
                                name="google_analytics_credentials_path"
                                id="google_analytics_credentials_path"
                                value="{{ old('google_analytics_credentials_path', $settings['google_analytics_credentials_path']) }}"
                                placeholder="{{ $defaults['google_analytics_credentials_path'] }}"
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                            >
                            @error('google_analytics_credentials_path')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-500">
                                Ví dụ: <code>analytics/google-analytics-credentials-1699999999.json</code>.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Theme colors</h2>
                    <p class="text-sm text-gray-600 mb-2">
                        Nhập màu dạng <code>#RRGGBB</code> hoặc <code>rgb(...)</code>. Để trống sẽ dùng màu mặc định trong giao diện.
                    </p>
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-5">
                        <strong>Lưu vĩnh viễn:</strong> Màu đang lưu trong database. Để không mất khi cập nhật/deploy bản mới, chạy lệnh <code class="bg-amber-100 px-1 rounded">php artisan settings:export-theme</code> rồi commit file <code class="bg-amber-100 px-1 rounded">config/theme.php</code> lên git.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="header_bg" class="block text-sm font-semibold text-gray-900 mb-1">Header background</label>
                            <input
                                type="text"
                                name="header_bg"
                                id="header_bg"
                                value="{{ old('header_bg', $settings['header_bg']) }}"
                                placeholder="#f8fafc"
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                            >
                            @error('header_bg')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="header_border" class="block text-sm font-semibold text-gray-900 mb-1">Header border color</label>
                            <input
                                type="text"
                                name="header_border"
                                id="header_border"
                                value="{{ old('header_border', $settings['header_border']) }}"
                                placeholder="#e2e8f0"
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                            >
                            @error('header_border')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="testimonials_bg" class="block text-sm font-semibold text-gray-900 mb-1">Reviews/Testimonials background</label>
                            <input
                                type="text"
                                name="testimonials_bg"
                                id="testimonials_bg"
                                value="{{ old('testimonials_bg', $settings['testimonials_bg']) }}"
                                placeholder="#ffffff"
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                            >
                            @error('testimonials_bg')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="footer_faq_bg" class="block text-sm font-semibold text-gray-900 mb-1">Footer FAQ background</label>
                            <input
                                type="text"
                                name="footer_faq_bg"
                                id="footer_faq_bg"
                                value="{{ old('footer_faq_bg', $settings['footer_faq_bg']) }}"
                                placeholder="#ffffff"
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                            >
                            @error('footer_faq_bg')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="footer_bg" class="block text-sm font-semibold text-gray-900 mb-1">Footer background</label>
                            <input
                                type="text"
                                name="footer_bg"
                                id="footer_bg"
                                value="{{ old('footer_bg', $settings['footer_bg']) }}"
                                placeholder="#242B3D"
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                            >
                            @error('footer_bg')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Mail branding (email layout)</h2>
                    <p class="text-sm text-gray-600 mb-2">
                        Logo và tên thương hiệu hiển thị trong header email. Để trống sẽ dùng logo mặc định và <code>APP_NAME</code>.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="mail_logo_url" class="block text-sm font-semibold text-gray-900 mb-1">Logo URL (email)</label>
                            <input
                                type="text"
                                name="mail_logo_url"
                                id="mail_logo_url"
                                value="{{ old('mail_logo_url', $settings['mail_logo_url']) }}"
                                placeholder="VD: {{ asset('images/logo to.png') }}"
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                            >
                            @error('mail_logo_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            <p class="mt-2 text-xs text-gray-500">URL đầy đủ hoặc đường dẫn relative (vd: <code>/images/logo.png</code>).</p>
                        </div>
                        <div>
                            <label for="mail_brand_name" class="block text-sm font-semibold text-gray-900 mb-1">Tên thương hiệu (alt text logo)</label>
                            <input
                                type="text"
                                name="mail_brand_name"
                                id="mail_brand_name"
                                value="{{ old('mail_brand_name', $settings['mail_brand_name']) }}"
                                placeholder="{{ $defaults['mail_brand_name'] ?: config('app.name') }}"
                                class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50"
                            >
                            @error('mail_brand_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
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
@endsection


