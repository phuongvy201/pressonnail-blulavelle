@php
    $classes = match ($status) {
        'pending' => 'bg-amber-100 text-amber-900',
        'approved' => 'bg-blue-100 text-blue-900',
        'shipped' => 'bg-indigo-100 text-indigo-900',
        'delivered' => 'bg-emerald-100 text-emerald-900',
        'rejected' => 'bg-red-100 text-red-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp
<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $classes }}">{{ ucfirst($status) }}</span>
