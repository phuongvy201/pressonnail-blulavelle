@extends('layouts.admin')

@php
    $inputClass = 'w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50';
    $hintClass = 'mt-2 text-xs text-gray-500';
    $labelClass = 'block text-sm font-semibold text-gray-900 mb-1';
@endphp

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 pb-28">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Cấu hình Tracking & Pixels</h1>
            <p class="text-gray-600 max-w-3xl">
                Quản lý pixel quảng cáo, Google Analytics và một số tuỳ chọn giao diện storefront.
                Để trống một trường sẽ dùng giá trị mặc định trong hệ thống (<code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">.env</code> / <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">config</code>).
            </p>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Quick nav --}}
        <nav class="mb-6 flex flex-wrap gap-2" aria-label="Mục cấu hình">
            @foreach ([
                'google' => 'Google',
                'meta' => 'Meta',
                'tiktok' => 'TikTok',
                'chatgpt' => 'ChatGPT',
                'pinterest' => 'Pinterest',
                'theme' => 'Theme',
                'mail' => 'Email',
                'gmc' => 'GMC',
            ] as $anchor => $label)
                <a href="#section-{{ $anchor }}"
                   class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-blue-300 hover:text-blue-700 transition">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.settings.analytics.update') }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- ── Google ── --}}
            <section id="section-google" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden scroll-mt-6">
                <header class="flex items-start gap-4 border-b border-gray-100 bg-gradient-to-r from-sky-50 to-white px-6 py-5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-sm font-bold text-white">G</div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900">Google — Tag Manager, Ads & GA4</h2>
                        <p class="mt-0.5 text-sm text-gray-600">GTM load sớm trên storefront; GA4 dùng cho dashboard Analytics trong admin.</p>
                    </div>
                </header>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="google_tag_manager_id" class="{{ $labelClass }}">Google Tag Manager ID</label>
                        <input type="text" name="google_tag_manager_id" id="google_tag_manager_id"
                               value="{{ old('google_tag_manager_id', $settings['google_tag_manager_id']) }}"
                               placeholder="{{ $defaults['google_tag_manager_id'] }}"
                               class="{{ $inputClass }}">
                        @error('google_tag_manager_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="{{ $hintClass }}">Ví dụ: <code>{{ $defaults['google_tag_manager_id'] }}</code></p>
                    </div>
                    <div>
                        <label for="google_ads_id" class="{{ $labelClass }}">Google Ads / gtag ID</label>
                        <input type="text" name="google_ads_id" id="google_ads_id"
                               value="{{ old('google_ads_id', $settings['google_ads_id']) }}"
                               placeholder="{{ $defaults['google_ads_id'] }}"
                               class="{{ $inputClass }}">
                        @error('google_ads_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="{{ $hintClass }}">Ví dụ: <code>{{ $defaults['google_ads_id'] }}</code></p>
                    </div>
                    <div>
                        <label for="google_analytics_property_id" class="{{ $labelClass }}">GA4 Property ID</label>
                        <input type="text" name="google_analytics_property_id" id="google_analytics_property_id"
                               value="{{ old('google_analytics_property_id', $settings['google_analytics_property_id']) }}"
                               placeholder="{{ $defaults['google_analytics_property_id'] }}"
                               class="{{ $inputClass }}">
                        @error('google_analytics_property_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="{{ $hintClass }}">Ví dụ: <code>G-XXXXXXXXXX</code></p>
                    </div>
                    <div class="md:col-span-2 rounded-xl border border-sky-100 bg-sky-50/50 p-4 space-y-4">
                        <p class="text-sm font-semibold text-gray-900">GA4 Credentials (Google Cloud)</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="google_analytics_credentials" class="{{ $labelClass }}">Upload file JSON</label>
                                <input type="file" name="google_analytics_credentials" id="google_analytics_credentials"
                                       accept=".json,application/json" class="{{ $inputClass }}">
                                @error('google_analytics_credentials')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="google_analytics_credentials_path" class="{{ $labelClass }}">Hoặc path trong <code>storage/app</code></label>
                                <input type="text" name="google_analytics_credentials_path" id="google_analytics_credentials_path"
                                       value="{{ old('google_analytics_credentials_path', $settings['google_analytics_credentials_path']) }}"
                                       placeholder="{{ $defaults['google_analytics_credentials_path'] }}"
                                       class="{{ $inputClass }}">
                                @error('google_analytics_credentials_path')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                <p class="{{ $hintClass }}">VD: <code>analytics/google-analytics-credentials-1699999999.json</code></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── Ad pixels (2-col grid of platform cards) ── --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Meta --}}
                <section id="section-meta" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden scroll-mt-6">
                    <header class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white px-5 py-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#1877F2] text-xs font-bold text-white">f</div>
                        <h2 class="text-base font-bold text-gray-900">Meta Pixel</h2>
                    </header>
                    <div class="p-5">
                        <label for="meta_pixel_id" class="{{ $labelClass }}">Pixel ID</label>
                        <input type="text" name="meta_pixel_id" id="meta_pixel_id"
                               value="{{ old('meta_pixel_id', $settings['meta_pixel_id']) }}"
                               placeholder="{{ $defaults['meta_pixel_id'] }}"
                               class="{{ $inputClass }}">
                        @error('meta_pixel_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="{{ $hintClass }}">Ví dụ: <code>{{ $defaults['meta_pixel_id'] }}</code></p>
                    </div>
                </section>

                {{-- TikTok --}}
                <section id="section-tiktok" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden scroll-mt-6">
                    <header class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-gray-900/5 to-white px-5 py-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-900 text-xs font-bold text-white">TT</div>
                        <h2 class="text-base font-bold text-gray-900">TikTok Pixel</h2>
                    </header>
                    <div class="p-5 space-y-4">
                        <div>
                            <label for="tiktok_pixel_id" class="{{ $labelClass }}">Pixel ID</label>
                            <input type="text" name="tiktok_pixel_id" id="tiktok_pixel_id"
                                   value="{{ old('tiktok_pixel_id', $settings['tiktok_pixel_id']) }}"
                                   placeholder="{{ $defaults['tiktok_pixel_id'] }}"
                                   class="{{ $inputClass }}">
                            @error('tiktok_pixel_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="tiktok_test_event_code" class="{{ $labelClass }}">Test Event Code</label>
                            <input type="text" name="tiktok_test_event_code" id="tiktok_test_event_code"
                                   value="{{ old('tiktok_test_event_code', $settings['tiktok_test_event_code']) }}"
                                   placeholder="{{ $defaults['tiktok_test_event_code'] }}"
                                   class="{{ $inputClass }}">
                            @error('tiktok_test_event_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            <p class="{{ $hintClass }}">Để trống khi chạy sự kiện live.</p>
                        </div>
                    </div>
                </section>

                {{-- ChatGPT --}}
                <section id="section-chatgpt" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden scroll-mt-6">
                    <header class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-white px-5 py-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-xs font-bold text-white">AI</div>
                        <h2 class="text-base font-bold text-gray-900">ChatGPT Ads Pixel</h2>
                    </header>
                    <div class="p-5 space-y-4">
                        <div>
                            <label for="openai_pixel_id" class="{{ $labelClass }}">Pixel ID <span class="font-normal text-gray-500">(bắt buộc để bật)</span></label>
                            <input type="text" name="openai_pixel_id" id="openai_pixel_id"
                                   value="{{ old('openai_pixel_id', $settings['openai_pixel_id']) }}"
                                   placeholder="{{ $defaults['openai_pixel_id'] }}"
                                   class="{{ $inputClass }}">
                            @error('openai_pixel_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            <p class="{{ $hintClass }}">Tạo trong tab <strong>Conversions</strong> của Ads Manager.</p>
                        </div>
                        <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 cursor-pointer hover:bg-gray-50 transition">
                            <input type="checkbox" name="openai_pixel_debug" value="1"
                                   class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                   @checked(old('openai_pixel_debug', $settings['openai_pixel_debug']) === '1')>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-900">Chế độ debug</span>
                                <span class="mt-0.5 block text-xs text-gray-500">Log SDK ra browser console khi test. Tắt trước production.</span>
                            </span>
                        </label>
                    </div>
                </section>

                {{-- Pinterest --}}
                <section id="section-pinterest" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden scroll-mt-6">
                    <header class="flex items-center gap-3 border-b border-gray-100 bg-gradient-to-r from-red-50 to-white px-5 py-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#E60023] text-xs font-bold text-white">P</div>
                        <h2 class="text-base font-bold text-gray-900">Pinterest Tag</h2>
                    </header>
                    <div class="p-5 space-y-4">
                        <div>
                            <label for="pinterest_tag_id" class="{{ $labelClass }}">Tag ID</label>
                            <input type="text" name="pinterest_tag_id" id="pinterest_tag_id"
                                   inputmode="numeric" pattern="[0-9]*"
                                   value="{{ old('pinterest_tag_id', $settings['pinterest_tag_id']) }}"
                                   placeholder="{{ $defaults['pinterest_tag_id'] }}"
                                   class="{{ $inputClass }}">
                            @error('pinterest_tag_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            <p class="{{ $hintClass }}">Pinterest Ads → Conversions → Pinterest Tag (chỉ số).</p>
                        </div>
                        <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 cursor-pointer hover:bg-gray-50 transition">
                            <input type="checkbox" name="pinterest_test_mode" value="1"
                                   class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500"
                                   @checked(old('pinterest_test_mode', $settings['pinterest_test_mode']) === '1')>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-900">Chế độ debug</span>
                                <span class="mt-0.5 block text-xs text-gray-500">Dùng khi kiểm tra bằng Tag Helper hoặc Events Manager.</span>
                            </span>
                        </label>
                        <div class="rounded-lg border border-red-100 bg-red-50/60 px-3 py-2.5">
                            <p class="text-xs font-semibold text-gray-800 mb-1">Sự kiện tự động trên shop</p>
                            <ul class="text-xs text-gray-600 list-disc list-inside space-y-0.5">
                                <li><code>pagevisit</code> — catalog / trang sản phẩm</li>
                                <li><code>addtocart</code> — thêm vào giỏ</li>
                                <li><code>checkout</code> — trang cảm ơn (không gửi ở form checkout)</li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ── Theme ── --}}
            <section id="section-theme" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden scroll-mt-6">
                <header class="flex items-start gap-4 border-b border-gray-100 bg-gradient-to-r from-violet-50 to-white px-6 py-5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-sm font-bold text-white">T</div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900">Theme colors</h2>
                        <p class="mt-0.5 text-sm text-gray-600">Màu header, footer và các vùng nền trên storefront. Định dạng <code>#RRGGBB</code> hoặc <code>rgb(...)</code>.</p>
                    </div>
                </header>
                <div class="p-6">
                    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-6">
                        <strong>Lưu vĩnh viễn:</strong> Chạy <code class="bg-amber-100 px-1 rounded text-xs">php artisan settings:export-theme</code> rồi commit <code class="bg-amber-100 px-1 rounded text-xs">config/theme.php</code> để không mất màu khi deploy.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach ([
                            'header_bg' => ['Header background', '#f8fafc'],
                            'header_border' => ['Header border', '#e2e8f0'],
                            'testimonials_bg' => ['Reviews / Testimonials', '#ffffff'],
                            'footer_faq_bg' => ['Footer FAQ', '#ffffff'],
                            'footer_bg' => ['Footer background', '#242B3D'],
                        ] as $field => [$fieldLabel, $placeholder])
                            <div>
                                <label for="{{ $field }}" class="{{ $labelClass }}">{{ $fieldLabel }}</label>
                                <input type="text" name="{{ $field }}" id="{{ $field }}"
                                       value="{{ old($field, $settings[$field]) }}"
                                       placeholder="{{ $placeholder }}"
                                       class="{{ $inputClass }}">
                                @error($field)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ── Mail ── --}}
            <section id="section-mail" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden scroll-mt-6">
                <header class="flex items-start gap-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-white px-6 py-5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">@</div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900">Mail branding</h2>
                        <p class="mt-0.5 text-sm text-gray-600">Logo và tên thương hiệu trong header email. Để trống dùng logo mặc định và <code>APP_NAME</code>.</p>
                    </div>
                </header>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="mail_logo_url" class="{{ $labelClass }}">Logo URL</label>
                        <input type="text" name="mail_logo_url" id="mail_logo_url"
                               value="{{ old('mail_logo_url', $settings['mail_logo_url']) }}"
                               placeholder="VD: {{ asset('images/logo to.png') }}"
                               class="{{ $inputClass }}">
                        @error('mail_logo_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="{{ $hintClass }}">URL đầy đủ hoặc relative, VD: <code>/images/logo.png</code></p>
                    </div>
                    <div>
                        <label for="mail_brand_name" class="{{ $labelClass }}">Tên thương hiệu (alt logo)</label>
                        <input type="text" name="mail_brand_name" id="mail_brand_name"
                               value="{{ old('mail_brand_name', $settings['mail_brand_name']) }}"
                               placeholder="{{ $defaults['mail_brand_name'] ?: config('app.name') }}"
                               class="{{ $inputClass }}">
                        @error('mail_brand_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- ── GMC ── --}}
            <section id="section-gmc" class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden scroll-mt-6">
                <header class="flex items-start gap-4 border-b border-gray-100 bg-gradient-to-r from-orange-50 to-white px-6 py-5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-500 text-sm font-bold text-white">GMC</div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900">GMC Safe Mode</h2>
                        <p class="mt-0.5 text-sm text-gray-600">Ẩn social proof trên trang sản phẩm khi kiểm tra Google Merchant Center.</p>
                    </div>
                </header>
                <div class="p-6">
                    <label for="show_product_social_proof" class="flex items-start gap-3 rounded-xl border border-gray-200 p-4 cursor-pointer hover:bg-gray-50 transition">
                        <input type="checkbox" name="show_product_social_proof" id="show_product_social_proof" value="1"
                               @checked(old('show_product_social_proof', $settings['show_product_social_proof']))
                               class="mt-0.5 h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-900">Hiển thị "viewing" và "in carts" ở trang sản phẩm</span>
                            <span class="mt-1 block text-xs text-gray-500">Tắt để ẩn hoàn toàn 2 chỉ số này trên storefront.</span>
                        </span>
                    </label>
                    @error('show_product_social_proof')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- Sticky actions --}}
            <div class="fixed bottom-0 inset-x-0 z-30 border-t border-gray-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-3">
                    <p class="hidden sm:block text-sm text-gray-500">Thay đổi chưa lưu sẽ mất khi rời trang.</p>
                    <div class="flex items-center gap-3 ml-auto">
                        <a href="{{ route('admin.dashboard') }}"
                           class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition text-sm font-medium">
                            Quay lại
                        </a>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm">
                            Lưu thay đổi
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
