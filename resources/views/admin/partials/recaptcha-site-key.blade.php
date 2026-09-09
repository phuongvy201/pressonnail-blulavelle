{{-- reCAPTCHA site key (public). Secret stays in .env only. --}}
@php
    $recaptcha = $recaptcha ?? ['required' => false, 'siteKey' => null, 'provider' => 'recaptcha'];
@endphp
<div class="rounded-xl border border-slate-200 bg-slate-50 p-4 {{ $class ?? '' }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-gray-900">Google reCAPTCHA — Site key (iOS / web)</p>
            <p class="mt-1 text-xs text-gray-600">
                Public key cho widget và SDK iOS. App lấy qua
                <code class="rounded bg-white px-1 py-0.5">GET /api/v1/auth/captcha</code>.
                Secret key không hiển thị.
            </p>
        </div>
        @if(!empty($recaptcha['required']))
            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">Bắt buộc khi register</span>
        @else
            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Chưa bật (thiếu secret .env)</span>
        @endif
    </div>
    <div class="mt-3 flex items-center gap-3">
        <div class="flex-1 rounded-lg border border-gray-200 bg-white p-3">
            @if(!empty($recaptcha['siteKey']))
                <code id="recaptcha-site-key" class="text-sm font-mono text-gray-800 break-all select-all">{{ $recaptcha['siteKey'] }}</code>
            @else
                <span class="text-sm text-gray-500">Chưa có <code>RECAPTCHA_SITE_KEY</code> trong <code>.env</code></span>
            @endif
        </div>
        @if(!empty($recaptcha['siteKey']))
            <button type="button"
                    onclick="navigator.clipboard.writeText(@json($recaptcha['siteKey']))"
                    class="shrink-0 rounded-lg bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">
                Copy
            </button>
        @endif
    </div>
    <p class="mt-2 text-xs text-gray-500">
        Cấu hình: <code>RECAPTCHA_SITE_KEY</code> + <code>RECAPTCHA_SECRET_KEY</code> trong <code>.env</code>.
    </p>
</div>
