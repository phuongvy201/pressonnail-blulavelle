@if(!empty($canEdit))
<div id="inline-edit-toolbar" class="fixed bottom-6 right-6 z-50 flex max-w-[min(100vw-2rem,20rem)] flex-col gap-2">
    @if(!empty($editMode))
    <p class="rounded-xl bg-white/95 px-3 py-2 text-xs font-medium leading-snug text-[#404753] shadow-lg ring-1 ring-[#bfc7d5]">
        Bấm các nút <strong class="text-primary">Chỉnh …</strong> trên từng section (Hero, Lợi ích, Các bước, Hạng, Sample, Spotlight, Dashboard, FAQ, CTA) hoặc <strong class="text-primary">Chỉnh footer</strong> cuối trang.
    </p>
    <a href="{{ url()->current() }}" class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-slate-800 text-white rounded-xl font-bold shadow-xl hover:bg-slate-700">Thoát chỉnh sửa</a>
    @else
    <a href="{{ url()->current() }}?edit=1" class="inline-flex items-center gap-2 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-xl hover:opacity-90">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
        Chỉnh sửa trang
    </a>
    @endif
</div>
@endif
