@extends('layouts.admin')

@section('title', 'Create New Post')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Create New Blog Post</h1>
        <p class="text-gray-600">Share your story with the community</p>
    </div>

    <!-- Display success message -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Display error message -->
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Display validation errors -->
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <h4 class="font-medium mb-2">Có lỗi xảy ra:</h4>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data" class="max-w-5xl">
        @csrf
        
        <div class="grid grid-cols-3 gap-6">
            <!-- Main Content (2/3) -->
            <div class="col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow p-6 space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Post Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366]">
                        @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Content -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
                        <textarea name="content" id="content" rows="20"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#005366]">{{ old('content') }}</textarea>
                        @error('content')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Excerpt -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Excerpt (Summary)</label>
                            <textarea name="excerpt" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ old('excerpt') }}</textarea>
                            @error('excerpt')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            <p class="text-xs text-gray-500 mt-1">Short description shown in listings</p>
                        </div>
                </div>

                <!-- SEO Section -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">SEO Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                            <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @error('meta_title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                            <textarea name="meta_description" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ old('meta_description') }}</textarea>
                            @error('meta_description')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Keywords</label>
                            <input type="text" name="meta_keywords" value="{{ old('meta_keywords') }}" placeholder="keyword1, keyword2"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            @error('meta_keywords')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar (1/3) -->
            <div class="col-span-1 space-y-6">
                <!-- Publish Settings -->
                <div class="bg-white rounded-lg shadow p-6 space-y-4">
                    <h3 class="font-semibold text-gray-900">Publish</h3>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="draft" {{ old('status', 'published') == 'draft' ? 'selected' : '' }}>Save as Draft</option>
                            <option value="published" {{ old('status', 'published') == 'published' ? 'selected' : '' }}>Publish Now</option>
                            <option value="scheduled" {{ old('status', 'published') == 'scheduled' ? 'selected' : '' }}>Schedule</option>
                        </select>
                        @error('status')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        <p class="text-xs text-gray-500 mt-1">Published posts require admin approval</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Publish Date</label>
                        <input type="datetime-local" name="published_at" value="{{ old('published_at') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                        @error('published_at')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full px-6 py-2 bg-[#F0427C] text-white rounded-lg hover:bg-[#d6386a]">
                        Create Post
                    </button>
                </div>

                <!-- Category & Tags -->
                <div class="bg-white rounded-lg shadow p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="post_category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">No Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('post_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('post_category_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                        <select name="tags[]" multiple class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm" size="5">
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}" {{ (collect(old('tags'))->contains($tag->id)) ? 'selected' : '' }}>{{ $tag->name }}</option>
                            @endforeach
                        </select>
                        @error('tags')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple</p>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="bg-white rounded-lg shadow p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                    <input type="file" name="featured_image" accept="image/*"
                           class="w-full text-sm">
                    @error('featured_image')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Gallery -->
                <div class="bg-white rounded-lg shadow p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gallery Images</label>
                    <input type="file" name="gallery[]" accept="image/*" multiple
                           class="w-full text-sm">
                    @error('gallery')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    @error('gallery.*')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-500 mt-1">Select multiple images</p>
                </div>

                <!-- Post Type -->
                <div class="bg-white rounded-lg shadow p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Post Type</label>
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="article" {{ old('type', 'article') == 'article' ? 'selected' : '' }}>Article</option>
                        <option value="video" {{ old('type') == 'video' ? 'selected' : '' }}>Video</option>
                        <option value="gallery" {{ old('type') == 'gallery' ? 'selected' : '' }}>Gallery</option>
                        <option value="product_review" {{ old('type') == 'product_review' ? 'selected' : '' }}>Product Review</option>
                    </select>
                    @error('type')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Options -->
                <div class="bg-white rounded-lg shadow p-6 space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="featured" value="1" class="w-4 h-4 text-[#005366] rounded">
                        <span class="ml-2 text-sm text-gray-700">Mark as Featured</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="allow_comments" value="1" checked class="w-4 h-4 text-[#005366] rounded">
                        <span class="ml-2 text-sm text-gray-700">Allow Comments</span>
                    </label>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/pw52gj1ywkblbwxr7ywefhxjq28di8umadjb79gk9hlpqzzy/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
    tinymce.init({
        selector: '#content',
        height: 500,
        menubar: true,
        setup: function (editor) {
            // Ensure proper handling of the textarea
            editor.on('init', function () {
                var textarea = document.getElementById('content');
                if (textarea) {
                    // Keep textarea accessible but not visible
                    textarea.style.position = 'absolute';
                    textarea.style.left = '-10000px';
                    textarea.style.top = '-10000px';
                    textarea.style.width = '1px';
                    textarea.style.height = '1px';
                    textarea.style.opacity = '0';
                    textarea.style.pointerEvents = 'none';
                }
            });
            
            // Remove validation error when user types in TinyMCE
            editor.on('input change keyup NodeChange', function() {
                var existingError = document.querySelector('.content-validation-error');
                if (existingError) {
                    existingError.remove();
                }
            });
        },
        init_instance_callback: function (editor) {
            // Remove any display:none styles that TinyMCE might have applied
            var textarea = document.getElementById('content');
            if (textarea) {
                textarea.style.display = 'block';
                textarea.style.position = 'absolute';
                textarea.style.left = '-10000px';
                textarea.style.top = '-10000px';
                textarea.style.width = '1px';
                textarea.style.height = '1px';
                textarea.style.opacity = '0';
                textarea.style.pointerEvents = 'none';
            }
        },
        plugins: [
            // Core editing features
            'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
            // Premium features
            'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'advtemplate', 'ai', 'uploadcare', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown', 'importword', 'exportword', 'exportpdf'
        ],
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography uploadcare | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        branding: false,
        promotion: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
        tinycomments_mode: 'embedded',
        tinycomments_author: '{{ auth()->user()->name }}',
        mergetags_list: [
            { value: 'First.Name', title: 'First Name' },
            { value: 'Email', title: 'Email' },
        ],
        ai_request: (request, respondWith) => respondWith.string(() => Promise.reject('See docs to implement AI Assistant')),
        uploadcare_public_key: 'b02167f0f3e107779bde',
        images_upload_handler: function (blobInfo, success, failure) {
            var xhr, formData;
            xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open('POST', '{{ route("admin.posts.upload-image") }}');
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
            
            xhr.onload = function() {
                var json;
                if (xhr.status != 200) {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                }
                json = JSON.parse(xhr.responseText);
                if (!json || typeof json.location != 'string') {
                    failure('Invalid JSON: ' + xhr.responseText);
                    return;
                }
                success(json.location);
            };
            
            formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        }
    });

    // Handle form validation with TinyMCE
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Update TinyMCE content to textarea before submit
                tinymce.triggerSave();
                
                // Check if content is empty
                var contentTextarea = document.getElementById('content');
                var editor = tinymce.get('content');
                var isEmpty = false;
                
                // Check both textarea value and editor content
                if (!contentTextarea.value || contentTextarea.value.trim() === '' || contentTextarea.value.trim() === '<p></p>' || contentTextarea.value.trim() === '<p><br></p>') {
                    if (editor) {
                        var editorContent = editor.getContent();
                        if (!editorContent || editorContent.trim() === '' || editorContent.trim() === '<p></p>' || editorContent.trim() === '<p><br></p>') {
                            isEmpty = true;
                        }
                    } else {
                        isEmpty = true;
                    }
                }
                
                if (isEmpty) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Focus on TinyMCE editor
                    if (editor) {
                        editor.focus();
                    }
                    
                    // Show custom validation message
                    var contentDiv = contentTextarea.closest('div');
                    var existingError = contentDiv.querySelector('.content-validation-error');
                    if (!existingError) {
                        var errorElement = document.createElement('p');
                        errorElement.className = 'text-red-500 text-sm mt-1 content-validation-error';
                        errorElement.textContent = 'Nội dung là bắt buộc.';
                        contentDiv.appendChild(errorElement);
                    }
                    
                    // Scroll to TinyMCE editor
                    var editorContainer = editor ? editor.getContainer() : null;
                    if (editorContainer) {
                        editorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    
                    return false;
                } else {
                    // Remove any existing validation error
                    var existingError = document.querySelector('.content-validation-error');
                    if (existingError) {
                        existingError.remove();
                    }
                }
            });
        }
    });

</script>
@endpush

