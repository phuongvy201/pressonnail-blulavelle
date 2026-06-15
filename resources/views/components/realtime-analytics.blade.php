@if(config('services.google.analytics.property_id'))
<div id="realtime-analytics-widget" class="hidden fixed bottom-4 right-4 bg-white rounded-lg shadow-lg p-4 max-w-xs z-50 border border-gray-200">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-800">Người đang online</h3>
        <button onclick="toggleRealtimeWidget()" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <div id="realtime-content">
        <div class="flex items-center space-x-2 mb-3">
            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
            <span id="realtime-total" class="text-2xl font-bold text-gray-900">0</span>
            <span class="text-sm text-gray-600">người đang xem</span>
        </div>
        
        <div id="realtime-pages" class="space-y-2 max-h-48 overflow-y-auto">
            <p class="text-xs text-gray-500">Đang tải...</p>
        </div>
        
        <div class="mt-3 pt-3 border-t border-gray-200">
            <p class="text-xs text-gray-400 text-center">Cập nhật mỗi 30 giây khi bật widget</p>
        </div>
    </div>
    
    <div id="realtime-error" class="hidden text-xs text-red-600">
        Không thể tải dữ liệu realtime
    </div>
</div>

<script>
let realtimeUpdateInterval;
let widgetVisible = false;

function toggleRealtimeWidget() {
    const widget = document.getElementById('realtime-analytics-widget');
    widgetVisible = !widgetVisible;
    if (widgetVisible) {
        widget.classList.remove('hidden');
        startRealtimeUpdates();
    } else {
        widget.classList.add('hidden');
        stopRealtimeUpdates();
    }
}

function startRealtimeUpdates() {
    stopRealtimeUpdates();
    fetchRealtimeData();
    realtimeUpdateInterval = setInterval(fetchRealtimeData, 30000);
}

function stopRealtimeUpdates() {
    if (realtimeUpdateInterval) {
        clearInterval(realtimeUpdateInterval);
        realtimeUpdateInterval = null;
    }
}

async function fetchRealtimeData() {
    try {
        const response = await fetch('{{ route("api.analytics.realtime") }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const result = await response.json();
        
        if (result.success && result.data) {
            updateRealtimeDisplay(result.data);
            document.getElementById('realtime-error').classList.add('hidden');
            document.getElementById('realtime-content').classList.remove('hidden');
        } else {
            throw new Error(result.message || 'Failed to fetch data');
        }
    } catch (error) {
        console.error('Realtime analytics error:', error);
        document.getElementById('realtime-content').classList.add('hidden');
        document.getElementById('realtime-error').classList.remove('hidden');
    }
}

function updateRealtimeDisplay(data) {
    // Cập nhật tổng số người đang online
    const totalElement = document.getElementById('realtime-total');
    if (totalElement && data.total_active_users !== undefined) {
        totalElement.textContent = data.total_active_users;
    }

    // Cập nhật danh sách trang đang xem
    const pagesElement = document.getElementById('realtime-pages');
    if (pagesElement && data.pages && data.pages.length > 0) {
        pagesElement.innerHTML = data.pages.slice(0, 5).map(item => {
            const pageName = item.page === '/' ? 'Trang chủ' : item.page;
            return `
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-700 truncate flex-1 mr-2" title="${item.page}">${pageName}</span>
                    <span class="text-gray-500 font-medium">${item.users}</span>
                </div>
            `;
        }).join('');
    } else if (pagesElement) {
        pagesElement.innerHTML = '<p class="text-xs text-gray-500">Chưa có dữ liệu</p>';
    }
}

// Widget ẩn mặc định — chỉ poll khi admin bật thủ công (toggleRealtimeWidget).
window.addEventListener('beforeunload', function() {
    stopRealtimeUpdates();
});
</script>
@endif


