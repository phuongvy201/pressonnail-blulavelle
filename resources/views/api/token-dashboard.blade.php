@extends('layouts.admin')

@section('title', 'API Token Dashboard')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">🔑 API Token Dashboard</h1>
        <p class="text-gray-600 mt-2">Quản lý API tokens để tích hợp với hệ thống</p>
    </div>

    @if($tokens->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-yellow-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800">Chưa có API Token</h3>
                    <p class="text-yellow-700 mt-1">Chạy lệnh: <code class="bg-yellow-100 px-2 py-1 rounded">php artisan db:seed --class=ApiTokenSeeder</code> để tạo token mới</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Active Tokens -->
    @foreach($tokens as $token)
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <!-- Token Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white">{{ $token->name }}</h2>
                    <p class="text-blue-100 text-sm mt-1">{{ $token->description }}</p>
                </div>
                <span class="px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded-full shadow-lg">
                    ✓ Active
                </span>
            </div>
        </div>

        <!-- Token Content -->
        <div class="p-6">
            <!-- Token Value -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">API Token:</label>
                <div class="flex items-center gap-3">
                    <div class="flex-1 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <code id="token-{{ $token->id }}" class="text-sm font-mono text-gray-800 break-all select-all">{{ $token->token }}</code>
                    </div>
                    <button 
                        onclick="copyToken('{{ $token->token }}', this)" 
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all duration-200 flex items-center gap-2 shadow-md hover:shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span class="copy-text">Copy</span>
                    </button>
                </div>
            </div>

            <!-- Permissions -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Permissions:</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($token->permissions as $permission)
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">
                            {{ $permission }}
                        </span>
                    @endforeach
                </div>
            </div>

            <!-- Token Info -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">Last Used</div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
                    </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">Usage Count</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $token->usage_count ?? 0 }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">Expires</div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ $token->expires_at ? $token->expires_at->format('Y-m-d') : 'Never' }}
                    </div>
                </div>
            </div>

            <!-- Security Warning -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-yellow-800 mb-1">⚠️ Bảo mật quan trọng!</h4>
                        <p class="text-sm text-yellow-700">Không chia sẻ token này với người khác. Token có quyền tạo và quản lý sản phẩm trong hệ thống.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Example -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <details class="cursor-pointer">
                <summary class="text-sm font-semibold text-gray-700 mb-2">📝 Cách sử dụng</summary>
                <div class="mt-4 space-y-4">
                    <!-- cURL Example -->
                    <div>
                        <h5 class="text-sm font-medium text-gray-700 mb-2">cURL:</h5>
                        <div class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto">
                            <pre class="text-xs font-mono">curl -X POST https://bluprinter.com/api/products/create \
  -H "X-API-Token: {{ $token->token }}" \
  -F "name=Test Product" \
  -F "template_id=1" \
  -F "images=@image.jpg"</pre>
                        </div>
                    </div>

                    <!-- Header Example -->
                    <div>
                        <h5 class="text-sm font-medium text-gray-700 mb-2">Header:</h5>
                        <div class="bg-gray-900 text-gray-100 rounded-lg p-4">
                            <code class="text-xs font-mono">X-API-Token: {{ $token->token }}</code>
                        </div>
                    </div>
                </div>
            </details>
        </div>
    </div>
    @endforeach

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        <a href="/api-docs.html" target="_blank" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">API Documentation</h3>
                    <p class="text-sm text-gray-600">Storefront API (X-API-Token)</p>
                </div>
            </div>
        </a>

        <a href="/api-v1-docs.html" target="_blank" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Native iOS API v1</h3>
                    <p class="text-sm text-gray-600">OpenAPI <code class="text-xs">/api/v1</code> (Bearer)</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.dashboard') }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Back to Dashboard</h3>
                    <p class="text-sm text-gray-600">Quay lại trang quản trị</p>
                </div>
            </div>
        </a>
    </div>
</div>

<script>
function copyToken(token, button) {
    navigator.clipboard.writeText(token).then(function() {
        const originalText = button.querySelector('.copy-text').textContent;
        button.querySelector('.copy-text').textContent = 'Copied!';
        button.classList.add('bg-green-600');
        button.classList.remove('bg-indigo-600');
        
        setTimeout(function() {
            button.querySelector('.copy-text').textContent = originalText;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-indigo-600');
        }, 2000);
    });
}
</script>
@endsection
