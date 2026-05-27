@extends('layouts.creator')

@section('title', 'Promo codes')

@section('content')
    <div class="mx-auto max-w-5xl px-5 py-12 md:px-16">
        <div>
            <a href="{{ route('creator.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
            <h1 class="creator-font-headline mt-2 text-3xl font-bold text-[#0b1c30]">Your promo codes</h1>
            <p class="mt-2 max-w-2xl text-sm text-[#404753]">
                Codes are created and assigned by {{ config('app.name') }} admin.
                Share them with your audience — checkout using your code attributes the order to you
                (<code class="rounded bg-[#e5eeff] px-1">{{ $affiliate->code }}</code>), even over a ref link.
            </p>
            <p class="mt-2 text-xs text-[#707884]">
                Need a new code? Contact the team — creators cannot create or edit discount settings here.
            </p>
        </div>

        <form method="get" action="{{ route('creator.promo-codes.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
            <div>
                <label for="status" class="creator-font-label text-xs font-semibold uppercase tracking-wide text-[#707884]">Status</label>
                <select name="status" id="status" onchange="this.form.submit()"
                        class="mt-1 rounded-lg border border-[#bfc7d5] bg-white px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-xl border border-[#bfc7d5] bg-white shadow-sm">
            @if ($promoCodes->isEmpty())
                <p class="p-8 text-center text-sm text-[#707884]">
                    No promo codes assigned to your account yet. Ask admin to create a code and link it to your affiliate profile.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-[#bfc7d5] bg-[#f8f9ff] text-xs uppercase tracking-wide text-[#707884]">
                            <tr>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Discount</th>
                                <th class="px-4 py-3">Uses</th>
                                <th class="px-4 py-3">Validity</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#bfc7d5]/60">
                            @foreach ($promoCodes as $promo)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span id="promo-code-{{ $promo->id }}" class="font-mono font-semibold text-[#0b1c30]">{{ $promo->code }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-[#404753]">
                                        @if ($promo->type === 'percentage')
                                            {{ rtrim(rtrim(number_format((float) $promo->value, 2), '0'), '.') }}%
                                        @else
                                            ${{ number_format((float) $promo->value, 2) }}
                                        @endif
                                        @if ($promo->min_order_value)
                                            <span class="block text-xs text-[#707884]">min ${{ number_format((float) $promo->min_order_value, 0) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[#404753]">
                                        {{ $promo->used_count }}{{ $promo->max_uses !== null ? ' / '.$promo->max_uses : '' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-[#707884]">
                                        @if ($promo->starts_at)
                                            <span>From {{ $promo->starts_at->format('M j, Y') }}</span><br>
                                        @endif
                                        @if ($promo->expires_at)
                                            <span>Until {{ $promo->expires_at->format('M j, Y') }}</span>
                                        @elseif (! $promo->starts_at)
                                            <span>No expiry</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($promo->isValid())
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">Ready to share</span>
                                        @elseif ($promo->is_active)
                                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900">Scheduled / limit</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button"
                                                onclick="copyPromoCode('promo-code-{{ $promo->id }}', this)"
                                                class="creator-font-label text-sm font-semibold text-primary hover:underline">
                                            Copy
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($promoCodes->hasPages())
                    <div class="border-t border-[#bfc7d5] px-4 py-3">
                        {{ $promoCodes->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
function copyPromoCode(elementId, btn) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const text = el.textContent.trim();
    const done = () => {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => { btn.textContent = orig; }, 2000);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
    } else {
        fallbackCopy(text, done);
    }
}
function fallbackCopy(text, done) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); done(); } catch (e) { alert('Copy failed'); }
    document.body.removeChild(ta);
}
</script>
@endpush
