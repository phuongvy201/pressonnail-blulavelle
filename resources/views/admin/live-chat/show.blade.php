@extends('layouts.admin')

@section('title', 'Chat with ' . $conversation->customer_name)

@section('content')
<div class="space-y-6" x-data="liveChatShow({{ $conversation->id }})" data-customer-name="{{ e($conversation->customer_name) }}">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.live-chat.index') }}" class="text-sm text-gray-600 hover:text-gray-900 mb-2 inline-block">← Chat list</a>
            <h1 class="text-2xl font-bold text-gray-900">Chat with {{ $conversation->customer_name }}</h1>
            <p class="text-sm text-gray-500">{{ $conversation->customer_email ?? '—' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col" style="height: 560px;">
        <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-3">
            <template x-if="messages.length === 0">
                <p class="text-sm text-gray-500 text-center py-8">No messages yet.</p>
            </template>
            <template x-for="m in messages" :key="m.id">
                <div :class="m.is_from_customer ? 'flex justify-start' : 'flex justify-end'">
                    <div :class="m.is_from_customer ? 'bg-gray-100 text-gray-900' : 'text-white'"
                         class="max-w-[80%] rounded-xl px-4 py-2 text-sm shadow-sm"
                         :style="!m.is_from_customer ? 'background:#f0427c' : ''">
                        <p class="text-xs font-semibold opacity-90 mb-1" x-text="m.is_from_customer ? 'Customer' : 'You'"></p>
                        <p x-text="m.body" class="whitespace-pre-wrap"></p>
                        <p class="text-xs mt-1 opacity-80" x-text="formatTime(m.created_at)"></p>
                    </div>
                </div>
            </template>
        </div>
        <div class="p-4 border-t border-gray-200">
            <form @submit.prevent="sendReply" class="flex gap-2">
                <input type="text" x-model="replyBody" placeholder="Enter message..."
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary">
                <button type="submit" :disabled="sending"
                        class="px-6 py-3 rounded-xl font-semibold text-white transition-opacity disabled:opacity-50"
                        style="background: #f0427c;">
                    Send
                </button>
            </form>
        </div>
    </div>
</div>

@php
    $messagesForJs = $conversation->messages->sortBy('id')->values()->map(function ($m) {
        return [
            'id' => $m->id,
            'is_from_customer' => $m->is_from_customer,
            'body' => $m->body,
            'created_at' => $m->created_at->toIso8601String(),
        ];
    })->values();
@endphp

<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('liveChatShow', function(conversationId) {
        return {
            conversationId: conversationId,
            customerName: 'Customer',
            messages: @json($messagesForJs),
            lastSeenMessageId: {{ $conversation->messages->max('id') ?? 0 }},
            replyBody: '',
            sending: false,
            pollInterval: null,
            formatTime(iso) {
                if (!iso) return '';
                var d = new Date(iso);
                return d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
            },
            fetchMessages() {
                var self = this;
                fetch('{{ url("/admin/live-chat") }}/' + this.conversationId + '/messages', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(r => r.json()).then(data => {
                    if (!data.success || !data.messages) return;
                    var maxId = Math.max(0, ...data.messages.map(function(m) { return m.id; }));
                    self.lastSeenMessageId = maxId;
                    self.messages = data.messages;
                }).catch(() => {});
            },
            sendReply() {
                var body = (this.replyBody || '').trim();
                if (!body || this.sending) return;
                this.sending = true;
                fetch('{{ url("/admin/live-chat") }}/' + this.conversationId + '/reply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ body: body })
                }).then(r => r.json()).then(data => {
                    if (data.success && data.message) {
                        this.messages.push(data.message);
                        this.replyBody = '';
                    }
                }).catch(() => {}).finally(() => { this.sending = false; });
            },
            init() {
                var self = this;
                this.customerName = (this.$el && this.$el.getAttribute('data-customer-name')) || 'Customer';
                this.pollInterval = setInterval(function() { self.fetchMessages(); }, 3000);
            },
            destroy() {
                if (this.pollInterval) clearInterval(this.pollInterval);
            }
        };
    });
});
</script>
@endsection
