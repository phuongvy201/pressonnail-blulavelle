<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <p class="text-sm font-medium text-gray-600">Website purchases</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $channelStats['website']['orders'] }}</p>
        <p class="mt-2 text-sm text-gray-600">{{ $channelStats['website']['customers'] }} customers · ${{ number_format($channelStats['website']['revenue'], 2) }} paid</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6">
        <p class="text-sm font-medium text-gray-600">App purchases</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $channelStats['app']['orders'] }}</p>
        <p class="mt-2 text-sm text-gray-600">{{ $channelStats['app']['customers'] }} customers · ${{ number_format($channelStats['app']['revenue'], 2) }} paid</p>
    </div>
</div>
<p class="text-sm text-gray-500">Paid orders only. Orders placed before this change count as Website, except orders already linked to an app checkout.</p>
