@extends('layouts.admin')

@section('content')
@php
    $shapesText = old('shapes_text', implode("\n", $settings['shapes']));
    $lengthsText = old('lengths_text', implode("\n", $settings['lengths']));
    $tipsText = old('capture_tips_text', implode("\n", $settings['capture_tips']));
@endphp
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Virtual Nail Settings</h1>
            <p class="text-gray-600">Configure try-on prompt, shapes, lengths, notices, and provider strategy.</p>
        </div>
        <a href="{{ $storefrontUrl }}" target="_blank" rel="noopener"
           class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Open storefront page
        </a>
        <a href="{{ route('admin.virtual-nail-trials.index') }}"
           class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            View try-on history
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Provider status (read-only from .env) --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-gray-900 mb-3">AI providers (.env — not editable here)</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-semibold text-gray-800">Primary · chatgpt2api</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $providerStatus['chatgpt2api_configured'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $providerStatus['chatgpt2api_configured'] ? 'Configured' : 'Missing' }}
                    </span>
                </div>
                <p class="text-gray-600 break-all">{{ $providerStatus['chatgpt2api_base_url'] ?: '—' }}/images/edits</p>
                <p class="text-gray-500 mt-1">Model: {{ $providerStatus['chatgpt2api_model'] }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-semibold text-gray-800">Fallback · Images API (edits)</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $providerStatus['image_api_configured'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $providerStatus['image_api_configured'] ? 'Configured' : 'Missing' }}
                    </span>
                </div>
                <p class="text-gray-600 break-all">{{ $providerStatus['image_api_base_url'] ?: '—' }}/images/edits</p>
                <p class="text-gray-500 mt-1">Model: {{ $providerStatus['image_api_model'] }}</p>
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-500">Try-on dùng <code class="bg-gray-100 px-1 rounded">/images/edits</code> (ảnh tay). chatgpt2api được ưu tiên khi pool healthy. Docs: <a href="https://docs.newapi.pro/en/docs/api/ai-model/images/openai/post-v1-images-edits" target="_blank" rel="noopener" class="text-blue-600 underline">New API edits</a></p>
    </div>

    <form method="POST" action="{{ route('admin.settings.virtual-nail.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Feature --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-gray-900">Feature</h2>
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="enabled" value="1" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked(old('enabled', $settings['enabled']))>
                <span>
                    <span class="block font-semibold text-gray-900">Enable Virtual Nail Try-On</span>
                    <span class="block text-sm text-gray-500">Shows the storefront page, FAB, and API endpoints.</span>
                </span>
            </label>
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="prefer_browser_pool" value="1" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked(old('prefer_browser_pool', $settings['prefer_browser_pool']))>
                <span>
                    <span class="block font-semibold text-gray-900">Prefer chatgpt2api when pool is healthy</span>
                    <span class="block text-sm text-gray-500">chatgpt2api giữ đúng tay + móng sản phẩm (~90%). Khi pool lỗi → fallback <code>/images/edits</code> qua New API.</span>
                </span>
            </label>
        </section>

        {{-- Shapes & lengths --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
            <h2 class="text-lg font-bold text-gray-900">Shapes &amp; lengths</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nail shapes <span class="font-normal text-gray-400">(one per line)</span></label>
                    <textarea name="shapes_text" rows="8" class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm font-mono">{{ $shapesText }}</textarea>
                    @error('shapes_text')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nail lengths <span class="font-normal text-gray-400">(one per line)</span></label>
                    <textarea name="lengths_text" rows="8" class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm font-mono">{{ $lengthsText }}</textarea>
                    @error('lengths_text')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Default shape</label>
                    <input type="text" name="default_shape" value="{{ old('default_shape', $settings['default_shape']) }}"
                           class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm"
                           list="vnt-shape-list">
                    <datalist id="vnt-shape-list">
                        @foreach($settings['shapes'] as $shape)
                            <option value="{{ $shape }}"></option>
                        @endforeach
                    </datalist>
                    @error('default_shape')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Default length</label>
                    <input type="text" name="default_length" value="{{ old('default_length', $settings['default_length']) }}"
                           class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm"
                           list="vnt-length-list">
                    <datalist id="vnt-length-list">
                        @foreach($settings['lengths'] as $length)
                            <option value="{{ $length }}"></option>
                        @endforeach
                    </datalist>
                    @error('default_length')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- Prompt --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">AI prompt template</h2>
                    <p class="text-sm text-gray-500 mt-1">Placeholders: <code class="bg-gray-100 px-1 rounded">{shape}</code> <code class="bg-gray-100 px-1 rounded">{length}</code> <code class="bg-gray-100 px-1 rounded">{product_name}</code></p>
                </div>
                <button type="submit" form="vnt-reset-prompt"
                        class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        onclick="return confirm('Reset prompt to the built-in default?')">
                    Reset to default
                </button>
            </div>
            <textarea name="prompt_template" rows="14"
                      class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm font-mono leading-relaxed">{{ old('prompt_template', $settings['prompt_template']) }}</textarea>
            @error('prompt_template')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </section>

        {{-- Copy / notices --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
            <h2 class="text-lg font-bold text-gray-900">Storefront copy</h2>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Shape &amp; length notice title</label>
                <input type="text" name="notice_title" value="{{ old('notice_title', $settings['notice_title']) }}"
                       class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm">
                @error('notice_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Shape &amp; length notice text</label>
                <textarea name="notice_text" rows="3"
                          class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm">{{ old('notice_text', $settings['notice_text']) }}</textarea>
                @error('notice_text')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Capture tips <span class="font-normal text-gray-400">(one per line)</span></label>
                <textarea name="capture_tips_text" rows="5"
                          class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm">{{ $tipsText }}</textarea>
                @error('capture_tips_text')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </section>

        {{-- Rate limits --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
            <h2 class="text-lg font-bold text-gray-900">Rate limits (per IP / minute)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Browse (products / options / status)</label>
                    <input type="number" name="browse_rate_limit" min="30" max="600"
                           value="{{ old('browse_rate_limit', $settings['browse_rate_limit']) }}"
                           class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm">
                    @error('browse_rate_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Generate try-on</label>
                    <input type="number" name="rate_limit" min="1" max="60"
                           value="{{ old('rate_limit', $settings['rate_limit']) }}"
                           class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm">
                    @error('rate_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <button type="submit"
                    class="px-6 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-sm transition">
                Save settings
            </button>
        </div>
    </form>

    <form id="vnt-reset-prompt" method="POST" action="{{ route('admin.settings.virtual-nail.reset-prompt') }}" class="hidden">
        @csrf
    </form>
</div>
@endsection
