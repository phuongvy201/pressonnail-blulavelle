@extends('layouts.admin')

@section('title', 'Google Analytics Dashboard')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="analyticsDashboard()">
    <!-- Top Header with Tabs -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
            <h1 class="text-2xl font-bold text-gray-900">Google Analytics</h1>
            <div class="flex items-center gap-4 flex-wrap">
                <!-- Days Selector -->
                <select 
                    x-model="days" 
                    @change="updateFilters()"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                >
                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="14" {{ $days == 14 ? 'selected' : '' }}>Last 14 Days</option>
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="60" {{ $days == 60 ? 'selected' : '' }}>Last 60 Days</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 Days</option>
                </select>
                
                <div class="flex items-center gap-2">
                    <button class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg" title="Export">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </button>
                    <button class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg" title="Share">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.885 12.938 9 12.482 9 12c0-.482-.115-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Tabs -->
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px space-x-8">
                <button 
                    @click="switchTab('acquisition')"
                    :class="activeTab === 'acquisition' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                >
                    Acquisition
                </button>
                <button 
                    @click="switchTab('audience')"
                    :class="activeTab === 'audience' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                >
                    Audience
                </button>
                <button 
                    @click="switchTab('conversions')"
                    :class="activeTab === 'conversions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                >
                    Conversions
                </button>
                <button 
                    @click="switchTab('pages')"
                    :class="activeTab === 'pages' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                >
                    Pages
                </button>
                <button 
                    @click="switchTab('events')"
                    :class="activeTab === 'events' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                >
                    Events
                </button>
            </nav>
        </div>
    </div>


    <!-- Acquisition Tab Content -->
    <div x-show="activeTab === 'acquisition'" x-transition>
        @php
            $summary = $summaryMetrics ?? [];
            $sessions = $summary['sessions'] ?? 0;
            $avgDuration = $summary['avg_session_duration'] ?? 0;
            $newSessionsPercent = $summary['new_sessions_percent'] ?? 0;
            $bounceRate = ($summary['bounce_rate'] ?? 0) * 100;
            $goalCompletions = $summary['goal_completions'] ?? 0;
            $pagesPerSession = $summary['pages_per_session'] ?? 0;
            
            // Format duration
            $hours = floor($avgDuration / 3600);
            $minutes = floor(($avgDuration % 3600) / 60);
            $seconds = floor($avgDuration % 60);
            $formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        @endphp

        <!-- Acquisition Sub-Navigation -->
        <div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200 pb-4">
            <button 
                @click="setFilter('all')"
                :class="filter === 'all' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium border transition"
            >
                All
            </button>
            <button 
                @click="setFilter('organic-search')"
                :class="filter === 'organic-search' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium border transition"
            >
                Organic Search
            </button>
            <button 
                @click="setFilter('paid-search')"
                :class="filter === 'paid-search' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium border transition"
            >
                Paid Search
            </button>
            <button 
                @click="setFilter('direct')"
                :class="filter === 'direct' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium border transition"
            >
                Direct
            </button>
            <button 
                @click="setFilter('social')"
                :class="filter === 'social' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium border transition"
            >
                Social
            </button>
            <button 
                @click="setFilter('referrals')"
                :class="filter === 'referrals' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium border transition"
            >
                Referrals
            </button>
            <button 
                @click="setFilter('display')"
                :class="filter === 'display' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium border transition"
            >
                Display
            </button>
            <button 
                @click="setFilter('email')"
                :class="filter === 'email' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium border transition"
            >
                Email
            </button>
            <button 
                @click="setFilter('other')"
                :class="filter === 'other' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg text-sm font-medium border transition"
            >
                Other
            </button>
        </div>

        <!-- Top Row: Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Sessions Line Chart -->
            @if(isset($sessionsByDate) && count($sessionsByDate) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Sessions</h3>
                    </div>
                    <div class="mb-4">
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($sessions) }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span class="text-sm text-green-600 font-medium">14%</span>
                        </div>
                    </div>
                    <div style="height: 250px;">
                        <canvas id="sessionsChart"></canvas>
                    </div>
                </div>
            @endif

            <!-- Sessions Donut Chart -->
            @if(isset($channels) && count($channels) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Sessions</h3>
                    </div>
                    <div class="flex items-center justify-center mb-4">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-gray-900">{{ number_format(array_sum(array_column($channels, 'sessions'))) }}</p>
                            <p class="text-sm text-gray-600">Sessions</p>
                        </div>
                    </div>
                    <div style="height: 250px;">
                        <canvas id="channelsChart"></canvas>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                        @php
                            $chartColors = [
                                'rgba(59, 130, 246, 0.8)',   // Blue
                                'rgba(139, 92, 246, 0.8)',   // Purple
                                'rgba(236, 72, 153, 0.8)',   // Pink
                                'rgba(251, 146, 60, 0.8)',   // Orange
                                'rgba(16, 185, 129, 0.8)',   // Green
                                'rgba(34, 197, 94, 0.8)',     // Green 2
                            ];
                        @endphp
                        @foreach($channels as $index => $channel)
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $chartColors[$index % count($chartColors)] }}"></div>
                                <span class="text-gray-700">{{ $channel['channel'] ?? 'N/A' }}:</span>
                                <span class="font-semibold text-gray-900">{{ number_format($channel['sessions'] ?? 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Middle Row: KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <!-- Sessions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2 truncate">Sessions</p>
                <p class="text-2xl font-bold text-gray-900 mb-1">{{ number_format($sessions) }}</p>
                <p class="text-xs text-red-600">-23%</p>
            </div>

            <!-- Avg. Session Duration -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2 truncate">Avg. Ses...</p>
                <p class="text-2xl font-bold text-gray-900 mb-1">{{ $formattedDuration }}</p>
                <p class="text-xs text-red-600">-10%</p>
            </div>

            <!-- % New Sessions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2 truncate">% New S...</p>
                <p class="text-2xl font-bold text-gray-900 mb-1">{{ number_format($newSessionsPercent, 2) }}%</p>
                <p class="text-xs text-red-600">-7%</p>
            </div>

            <!-- Bounce Rate -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2 truncate">Bounce...</p>
                <p class="text-2xl font-bold text-gray-900 mb-1">{{ number_format($bounceRate, 2) }}%</p>
                <p class="text-xs text-red-600">-68%</p>
            </div>

            <!-- Goal Completions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2 truncate">Goal Co...</p>
                <p class="text-2xl font-bold text-gray-900 mb-1">{{ number_format($goalCompletions) }}</p>
                <p class="text-xs text-green-600">+80%</p>
            </div>

            <!-- Pages/Session -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase mb-2 truncate">Pages/Se...</p>
                <p class="text-2xl font-bold text-gray-900 mb-1">{{ number_format($pagesPerSession, 2) }}</p>
                <p class="text-xs text-red-600">-9%</p>
            </div>
        </div>

        <!-- Bottom: Channels Table -->
        @if(isset($channels) && count($channels) > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Channels Overview</h3>
                        <p class="text-sm text-gray-600 mt-1">Showing {{ count($channels) }} of {{ count($channels) }} Rows</p>
                    </div>
                    <input 
                        type="text" 
                        placeholder="Search..." 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        x-model="searchChannel"
                        @input="filterChannels()"
                    >
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Channel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Session Du...</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% New Sessions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bounce Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Goal Completio...</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pages/Session</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="channelsTable">
                            @foreach($channels as $channel)
                                @php
                                    $channelSessions = $channel['sessions'] ?? 0;
                                    $newUsers = $channel['new_users'] ?? 0;
                                    $newSessionsPercent = $channelSessions > 0 ? round(($newUsers / $channelSessions) * 100, 2) : 0;
                                    
                                    $duration = $channel['avg_session_duration'] ?? 0;
                                    $h = floor($duration / 3600);
                                    $m = floor(($duration % 3600) / 60);
                                    $s = floor($duration % 60);
                                    $formattedDur = sprintf('%02d:%02d:%02d', $h, $m, $s);
                                    
                                    $bounceRatePercent = ($channel['bounce_rate'] ?? 0) * 100;
                                    $pagesPerSession = $channelSessions > 0 ? round(($channel['page_views'] ?? 0) / $channelSessions, 2) : 0;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $channel['channel'] ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($channelSessions) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $formattedDur }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($newSessionsPercent, 2) }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($bounceRatePercent, 2) }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        -
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $pagesPerSession > 0 ? number_format($pagesPerSession, 2) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Traffic Sources Section -->
        @if(isset($trafficSources) && count($trafficSources) > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Traffic Sources - Chi tiết nguồn truy cập</h2>
                    <p class="text-gray-600">Hiển thị chi tiết lượt truy cập từ TikTok, Facebook, Pinterest, Google, v.v.</p>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Danh sách nguồn truy cập</h3>
                        <div class="text-sm text-gray-600">
                            Tổng: {{ count($trafficSources) }} nguồn
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medium</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Session Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Users</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bounce Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page Views</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($trafficSources as $source)
                                    @php
                                        $duration = $source['avg_session_duration'] ?? 0;
                                        $h = floor($duration / 3600);
                                        $m = floor(($duration % 3600) / 60);
                                        $s = floor($duration % 60);
                                        $formattedDur = sprintf('%02d:%02d:%02d', $h, $m, $s);
                                        
                                        $bounceRatePercent = ($source['bounce_rate'] ?? 0) * 100;
                                        
                                        // Badge color cho source type
                                        $sourceTypeColors = [
                                            'Google' => 'bg-blue-100 text-blue-800',
                                            'Bing' => 'bg-green-100 text-green-800',
                                            'Organic Search' => 'bg-purple-100 text-purple-800',
                                            'Paid Search' => 'bg-yellow-100 text-yellow-800',
                                            'Direct' => 'bg-gray-100 text-gray-800',
                                            'Facebook' => 'bg-blue-100 text-blue-800',
                                            'TikTok' => 'bg-black text-white',
                                            'Pinterest' => 'bg-red-100 text-red-800',
                                            'Instagram' => 'bg-pink-100 text-pink-800',
                                            'Twitter/X' => 'bg-sky-100 text-sky-800',
                                            'YouTube' => 'bg-red-100 text-red-800',
                                            'LinkedIn' => 'bg-blue-100 text-blue-800',
                                            'Referral' => 'bg-indigo-100 text-indigo-800',
                                            'Email' => 'bg-green-100 text-green-800',
                                            'Display' => 'bg-orange-100 text-orange-800',
                                            'Other' => 'bg-gray-100 text-gray-800',
                                        ];
                                        $sourceTypeColor = $sourceTypeColors[$source['source_type'] ?? 'Other'] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sourceTypeColor }}">
                                                {{ $source['source_type'] ?? 'Other' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $source['source'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $source['medium'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                            {{ number_format($source['sessions'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $formattedDur }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($source['new_users'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($bounceRatePercent, 2) }}%
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($source['page_views'] ?? 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary by Source Type -->
                @php
                    $sourceTypeSummary = [];
                    foreach ($trafficSources as $source) {
                        $type = $source['source_type'] ?? 'Other';
                        if (!isset($sourceTypeSummary[$type])) {
                            $sourceTypeSummary[$type] = [
                                'sessions' => 0,
                                'new_users' => 0,
                                'page_views' => 0,
                                'count' => 0
                            ];
                        }
                        $sourceTypeSummary[$type]['sessions'] += $source['sessions'] ?? 0;
                        $sourceTypeSummary[$type]['new_users'] += $source['new_users'] ?? 0;
                        $sourceTypeSummary[$type]['page_views'] += $source['page_views'] ?? 0;
                        $sourceTypeSummary[$type]['count']++;
                    }
                    arsort($sourceTypeSummary);
                @endphp

                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tổng hợp theo loại nguồn</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($sourceTypeSummary as $type => $summary)
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-gray-900">{{ $type }}</h4>
                                    <span class="text-xs text-gray-500">{{ $summary['count'] }} nguồn</span>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Sessions:</span>
                                        <span class="font-semibold text-gray-900">{{ number_format($summary['sessions']) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">New Users:</span>
                                        <span class="font-semibold text-gray-900">{{ number_format($summary['new_users']) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Page Views:</span>
                                        <span class="font-semibold text-gray-900">{{ number_format($summary['page_views']) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Audience Tab -->
    <div x-show="activeTab === 'audience'" x-transition>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @if(isset($demographics) && count($demographics) > 0)
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Nhân khẩu học</h3>
                    <div style="height: 400px;">
                        <canvas id="demographicsChart"></canvas>
                    </div>
                </div>
            @endif

            @if(isset($devices) && count($devices) > 0)
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Thiết bị</h3>
                    <div style="height: 400px;">
                        <canvas id="devicesChart"></canvas>
                    </div>
                </div>
            @endif

            @if((!isset($demographics) || count($demographics) == 0) && (!isset($devices) || count($devices) == 0))
                <div class="text-center py-12">
                    <p class="text-gray-500">Không có dữ liệu để hiển thị</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Conversions Tab -->
    <div x-show="activeTab === 'conversions'" x-transition>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @if(isset($conversions) && count($conversions) > 0)
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Conversions</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($conversions as $conversion)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $conversion['event_name'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($conversion['count'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($conversion['value'] ?? 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500">Không có dữ liệu conversions để hiển thị</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Pages Tab -->
    <div x-show="activeTab === 'pages'" x-transition>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @if(isset($topPages) && count($topPages) > 0)
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Trang được xem nhiều nhất</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trang</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pageviews</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unique Pageviews</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Time</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach(array_slice($topPages, 0, 20) as $page)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                            <a href="{{ $page['page_path'] ?? '#' }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                                {{ $page['page_path'] ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($page['pageviews'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($page['unique_pageviews'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($page['avg_time_on_page'] ?? 0, 1) }}s
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500">Không có dữ liệu trang để hiển thị</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Events Tab -->
    <div x-show="activeTab === 'events'" x-transition>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @if(isset($events) && count($events) > 0)
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Events</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Value</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach(array_slice($events, 0, 20) as $event)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $event['event_name'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($event['count'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($event['total_value'] ?? 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500">Không có dữ liệu events để hiển thị</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Domains Tab -->
    <div x-show="activeTab === 'domains'" x-transition>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            @if(isset($domains) && count($domains) > 0)
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Domains</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($domains as $domain)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $domain['domain'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($domain['sessions'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($domain['users'] ?? 0) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="?tab=domains&domain={{ urlencode($domain['domain'] ?? '') }}&days={{ $days }}" 
                                               class="text-blue-600 hover:text-blue-900">
                                                Xem chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-500">Không có dữ liệu domains để hiển thị</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function analyticsDashboard() {
    return {
        activeTab: '{{ $tab }}',
        days: {{ $days }},
        filter: '{{ $filter }}',
        searchChannel: '',
        
        switchTab(tab) {
            this.activeTab = tab;
            this.updateFilters();
        },
        
        setFilter(filterValue) {
            this.filter = filterValue;
            this.updateFilters();
        },
        
        filterChannels() {
            const searchTerm = this.searchChannel.toLowerCase();
            const rows = document.querySelectorAll('#channelsTable tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        },
        
        updateFilters() {
            const params = new URLSearchParams({
                tab: this.activeTab,
                days: this.days,
            });
            
            if (this.activeTab === 'acquisition' && this.filter !== 'all') {
                params.append('filter', this.filter);
            }
            
            window.location.href = '{{ route("admin.analytics.index") }}?' + params.toString();
        }
    }
}

// Chart Colors
const channelColors = [
    'rgba(59, 130, 246, 0.8)',   // Blue
    'rgba(139, 92, 246, 0.8)',   // Purple
    'rgba(236, 72, 153, 0.8)',   // Pink
    'rgba(251, 146, 60, 0.8)',   // Orange
    'rgba(16, 185, 129, 0.8)',   // Green
    'rgba(34, 197, 94, 0.8)',     // Green 2
];

// Sessions Line Chart
@if(isset($sessionsByDate) && count($sessionsByDate) > 0)
document.addEventListener('DOMContentLoaded', function() {
    const sessionsCtx = document.getElementById('sessionsChart');
    if (sessionsCtx) {
        const sessionsData = {!! json_encode($sessionsByDate) !!};
        const labels = sessionsData.map(d => d.date);
        const sessions = sessionsData.map(d => d.sessions);
        const pageViews = sessionsData.map(d => d.page_views || 0);
        
        new Chart(sessionsCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sessions',
                        data: sessions,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: false,
                        borderWidth: 2
                    },
                    {
                        label: 'Page Views',
                        data: pageViews,
                        borderColor: 'rgba(59, 130, 246, 0.5)',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
@endif

// Channels Donut Chart
@if(isset($channels) && count($channels) > 0)
document.addEventListener('DOMContentLoaded', function() {
    const channelsCtx = document.getElementById('channelsChart');
    if (channelsCtx) {
        const channelData = {!! json_encode($channels) !!};
        const labels = channelData.map(c => c.channel);
        const data = channelData.map(c => c.sessions);
        
        new Chart(channelsCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: channelColors.slice(0, labels.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });
    }
});
@endif

// Demographics Chart
@if(isset($demographics) && count($demographics) > 0)
document.addEventListener('DOMContentLoaded', function() {
    const demographicsCtx = document.getElementById('demographicsChart');
    if (demographicsCtx) {
        new Chart(demographicsCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_column($demographics, 'country')) !!},
                datasets: [{
                    label: 'Users',
                    data: {!! json_encode(array_column($demographics, 'users')) !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
@endif

// Devices Chart
@if(isset($devices) && count($devices) > 0)
document.addEventListener('DOMContentLoaded', function() {
    const devicesCtx = document.getElementById('devicesChart');
    if (devicesCtx) {
        new Chart(devicesCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode(array_column($devices, 'device_category')) !!},
                datasets: [{
                    data: {!! json_encode(array_column($devices, 'sessions')) !!},
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
});
@endif
</script>
@endsection
