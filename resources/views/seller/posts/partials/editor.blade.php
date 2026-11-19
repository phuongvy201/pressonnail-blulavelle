@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('blog-post-form');
        var uploadUrl = @json(route('admin.posts.upload-image'));
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        var area = document.getElementById('content');
        if (!window.tinymce) {
            if (area) {
                area.style.display = 'block';
            }
            return;
        }

        tinymce.init({
            selector: '#content',
            base_url: 'https://cdn.jsdelivr.net/npm/tinymce@7.6.1',
            suffix: '.min',
            license_key: 'gpl',
            height: 520,
            menubar: 'edit view insert format table',
            plugins: 'lists link image table code wordcount autoresize media charmap searchreplace visualblocks anchor',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent | link image media table | removeformat code',
            branding: false,
            promotion: false,
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,
            automatic_uploads: true,
            images_upload_handler: function (blobInfo) {
                return new Promise(function (resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadUrl);
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
                    xhr.onload = function () {
                        if (xhr.status !== 200) {
                            reject('Image upload failed (' + xhr.status + ').');
                            return;
                        }
                        var json;
                        try {
                            json = JSON.parse(xhr.responseText);
                        } catch (error) {
                            reject('Image upload returned an unreadable response.');
                            return;
                        }
                        if (!json || typeof json.location !== 'string') {
                            reject('Image upload did not return a file address.');
                            return;
                        }
                        resolve(json.location);
                    };
                    xhr.onerror = function () {
                        reject('Image upload failed.');
                    };
                    var data = new FormData();
                    data.append('file', blobInfo.blob(), blobInfo.filename());
                    xhr.send(data);
                });
            }
        }).catch(function () {
            if (area) {
                area.style.display = 'block';
            }
        });

        window.setTimeout(function () {
            if (!document.querySelector('.tox-tinymce') && area) {
                area.style.display = 'block';
            }
        }, 2500);

        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (window.tinymce) {
                tinymce.triggerSave();
            }
            var editor = window.tinymce ? tinymce.get('content') : null;
            var html = editor ? editor.getContent({ format: 'html' }) : (document.getElementById('content')?.value || '');
            var text = editor ? editor.getContent({ format: 'text' }).trim() : html.replace(/<[^>]*>/g, '').trim();
            var existing = form.querySelector('.content-validation-error');
            if (existing) {
                existing.remove();
            }
            if (text === '') {
                event.preventDefault();
                if (editor) {
                    editor.focus();
                }
                var field = document.getElementById('content')?.closest('div');
                if (field) {
                    var message = document.createElement('p');
                    message.className = 'text-red-500 text-sm mt-1 content-validation-error';
                    message.textContent = 'Content is required.';
                    field.appendChild(message);
                }
            }
        });
    });
</script>
@endpush
