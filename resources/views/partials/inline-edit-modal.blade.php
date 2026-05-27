<div id="inline-edit-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 p-4" aria-modal="true" role="dialog" aria-labelledby="inline-edit-modal-title">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-hidden flex flex-col" data-inline-edit-panel>
        <div class="p-4 border-b border-slate-200 flex justify-between items-center">
            <h3 id="inline-edit-modal-title" class="text-lg font-bold text-slate-900">Chỉnh sửa nội dung</h3>
            <button type="button" id="inline-edit-modal-close" class="p-2 rounded-lg hover:bg-slate-100 text-slate-600">×</button>
        </div>
        <form id="inline-edit-form" class="p-4 overflow-y-auto flex-1">
            <div id="inline-edit-fields"></div>
        </form>
        <div class="p-4 border-t border-slate-200 flex justify-end gap-2">
            <button type="button" id="inline-edit-cancel" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 font-medium">Hủy</button>
            <button type="submit" form="inline-edit-form" class="px-4 py-2 bg-primary text-white rounded-lg font-bold hover:opacity-90">Lưu</button>
        </div>
    </div>
</div>
