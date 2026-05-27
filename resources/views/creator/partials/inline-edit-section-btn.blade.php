@if(!empty($canEdit) && !empty($editMode))
<div class="mx-auto mb-2 flex max-w-7xl justify-end px-5 md:px-16">
    <button type="button"
            class="inline-edit-trigger rounded-lg bg-primary px-3 py-2 text-sm font-bold text-white shadow-lg hover:opacity-90"
            data-block="{{ $block }}">Chỉnh {{ $label }}</button>
</div>
@endif
