@extends('layouts.admin')

@section('title', 'Sample Hands — Virtual Nail')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Sample Hands</h1>
            <p class="mt-1 text-sm text-gray-600">
                Pre-made hand photos customers can use to preview nail designs without uploading their own image.
            </p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <a href="{{ route('admin.settings.virtual-nail.edit') }}"
               class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Virtual Nail Settings
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ready</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $stats['ready'] }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Left hands</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $samples->where('handSide', 'left')->count() }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Right hands</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $samples->where('handSide', 'right')->count() }}</p>
        </div>
    </div>

    {{-- Success / Error messages --}}
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    {{-- Upload form --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Upload new sample hand</h2>

        <form method="POST" action="{{ route('admin.sample-hands.store') }}"
              enctype="multipart/form-data"
              class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                {{-- File upload --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Photo <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="file" name="file" id="file-input"
                               accept="image/jpeg,image/png,image/webp"
                               class="hidden"
                               onchange="handleFileSelect(this)">
                        <label for="file-input"
                               class="flex flex-col items-center justify-center w-full h-40 rounded-2xl border-2 border-dashed border-gray-300 cursor-pointer hover:border-blue-400 hover:bg-blue-50/30 transition">
                            <div id="upload-placeholder" class="text-center">
                                <svg class="mx-auto w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">Click to select or drag image here</p>
                                <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP · max 10 MB · ≥ 256×256px</p>
                            </div>
                            <div id="upload-preview" class="hidden text-center w-full px-4">
                                <img id="preview-img" class="mx-auto max-h-32 rounded-xl object-contain" alt="Preview">
                                <p id="preview-filename" class="mt-2 text-xs text-gray-500 truncate"></p>
                            </div>
                        </label>
                    </div>
                    @error('file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Meta fields --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Label <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="label" value="{{ old('label') }}"
                               placeholder="e.g. Light — right hand"
                               class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm">
                        @error('label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Hand side <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-3 mt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="hand_side" value="left"
                                       class="text-blue-600 border-gray-300 focus:ring-blue-500"
                                       {{ old('hand_side') === 'left' ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">Left</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="hand_side" value="right"
                                       class="text-blue-600 border-gray-300 focus:ring-blue-500"
                                       {{ old('hand_side', 'right') !== 'left' ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">Right</span>
                            </label>
                        </div>
                        @error('hand_side')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-sm hover:bg-blue-700 transition shadow-sm">
                        Upload sample hand
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Sample hands list --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">All sample hands ({{ $samples->count() }})</h2>
        </div>

        @if($samples->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="mt-3 text-gray-500 text-sm">No sample hands yet. Upload one above.</p>
                <p class="mt-1 text-gray-400 text-xs">Recommended: ≥ 256×256px, plain background, palm-facing camera.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">Preview</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Label</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Side</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">File</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Size</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Added</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($samples as $sample)
                            <tr class="hover:bg-gray-50/60 transition">
                                {{-- Preview thumbnail --}}
                                <td class="px-5 py-3">
                                    @if($sample['previewUrl'])
                                        <img src="{{ $sample['previewUrl'] }}"
                                             alt="{{ $sample['label'] }}"
                                             class="w-12 h-12 rounded-xl object-cover border border-gray-200">
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </td>

                                {{-- Label --}}
                                <td class="px-5 py-3">
                                    <span class="font-semibold text-gray-900 text-sm">{{ $sample['label'] }}</span>
                                </td>

                                {{-- Side --}}
                                <td class="px-5 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ $sample['handSide'] === 'left'
                                            ? 'bg-violet-50 text-violet-700'
                                            : 'bg-blue-50 text-blue-700' }}">
                                        {{ ucfirst($sample['handSide']) }}
                                    </span>
                                </td>

                                {{-- Filename --}}
                                <td class="px-5 py-3">
                                    <code class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ $sample['filename'] ?: '—' }}</code>
                                </td>

                                {{-- Size --}}
                                <td class="px-5 py-3 text-sm text-gray-600">
                                    {{ $sample['byteSize'] > 0 ? number_format($sample['byteSize'] / 1024, 1).' KB' : '—' }}
                                </td>

                                {{-- Created --}}
                                <td class="px-5 py-3 text-sm text-gray-500">
                                    {{ $sample['createdAt'] }}
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        {{-- Edit button --}}
                                        <button type="button"
                                                onclick="openEditModal('{{ $sample['id'] }}', '{{ e($sample['label']) }}', '{{ $sample['handSide'] }}')"
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Edit
                                        </button>

                                        {{-- Delete button --}}
                                        <form method="POST"
                                              action="{{ route('admin.sample-hands.destroy', $sample['id']) }}"
                                              onsubmit="return confirm('Delete &quot;{{ e($sample['label']) }}&quot;? This removes the image from disk and the database.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-500 hover:text-red-700 text-sm font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Edit modal --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Edit sample hand</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="edit-form" method="POST" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Label</label>
                    <input type="text" name="label" id="edit-label"
                           class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200/50 text-sm">
                    @error('label')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Hand side</label>
                    <div class="flex gap-5 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="hand_side" id="edit-side-left" value="left"
                                   class="text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">Left</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="hand_side" id="edit-side-right" value="right"
                                   class="text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">Right</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 transition shadow-sm">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ── File upload preview ──────────────────────────────────────────────────
    function handleFileSelect(input) {
        const file = input.files && input.files[0];
        const placeholder = document.getElementById('upload-placeholder');
        const previewEl = document.getElementById('upload-preview');
        const previewImg = document.getElementById('preview-img');
        const previewFilename = document.getElementById('preview-filename');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewFilename.textContent = file.name + ' (' + formatBytes(file.size) + ')';
                placeholder.classList.add('hidden');
                previewEl.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            placeholder.classList.remove('hidden');
            previewEl.classList.add('hidden');
        }
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    // ── Edit modal ───────────────────────────────────────────────────────────
    function openEditModal(id, label, handSide) {
        const modal = document.getElementById('edit-modal');
        const form = document.getElementById('edit-form');
        const labelInput = document.getElementById('edit-label');

        form.action = '/admin/sample-hands/' + id;
        labelInput.value = label;

        document.getElementById('edit-side-left').checked = (handSide === 'left');
        document.getElementById('edit-side-right').checked = (handSide === 'right');

        modal.classList.remove('hidden');
        labelInput.focus();
    }

    function closeEditModal() {
        document.getElementById('edit-modal').classList.add('hidden');
    }

    // Close modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeEditModal();
    });
</script>
@endsection
