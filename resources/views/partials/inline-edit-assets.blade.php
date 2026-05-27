{{-- Modal + config + script — chỉ khi admin bật ?edit=1. @push('inline_edit_config') để thêm schema/data từ view con. --}}
@if(!empty($canEdit) && !empty($editMode))
@include('partials.inline-edit-modal')
<script>
window.INLINE_EDIT_CONFIG = {
    apiBase: @json(url('/admin/api/content-blocks')),
    csrfToken: @json(csrf_token()),
    uploadImageUrl: @json(route('admin.api.content-blocks.upload-image')),
    uploadVideoUrl: @json(route('admin.api.content-blocks.upload-video')),
};
window.CONTENT_BLOCK_SCHEMAS = window.CONTENT_BLOCK_SCHEMAS || {};
window.CONTENT_BLOCK_DATA = window.CONTENT_BLOCK_DATA || {};
</script>
@stack('inline_edit_config')
@php
    $__inlineEditJsPath = public_path('js/inline-content-blocks.js');
    $__inlineEditJsV = is_file($__inlineEditJsPath) ? (string) filemtime($__inlineEditJsPath) : '1';
@endphp
<script src="{{ asset('js/inline-content-blocks.js') }}?v={{ $__inlineEditJsV }}" defer></script>
@endif
