@if(\App\Support\VirtualNailSettings::enabled() && !request()->routeIs('virtual-nail.index'))
<div id="virtual-nail-fab-wrap" class="relative inline-block group">
    <span id="virtual-nail-fab-tip" class="pointer-events-none absolute right-full top-1/2 -translate-y-1/2 mr-3 hidden sm:flex items-center opacity-0 translate-x-2 transition-all duration-300 group-hover:opacity-100 group-hover:translate-x-0 z-10">
        <span class="rounded-full bg-slate-900 text-white text-xs font-semibold px-3 py-2 shadow-lg whitespace-nowrap">Try nails with AI</span>
        <span class="w-0 h-0 border-y-[6px] border-y-transparent border-l-[8px] border-l-slate-900"></span>
    </span>

    <a
        href="{{ route('virtual-nail.index') }}"
        id="virtual-nail-fab-btn"
        class="virtual-nail-fab-target relative w-12 h-12 sm:w-14 sm:h-14 rounded-full shadow-lg flex items-center justify-center text-white hover:opacity-95 active:scale-95 transition-all flex-shrink-0 focus:outline-none focus-visible:ring-4 focus-visible:ring-pink-400/50"
        style="background: linear-gradient(135deg, #e91e8c 0%, #0195fe 100%);"
        aria-label="Virtual nail try-on"
        title="Virtual nail try-on"
    >
        <span class="material-symbols-outlined text-2xl sm:text-3xl" aria-hidden="true">back_hand</span>
        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] sm:min-w-[20px] sm:h-5 px-1 flex items-center justify-center rounded-full bg-white text-[9px] sm:text-[10px] font-extrabold uppercase tracking-tight text-pink-600 shadow-sm">AI</span>
    </a>
</div>
<script>
(function () {
    var wrap = document.getElementById('virtual-nail-fab-wrap');
    if (!wrap) return;

    if (localStorage.getItem('virtualNailFabSeen') !== '1') {
        wrap.classList.add('virtual-nail-fab-attention');
        setTimeout(function () {
            wrap.classList.add('virtual-nail-fab-ring');
            setTimeout(function () { wrap.classList.remove('virtual-nail-fab-ring'); }, 3200);
        }, 1200);

        var tip = document.getElementById('virtual-nail-fab-tip');
        if (tip) {
            setTimeout(function () {
                tip.classList.remove('opacity-0', 'translate-x-2');
                tip.classList.add('opacity-100', 'translate-x-0');
                setTimeout(function () {
                    tip.classList.add('opacity-0', 'translate-x-2');
                    tip.classList.remove('opacity-100', 'translate-x-0');
                }, 4500);
            }, 800);
        }

        setTimeout(function () {
            wrap.classList.remove('virtual-nail-fab-attention');
            localStorage.setItem('virtualNailFabSeen', '1');
        }, 12000);
    }

    var toolbar = document.getElementById('inline-edit-toolbar');
    var actions = document.getElementById('site-floating-actions');
    if (toolbar && actions) {
        actions.classList.remove('bottom-4', 'sm:bottom-6');
        actions.classList.add('bottom-20', 'sm:bottom-24');
    }
})();
</script>
@endif
