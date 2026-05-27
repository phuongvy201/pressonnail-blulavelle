<div id="size-guide-modal" class="size-guide-modal-root fixed inset-0 z-[120] hidden flex items-center justify-center p-3 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="size-guide-modal-title">
    <div class="absolute inset-0 size-guide-modal-backdrop" data-size-guide-modal-close aria-hidden="true"></div>
    <div class="relative z-10 w-full max-w-2xl max-h-[min(90vh,90dvh)] flex flex-col rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden pointer-events-auto">
        <div class="flex items-start justify-between gap-3 px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-[#0297FE]/10 to-white shrink-0">
            <div class="min-w-0 pr-2">
                <h2 id="size-guide-modal-title" class="text-base font-extrabold text-slate-900">Size chart</h2>
                <p class="text-xs text-slate-600 mt-0.5 leading-snug">Measurements in millimeters (mm) per finger.</p>
            </div>
            <button type="button" class="shrink-0 w-10 h-10 rounded-full border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" data-size-guide-modal-close aria-label="Close size guide">
                <span class="material-symbols-outlined text-2xl">close</span>
            </button>
        </div>
        <div class="overflow-y-auto overscroll-contain px-4 py-4">
            <div class="overflow-x-auto rounded-xl border-2 border-[#1a4a7a]">
                <table class="min-w-full text-center text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-[#1a4a7a] text-white">
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3 font-bold uppercase tracking-wide whitespace-nowrap border-r border-white/20">Size</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3 font-bold uppercase tracking-wide whitespace-nowrap border-r border-white/20">Thumb</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3 font-bold uppercase tracking-wide whitespace-nowrap border-r border-white/20">Index</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3 font-bold uppercase tracking-wide whitespace-nowrap border-r border-white/20">Middle</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3 font-bold uppercase tracking-wide whitespace-nowrap border-r border-white/20">Ring</th>
                            <th class="px-3 py-2.5 sm:px-4 sm:py-3 font-bold uppercase tracking-wide whitespace-nowrap">Pinky</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sizeChartTable as $rowIndex => $row)
                            <tr class="{{ $rowIndex % 2 === 0 ? 'bg-white' : 'bg-[#eef4fb]' }}">
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 font-bold text-[#1a4a7a] whitespace-nowrap border-r border-[#1a4a7a]/15">{{ $row['preset'] }}</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-[#1a4a7a] whitespace-nowrap border-r border-[#1a4a7a]/15">{{ $row['thumb']['mm'] }} mm</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-[#1a4a7a] whitespace-nowrap border-r border-[#1a4a7a]/15">{{ $row['index']['mm'] }} mm</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-[#1a4a7a] whitespace-nowrap border-r border-[#1a4a7a]/15">{{ $row['middle']['mm'] }} mm</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-[#1a4a7a] whitespace-nowrap border-r border-[#1a4a7a]/15">{{ $row['ring']['mm'] }} mm</td>
                                <td class="px-3 py-2.5 sm:px-4 sm:py-3 text-[#1a4a7a] whitespace-nowrap">{{ $row['pinky']['mm'] }} mm</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-[11px] text-slate-500 italic text-center">If between sizes, choose the larger size.</p>
            <a href="{{ route('sizing-kit.index') }}#size-chart" class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-[#0297FE] hover:underline">
                How to measure &amp; sizing kit
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>
    </div>
</div>
