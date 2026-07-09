document.addEventListener('DOMContentLoaded', function() {
    if (!window.INLINE_EDIT_CONFIG) return;

    function readSectionBgColor(section) {
        if (!section) return '';
        return (section.dataset.contentBgColor || '').trim();
    }

    function applySectionBgColor(section, color) {
        if (!section) return;
        color = (color || '').trim();
        if (color) {
            section.style.backgroundColor = color;
            section.dataset.contentBgColor = color;
        } else {
            section.style.removeProperty('background-color');
            delete section.dataset.contentBgColor;
        }
    }

    var modal = document.getElementById('inline-edit-modal');
        var form = document.getElementById('inline-edit-form');
        var fieldsContainer = document.getElementById('inline-edit-fields');
        var currentBlock = null;
        var currentSchema = null;
        var currentTabInitialData = null;

        function openModal(blockKey, schemaArray) {
            currentBlock = blockKey;
            currentSchema = Array.isArray(schemaArray) ? schemaArray : [];
            currentTabInitialData = null;
            var section = document.querySelector('[data-content-block="' + blockKey + '"]');
            if (!section) return;
            var values = {};
            var blockData = window.CONTENT_BLOCK_DATA && window.CONTENT_BLOCK_DATA[blockKey];
            currentSchema.forEach(function(f) {
                if (f.type === 'tabs' || f.type === 'community_cards') {
                    currentTabInitialData = (blockData && blockData.tabs) ? blockData.tabs : [];
                    values[f.key] = [];
                    return;
                }
                if (f.type === 'images') {
                    values[f.key] = (blockData && Array.isArray(blockData[f.key]))
                        ? blockData[f.key].slice()
                        : ((f.key === 'images' && blockData && Array.isArray(blockData.images)) ? blockData.images.slice() : []);
                    return;
                }
                if (f.key === 'bg_color') {
                    values[f.key] = readSectionBgColor(section);
                    return;
                }
                if (f.key.indexOf('_features') !== -1) {
                    var featuresList = section.querySelector('[data-features-list="' + f.key + '"]');
                    if (featuresList) {
                        values[f.key] = Array.from(featuresList.querySelectorAll('li'))
                            .map(function(li) { return (li.textContent || '').trim(); })
                            .filter(Boolean)
                            .join('\n');
                    } else {
                        values[f.key] = '';
                    }
                    return;
                }
                var el = section.querySelector('[data-content-field="' + f.key + '"]');
                if (el) {
                    if (el.tagName === 'IMG') values[f.key] = el.src || '';
                    else if (el.dataset.contentStyle === 'width') {
                        values[f.key] = (el.style.width || '').replace('%', '').trim();
                    } else if (el.href !== undefined) values[f.key] = f.key.indexOf('url') !== -1 ? (el.getAttribute('href') || '') : (el.textContent || '').trim();
                    else values[f.key] = (el.textContent || '').trim();
                } else values[f.key] = '';
            });
            fieldsContainer.innerHTML = '';
            currentSchema.forEach(function(f) {
                if (f.type === 'tabs' || f.type === 'community_cards') {
                    var isCommunity = f.type === 'community_cards';
                    var tabKeys = f.tabKeys || (isCommunity ? ['card1', 'card2', 'card3', 'card4', 'card5'] : ['nail', 'box', 'arm', 'back']);
                    var tabs = Array.isArray(currentTabInitialData) && currentTabInitialData.length ? currentTabInitialData : tabKeys.map(function(k, i) {
                        return { key: k, label: isCommunity ? 'user' : k.charAt(0).toUpperCase() + k.slice(1), avatar_url: null, image_url: null, video_url: null };
                    });
                    var sectionLabel = document.createElement('div');
                    sectionLabel.className = 'block text-sm font-medium text-slate-700 mt-4 first:mt-0 mb-2';
                    sectionLabel.textContent = f.label;
                    fieldsContainer.appendChild(sectionLabel);
                    tabKeys.forEach(function(tabKey, i) {
                        var tab = tabs[i] || { key: tabKey, label: tabKey, avatar_url: null, image_url: null, video_url: null };
                        var card = document.createElement('div');
                        card.className = 'border border-slate-200 rounded-lg p-3 mb-3 bg-slate-50';
                        var rowLabel = document.createElement('label');
                        rowLabel.className = 'block text-xs font-medium text-slate-500 mb-2';
                        rowLabel.textContent = (isCommunity ? 'Thẻ ' : 'Tab ') + (i + 1) + ' — ' + tabKey;
                        card.appendChild(rowLabel);
                        var labelInput = document.createElement('input');
                        labelInput.type = 'text';
                        labelInput.name = 'tabs_' + i + '_label';
                        labelInput.placeholder = isCommunity ? 'Username (vd: lynhtran)' : 'Chữ trên nút';
                        labelInput.value = (tab.label || '').trim();
                        labelInput.className = 'w-full px-3 py-2 border border-slate-300 rounded-lg bg-white mb-2';
                        card.appendChild(labelInput);
                        var hiddenAvatar = null;
                        var avatarImg = null;
                        if (isCommunity) {
                            hiddenAvatar = document.createElement('input');
                            hiddenAvatar.type = 'hidden';
                            hiddenAvatar.name = 'tabs_' + i + '_avatar';
                            hiddenAvatar.value = tab.avatar_url || '';
                            card.appendChild(hiddenAvatar);
                            var avatarWrap = document.createElement('div');
                            avatarWrap.className = 'mb-2 flex items-center gap-2 flex-wrap';
                            avatarImg = document.createElement('img');
                            avatarImg.className = 'w-10 h-10 rounded-full object-cover border border-slate-200';
                            if (tab.avatar_url) { avatarImg.src = tab.avatar_url; avatarImg.style.display = 'block'; } else { avatarImg.style.display = 'none'; }
                            var avatarFileInput = document.createElement('input');
                            avatarFileInput.type = 'file';
                            avatarFileInput.accept = 'image/jpeg,image/jpg,image/png,image/webp';
                            avatarFileInput.className = 'hidden';
                            var avatarBtn = document.createElement('button');
                            avatarBtn.type = 'button';
                            avatarBtn.className = 'px-2 py-1.5 bg-slate-200 hover:bg-slate-300 rounded text-sm font-medium text-slate-700';
                            avatarBtn.textContent = 'Avatar (S3)';
                            avatarWrap.appendChild(avatarImg);
                            avatarWrap.appendChild(avatarFileInput);
                            avatarWrap.appendChild(avatarBtn);
                            card.appendChild(avatarWrap);
                            avatarBtn.addEventListener('click', function() { avatarFileInput.click(); });
                            avatarFileInput.addEventListener('change', function() {
                                var file = this.files && this.files[0];
                                if (!file) return;
                                avatarBtn.disabled = true;
                                avatarBtn.textContent = 'Đang tải...';
                                var formData = new FormData();
                                formData.append('image', file);
                                formData.append('_token', window.INLINE_EDIT_CONFIG.csrfToken);
                                fetch(window.INLINE_EDIT_CONFIG.uploadImageUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
                                    .then(function(r) { return r.json(); })
                                    .then(function(data) {
                                        if (!data.success || !data.url) throw new Error(data.message || 'Upload thất bại');
                                        hiddenAvatar.value = data.url;
                                        avatarImg.src = data.url;
                                        avatarImg.style.display = 'block';
                                    })
                                    .catch(function(err) { alert('Upload thất bại: ' + (err.message || 'Vui lòng thử lại')); })
                                    .finally(function() { avatarBtn.disabled = false; avatarBtn.textContent = 'Avatar (S3)'; avatarFileInput.value = ''; });
                            });
                        }
                        var hiddenImg = document.createElement('input');
                        hiddenImg.type = 'hidden';
                        hiddenImg.name = 'tabs_' + i + '_image';
                        hiddenImg.value = tab.image_url || '';
                        card.appendChild(hiddenImg);
                        var hiddenVid = null;
                        if (!isCommunity) {
                            hiddenVid = document.createElement('input');
                            hiddenVid.type = 'hidden';
                            hiddenVid.name = 'tabs_' + i + '_video';
                            hiddenVid.value = tab.video_url || '';
                            card.appendChild(hiddenVid);
                        }
                        var preview = document.createElement('div');
                        preview.className = 'mb-2';
                        var img = document.createElement('img');
                        img.className = 'max-h-20 rounded border border-slate-200 object-cover';
                        img.style.maxWidth = '160px';
                        img.alt = 'Preview';
                        if (tab.image_url) { img.src = tab.image_url; img.style.display = 'block'; } else { img.style.display = 'none'; }
                        var fileInput = document.createElement('input');
                        fileInput.type = 'file';
                        fileInput.accept = isCommunity ? 'image/gif' : 'image/jpeg,image/jpg,image/png,image/webp';
                        fileInput.className = 'hidden';
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'px-2 py-1.5 bg-slate-200 hover:bg-slate-300 rounded text-sm font-medium text-slate-700 mr-2';
                        btn.textContent = isCommunity ? 'GIF (S3)' : 'Chọn ảnh (S3)';
                        preview.appendChild(img);
                        card.appendChild(preview);
                        card.appendChild(fileInput);
                        card.appendChild(btn);
                        btn.addEventListener('click', function() { fileInput.click(); });
                        fileInput.addEventListener('change', function() {
                            var file = this.files && this.files[0];
                            if (!file) return;
                            var allowed = isCommunity
                                ? ['image/gif']
                                : ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                            if (allowed.indexOf(file.type) === -1) { alert(isCommunity ? 'Chỉ chấp nhận file GIF' : 'Chỉ chấp nhận ảnh: JPG, PNG, WebP'); return; }
                            if (file.size > 10 * 1024 * 1024) { alert('Ảnh tối đa 10MB'); return; }
                            btn.disabled = true;
                            btn.textContent = 'Đang tải...';
                            var formData = new FormData();
                            formData.append('image', file);
                            formData.append('_token', window.INLINE_EDIT_CONFIG.csrfToken);
                            fetch(window.INLINE_EDIT_CONFIG.uploadImageUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
                                .then(function(r) { return r.json(); })
                                .then(function(data) {
                                    if (!data.success || !data.url) throw new Error(data.message || 'Upload thất bại');
                                    hiddenImg.value = data.url;
                                    img.src = data.url;
                                    img.style.display = 'block';
                                })
                                .catch(function(err) { alert('Upload thất bại: ' + (err.message || 'Vui lòng thử lại')); })
                                .finally(function() { btn.disabled = false; btn.textContent = isCommunity ? 'GIF (S3)' : 'Chọn ảnh (S3)'; fileInput.value = ''; });
                        });
                        if (!isCommunity) {
                        var videoWrap = document.createElement('div');
                        videoWrap.className = 'mt-2 flex items-center gap-2 flex-wrap';
                        var videoLabel = document.createElement('span');
                        videoLabel.className = 'text-xs text-slate-500';
                        videoLabel.textContent = 'Video: ';
                        videoWrap.appendChild(videoLabel);
                        var videoLink = document.createElement('a');
                        videoLink.className = 'text-xs text-primary-fg truncate max-w-[180px]';
                        videoLink.target = '_blank';
                        videoLink.rel = 'noopener';
                        if (tab.video_url) { videoLink.href = tab.video_url; videoLink.textContent = 'Đã có video'; } else { videoLink.textContent = 'Chưa chọn'; videoLink.href = '#'; }
                        videoWrap.appendChild(videoLink);
                        var videoFileInput = document.createElement('input');
                        videoFileInput.type = 'file';
                        videoFileInput.accept = 'video/mp4,video/webm,video/ogg,video/quicktime';
                        videoFileInput.className = 'hidden';
                        var videoBtn = document.createElement('button');
                        videoBtn.type = 'button';
                        videoBtn.className = 'px-2 py-1.5 bg-slate-200 hover:bg-slate-300 rounded text-sm font-medium text-slate-700';
                        videoBtn.textContent = 'Chọn video (S3)';
                        card.appendChild(videoWrap);
                        card.appendChild(videoFileInput);
                        card.appendChild(videoBtn);
                        videoBtn.addEventListener('click', function() { videoFileInput.click(); });
                        videoFileInput.addEventListener('change', function() {
                            var file = this.files && this.files[0];
                            if (!file) return;
                            var allowed = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
                            if (allowed.indexOf(file.type) === -1) { alert('Chỉ chấp nhận video: MP4, WebM, OGG, MOV'); return; }
                            if (file.size > 100 * 1024 * 1024) { alert('Video tối đa 100MB'); return; }
                            videoBtn.disabled = true;
                            videoBtn.textContent = 'Đang tải...';
                            var formData = new FormData();
                            formData.append('video', file);
                            formData.append('_token', window.INLINE_EDIT_CONFIG.csrfToken);
                            fetch(window.INLINE_EDIT_CONFIG.uploadVideoUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
                                .then(function(r) { return r.json(); })
                                .then(function(data) {
                                    if (!data.success || !data.url) throw new Error(data.message || 'Upload thất bại');
                                    hiddenVid.value = data.url;
                                    videoLink.href = data.url;
                                    videoLink.textContent = 'Đã upload video';
                                })
                                .catch(function(err) { alert('Upload video thất bại: ' + (err.message || 'Vui lòng thử lại')); })
                                .finally(function() { videoBtn.disabled = false; videoBtn.textContent = 'Chọn video (S3)'; videoFileInput.value = ''; });
                        });
                        }
                        fieldsContainer.appendChild(card);
                    });
                    return;
                }
                if (f.type === 'images') {
                    var imagesList = Array.isArray(values[f.key]) ? values[f.key].slice() : [];
                    var container = document.createElement('div');
                    container.className = 'mt-3 first:mt-0';
                    var sectionLabel = document.createElement('div');
                    sectionLabel.className = 'block text-sm font-medium text-slate-700 mb-2';
                    sectionLabel.textContent = f.label;
                    container.appendChild(sectionLabel);
                    var listEl = document.createElement('div');
                    listEl.className = 'space-y-2 mb-2';
                    listEl.dataset.imagesList = 'true';
                    container.appendChild(listEl);
                    function renderImagesList() {
                        listEl.innerHTML = '';
                        imagesList.forEach(function(url, idx) {
                            var row = document.createElement('div');
                            row.className = 'flex items-center gap-2 p-2 border border-slate-200 rounded-lg bg-slate-50';
                            var hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = f.key + '_' + idx;
                            hidden.value = url;
                            var img = document.createElement('img');
                            img.className = 'w-14 h-14 object-cover rounded border border-slate-200 flex-shrink-0';
                            img.src = url;
                            img.alt = 'Preview';
                            var removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'ml-auto px-2 py-1 text-red-600 hover:bg-primary rounded text-sm font-medium';
                            removeBtn.textContent = 'Xóa';
                            removeBtn.addEventListener('click', function() {
                                imagesList.splice(idx, 1);
                                renderImagesList();
                            });
                            row.appendChild(hidden);
                            row.appendChild(img);
                            row.appendChild(removeBtn);
                            listEl.appendChild(row);
                        });
                    }
                    renderImagesList();
                    var addBtn = document.createElement('button');
                    addBtn.type = 'button';
                    addBtn.className = 'px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg text-sm font-medium text-slate-700';
                    addBtn.textContent = 'Thêm ảnh (upload lên AWS)';
                    var fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.accept = 'image/jpeg,image/jpg,image/png,image/webp';
                    fileInput.className = 'hidden';
                    addBtn.addEventListener('click', function() { fileInput.click(); });
                    fileInput.addEventListener('change', function() {
                        var file = this.files && this.files[0];
                        if (!file) return;
                        var allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                        if (allowed.indexOf(file.type) === -1) { alert('Chỉ chấp nhận ảnh: JPG, PNG, WebP'); return; }
                        if (file.size > 10 * 1024 * 1024) { alert('Ảnh tối đa 10MB'); return; }
                        addBtn.disabled = true;
                        addBtn.textContent = 'Đang tải...';
                        var formData = new FormData();
                        formData.append('image', file);
                        formData.append('_token', window.INLINE_EDIT_CONFIG.csrfToken);
                        fetch(window.INLINE_EDIT_CONFIG.uploadImageUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (!data.success || !data.url) throw new Error(data.message || 'Upload thất bại');
                                imagesList.push(data.url);
                                renderImagesList();
                            })
                            .catch(function(err) { alert('Upload thất bại: ' + (err.message || 'Vui lòng thử lại')); })
                            .finally(function() { addBtn.disabled = false; addBtn.textContent = 'Thêm ảnh (upload lên AWS)'; fileInput.value = ''; });
                    });
                    container.appendChild(addBtn);
                    fieldsContainer.appendChild(container);
                    return;
                }
                var label = document.createElement('label');
                label.className = 'block text-sm font-medium text-slate-700 mt-3 first:mt-0';
                label.textContent = f.label;
                var wrap = document.createElement('div');
                wrap.className = 'mt-1';
                if (f.type === 'image') {
                    var hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = f.key;
                    hiddenInput.value = values[f.key] || '';
                    hiddenInput.dataset.fieldKey = f.key;
                    var preview = document.createElement('div');
                    preview.className = 'mb-2';
                    var img = document.createElement('img');
                    img.className = 'max-h-28 rounded-lg border border-slate-200 object-cover';
                    img.style.maxWidth = '200px';
                    img.alt = 'Preview';
                    if (values[f.key]) { img.src = values[f.key]; img.style.display = 'block'; } else { img.style.display = 'none'; }
                    var fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.accept = 'image/jpeg,image/jpg,image/png,image/webp';
                    fileInput.className = 'hidden';
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'px-3 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg text-sm font-medium text-slate-700';
                    btn.textContent = 'Chọn ảnh (upload lên AWS)';
                    preview.appendChild(img);
                    wrap.appendChild(preview);
                    wrap.appendChild(hiddenInput);
                    wrap.appendChild(fileInput);
                    wrap.appendChild(btn);
                    btn.addEventListener('click', function() { fileInput.click(); });
                    fileInput.addEventListener('change', function() {
                        var file = this.files && this.files[0];
                        if (!file) return;
                        var allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                        if (allowed.indexOf(file.type) === -1) {
                            alert('Chỉ chấp nhận ảnh: JPG, PNG, WebP');
                            return;
                        }
                        if (file.size > 10 * 1024 * 1024) {
                            alert('Ảnh tối đa 10MB');
                            return;
                        }
                        btn.disabled = true;
                        btn.textContent = 'Đang tải lên...';
                        var formData = new FormData();
                        formData.append('image', file);
                        formData.append('_token', window.INLINE_EDIT_CONFIG.csrfToken);
                        fetch(window.INLINE_EDIT_CONFIG.uploadImageUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (!data.success || !data.url) throw new Error(data.message || 'Upload thất bại');
                            hiddenInput.value = data.url;
                            img.src = data.url;
                            img.style.display = 'block';
                        })
                        .catch(function(err) {
                            alert('Upload thất bại: ' + (err.message || 'Vui lòng thử lại'));
                        })
                        .finally(function() {
                            btn.disabled = false;
                            btn.textContent = 'Chọn ảnh (upload lên AWS)';
                            fileInput.value = '';
                        });
                    });
                } else if (f.type === 'textarea') {
                    var input = document.createElement('textarea');
                    input.rows = 3;
                    input.className = 'w-full px-3 py-2 border border-slate-300 rounded-lg bg-white';
                    input.name = f.key;
                    input.value = values[f.key] || '';
                    input.dataset.fieldKey = f.key;
                    wrap.appendChild(input);
                } else {
                    var input = document.createElement('input');
                    input.type = f.type === 'url' ? 'url' : 'text';
                    input.className = 'w-full px-3 py-2 border border-slate-300 rounded-lg bg-white';
                    input.name = f.key;
                    input.value = values[f.key] || '';
                    input.dataset.fieldKey = f.key;
                    wrap.appendChild(input);
                }
                label.appendChild(wrap);
                fieldsContainer.appendChild(label);
            });
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }
        window.openInlineEditModal = function(blockKey) {
            var schema = window.CONTENT_BLOCK_SCHEMAS && window.CONTENT_BLOCK_SCHEMAS[blockKey];
            if (blockKey && schema && Array.isArray(schema)) openModal(blockKey, schema);
        };

        function closeModal() {
            currentBlock = null;
            currentSchema = null;
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        document.querySelectorAll('.inline-edit-trigger').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var block = this.getAttribute('data-block');
                var schema = window.CONTENT_BLOCK_SCHEMAS && window.CONTENT_BLOCK_SCHEMAS[block];
                if (block && schema && Array.isArray(schema)) openModal(block, schema);
            });
        });
        document.getElementById('inline-edit-modal-close') && document.getElementById('inline-edit-modal-close').addEventListener('click', closeModal);
        document.getElementById('inline-edit-cancel') && document.getElementById('inline-edit-cancel').addEventListener('click', closeModal);
        // Không đóng khi click nền tối — chỉ Hủy / × / Lưu thành công

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!currentBlock || !window.INLINE_EDIT_CONFIG) return;
                var content = {};
                var tabKeys = null;
                var isCommunityCards = false;
                currentSchema.forEach(function(f) {
                    if (f.type === 'tabs' || f.type === 'community_cards') {
                        isCommunityCards = f.type === 'community_cards';
                        tabKeys = f.tabKeys || (isCommunityCards ? ['card1', 'card2', 'card3', 'card4', 'card5'] : ['nail', 'box', 'arm', 'back']);
                        return;
                    }
                    if (f.type === 'images') {
                        var inputs = form.querySelectorAll('input[name^="' + f.key + '_"]');
                        var sorted = Array.from(inputs).sort(function(a, b) {
                            var na = parseInt(a.name.replace(f.key + '_', ''), 10);
                            var nb = parseInt(b.name.replace(f.key + '_', ''), 10);
                            return na - nb;
                        });
                        content[f.key] = sorted.map(function(inp) { return inp.value.trim(); }).filter(Boolean);
                        return;
                    }
                    var input = form.querySelector('[name="' + f.key + '"]');
                    if (input) content[f.key] = input.value.trim();
                });
                if (tabKeys && tabKeys.length) {
                    content.tabs = tabKeys.map(function(k, i) {
                        var labelInput = form.querySelector('[name="tabs_' + i + '_label"]');
                        var imageInput = form.querySelector('[name="tabs_' + i + '_image"]');
                        var videoInput = form.querySelector('[name="tabs_' + i + '_video"]');
                        var avatarInput = form.querySelector('[name="tabs_' + i + '_avatar"]');
                        var prev = currentTabInitialData && currentTabInitialData[i] ? currentTabInitialData[i] : {};
                        var imgUrl = (imageInput && imageInput.value) ? imageInput.value.trim() : (prev.image_url || null);
                        var tabData = {
                            key: k,
                            label: (labelInput && labelInput.value) ? labelInput.value.trim() : (prev.label || k),
                            image_url: imgUrl || null,
                        };
                        if (isCommunityCards) {
                            tabData.avatar_url = (avatarInput && avatarInput.value) ? avatarInput.value.trim() : (prev.avatar_url || null);
                        } else {
                            tabData.video_url = (videoInput && videoInput.value) ? videoInput.value.trim() : (prev.video_url || null);
                        }
                        return tabData;
                    });
                }
                fetch(window.INLINE_EDIT_CONFIG.apiBase, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.INLINE_EDIT_CONFIG.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ key: currentBlock, content: content }),
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (currentBlock === 'home.hero' || currentBlock === 'home.see_it_in_action' || currentBlock === 'home.indulge' || currentBlock === 'home.customer_favorites') {
                        closeModal();
                        location.reload();
                        return;
                    }
                    var section = document.querySelector('[data-content-block="' + currentBlock + '"]');
                    if (section && data.content) {
                        if (Object.prototype.hasOwnProperty.call(data.content, 'bg_color')) {
                            applySectionBgColor(section, data.content.bg_color);
                        }
                        Object.keys(data.content).forEach(function(key) {
                            if (key === 'tabs' || key === 'bg_color') return;
                            if (key.indexOf('_features') !== -1) {
                                var listEl = section.querySelector('[data-features-list="' + key + '"]');
                                if (listEl && data.content[key]) {
                                    listEl.innerHTML = '';
                                    String(data.content[key]).split(/\r?\n/).forEach(function(line) {
                                        line = line.trim();
                                        if (!line) return;
                                        var li = document.createElement('li');
                                        li.textContent = line;
                                        listEl.appendChild(li);
                                    });
                                }
                                return;
                            }
                            var el = section.querySelector('[data-content-field="' + key + '"]');
                            if (el) {
                                if (el.tagName === 'IMG') el.src = data.content[key] || el.src;
                                else if (el.dataset.contentStyle === 'width') {
                                    var barNum = parseInt(String(data.content[key] || '').replace(/\D/g, ''), 10);
                                    if (!isNaN(barNum)) {
                                        barNum = Math.min(100, Math.max(0, barNum));
                                        el.style.width = barNum + '%';
                                    }
                                } else if (el.href !== undefined) {
                                    if (key.indexOf('url') !== -1) el.setAttribute('href', data.content[key] || '#');
                                    else el.textContent = data.content[key] || '';
                                } else el.textContent = data.content[key] || '';
                            }
                        });
                    }
                    closeModal();
                })
                .catch(function() { alert('Không thể lưu. Vui lòng thử lại.'); });
            });
        }
});
