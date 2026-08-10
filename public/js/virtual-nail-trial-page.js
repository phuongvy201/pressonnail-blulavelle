(function () {
    'use strict';

    var cfg = window.virtualNailPageConfig || {};
    if (!cfg.routes) return;

    var state = {
        handFile: null,
        handPreviewUrl: null,
        product: cfg.product || null,
        products: [],
        nailShape: cfg.defaultShape || 'Almond',
        nailLength: cfg.defaultLength || 'Medium',
        resultImage: null,
        showingBefore: false,
        searchTimer: null,
        processing: false,
        cameraStream: null,
        cameraFacing: 'environment',
    };

    var els = {};

    function $(id) { return document.getElementById(id); }

    function cacheElements() {
        els.preview = $('vnt-preview');
        els.previewPlaceholder = $('vnt-preview-placeholder');
        els.dzBadge = $('vnt-dz-badge');
        els.dropzone = $('vnt-dropzone');
        els.fileInput = $('vnt-file-input');
        els.captureBtn = $('vnt-capture-btn');
        els.uploadBtn = $('vnt-upload-btn');
        els.designSection = $('vnt-design-section');
        els.selectedWrap = $('vnt-selected-product');
        els.selectedImg = $('vnt-selected-product-img');
        els.selectedName = $('vnt-selected-product-name');
        els.changeDesign = $('vnt-change-design');
        els.productList = $('vnt-product-list');
        els.productSearch = $('vnt-product-search');
        els.productEmpty = $('vnt-product-empty');
        els.shapeOptions = $('vnt-shape-options');
        els.lengthOptions = $('vnt-length-options');
        els.generateBtn = $('vnt-generate-btn');
        els.error = $('vnt-error');
        els.resultIdle = $('vnt-result-idle');
        els.resultProcessing = $('vnt-result-processing');
        els.resultDone = $('vnt-result-done');
        els.resultImage = $('vnt-result-image');
        els.beforeImage = $('vnt-before-image');
        els.beforeToggle = $('vnt-before-toggle');
        els.viewProduct = $('vnt-view-product');
        els.tryAnother = $('vnt-try-another');
        els.downloadBtn = $('vnt-download-btn');
        els.previewActions = $('vnt-preview-actions');
        els.statusDot = $('vnt-status-dot');
        els.statusText = $('vnt-status-text');
        els.lightbox = $('vnt-lightbox');
        els.lightboxImg = $('vnt-lightbox-img');
        els.lightboxClose = $('vnt-lightbox-close');
        els.cameraModal = $('vnt-camera-modal');
        els.cameraVideo = $('vnt-camera-video');
        els.cameraCanvas = $('vnt-camera-canvas');
        els.cameraShutter = $('vnt-camera-shutter');
        els.cameraCancel = $('vnt-camera-cancel');
        els.cameraSwitch = $('vnt-camera-switch');
        els.cameraError = $('vnt-camera-error');
        els.cameraDemoBg = $('vnt-camera-demo-bg');
        els.cameraDemoBadge = $('vnt-camera-demo-badge');
        els.historyWrap = $('vnt-history-wrap');
        els.historyList = $('vnt-history-list');
        els.backgroundNotice = $('vnt-background-notice');
    }

    function setHidden(el, hidden) {
        if (!el) return;
        el.classList.toggle('is-hidden', !!hidden);
        el.classList.toggle('hidden', !!hidden);
    }

    function showError(message) {
        if (!els.error) return;
        if (!message) {
            setHidden(els.error, true);
            els.error.textContent = '';
            return;
        }
        els.error.textContent = message;
        setHidden(els.error, false);
    }

    function updateStatus(mode) {
        if (!els.statusDot || !els.statusText) return;
        els.statusDot.classList.remove('is-ready', 'is-busy');
        if (mode === 'processing') {
            els.statusDot.classList.add('is-busy');
            els.statusText.textContent = 'Processing...';
        } else if (mode === 'done') {
            els.statusDot.classList.add('is-ready');
            els.statusText.textContent = 'Ready';
        } else if (state.handFile && state.product) {
            els.statusText.textContent = 'Ready to generate';
        } else {
            els.statusText.textContent = 'Not ready';
        }
    }

    function updateGenerateButton() {
        if (!els.generateBtn) return;
        var ready = !!state.handFile && !!state.product && !!state.nailShape && !!state.nailLength && !state.processing;
        els.generateBtn.disabled = !ready;
        updateStatus(state.processing ? 'processing' : (state.resultImage ? 'done' : 'idle'));
    }

    function updateProductUi() {
        var hasProduct = !!state.product;
        setHidden(els.designSection, hasProduct);
        setHidden(els.selectedWrap, !hasProduct);
        setHidden(els.changeDesign, !hasProduct);
        if (hasProduct && els.selectedName) els.selectedName.textContent = state.product.name || '';
        if (hasProduct && els.selectedImg) {
            if (state.product.image) {
                els.selectedImg.src = state.product.image;
            } else {
                els.selectedImg.removeAttribute('src');
            }
        }
        if (hasProduct && els.viewProduct && state.product.slug) {
            els.viewProduct.href = '/products/' + encodeURIComponent(state.product.slug);
            setHidden(els.viewProduct, false);
        } else if (els.viewProduct) {
            setHidden(els.viewProduct, true);
        }
        updateGenerateButton();
    }

    function setResultView(mode) {
        setHidden(els.resultIdle, mode !== 'idle');
        setHidden(els.resultProcessing, mode !== 'processing');
        setHidden(els.resultDone, mode !== 'done');
        setHidden(els.previewActions, mode !== 'done');

        if (els.resultIdle) {
            els.resultIdle.style.display = mode === 'idle' ? 'flex' : 'none';
        }
        if (els.resultProcessing) {
            els.resultProcessing.style.display = mode === 'processing' ? 'flex' : 'none';
        }
        if (els.resultDone) {
            els.resultDone.style.display = mode === 'done' ? 'block' : 'none';
        }

        updateStatus(mode);
    }

    function shapeIconClass(name) {
        var key = String(name || '').toLowerCase().replace(/\s+/g, '');
        var map = {
            almond: 'vnt-s-almond',
            coffin: 'vnt-s-coffin',
            square: 'vnt-s-square',
            round: 'vnt-s-round',
            oval: 'vnt-s-oval',
            stiletto: 'vnt-s-stiletto',
            squoval: 'vnt-s-squoval',
        };
        return map[key] || 'vnt-s-oval';
    }

    function lengthIconWidth(name, index) {
        var key = String(name || '').toLowerCase();
        if (key.indexOf('extra') !== -1) return 24;
        if (key.indexOf('long') !== -1) return 20;
        if (key.indexOf('medium') !== -1 || key.indexOf('vừa') !== -1) return 16;
        if (key.indexOf('short') !== -1) return 12;
        return 12 + (index || 0) * 4;
    }

    function bindOptionGroup(container, group) {
        if (!container) return;
        container.querySelectorAll('.vnt-option-btn[data-group="' + group + '"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                container.querySelectorAll('.vnt-option-btn[data-group="' + group + '"]').forEach(function (b) {
                    b.classList.remove('is-selected');
                });
                btn.classList.add('is-selected');
                if (group === 'shape') state.nailShape = btn.getAttribute('data-value') || '';
                if (group === 'length') state.nailLength = btn.getAttribute('data-value') || '';
                updateGenerateButton();
            });
        });
    }

    function renderOptionGroup(container, group, options, selected) {
        if (!container) return;
        container.innerHTML = (options || []).map(function (value, index) {
            var active = value === selected;
            var icon = group === 'shape'
                ? '<span class="vnt-shape-icon ' + shapeIconClass(value) + '"></span>'
                : '<span class="vnt-len-icon" style="width:' + lengthIconWidth(value, index) + 'px"></span>';
            return '<button type="button" class="vnt-pill vnt-option-btn' + (active ? ' is-selected' : '') +
                '" data-group="' + group + '" data-value="' + escapeAttr(value) + '">' +
                icon + escapeHtml(value) + '</button>';
        }).join('');
        bindOptionGroup(container, group);
    }

    function loadProductOptions(productId) {
        fetch(cfg.routes.productOptions + '/' + productId + '/options', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                if (data.shapes && data.shapes.length) {
                    state.nailShape = pickExisting(state.nailShape, data.shapes, cfg.defaultShape);
                    renderOptionGroup(els.shapeOptions, 'shape', data.shapes, state.nailShape);
                }
                if (data.lengths && data.lengths.length) {
                    state.nailLength = pickExisting(state.nailLength, data.lengths, cfg.defaultLength);
                    renderOptionGroup(els.lengthOptions, 'length', data.lengths, state.nailLength);
                }
                updateGenerateButton();
            })
            .catch(function () {});
    }

    function pickExisting(current, list, fallback) {
        var lower = String(current || '').toLowerCase();
        for (var i = 0; i < list.length; i++) {
            if (String(list[i]).toLowerCase() === lower) return list[i];
        }
        for (var j = 0; j < list.length; j++) {
            if (String(list[j]).toLowerCase() === String(fallback || '').toLowerCase()) return list[j];
        }
        return list[0] || fallback || '';
    }

    function setHandFile(file) {
        if (!file || !file.type || file.type.indexOf('image/') !== 0) {
            showError('Please choose a valid image file (JPG, PNG, or WEBP).');
            return;
        }
        if (state.handPreviewUrl) URL.revokeObjectURL(state.handPreviewUrl);
        state.handFile = file;
        state.handPreviewUrl = URL.createObjectURL(file);
        if (els.preview) {
            els.preview.src = state.handPreviewUrl;
            els.preview.classList.remove('is-hidden', 'hidden');
        }
        setHidden(els.previewPlaceholder, true);
        setHidden(els.dzBadge, false);
        showError('');
        updateGenerateButton();
    }

    function cameraSupported() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    function showCameraError(message) {
        if (!els.cameraError) return;
        if (!message) {
            els.cameraError.classList.add('hidden');
            els.cameraError.textContent = '';
            return;
        }
        els.cameraError.textContent = message;
        els.cameraError.classList.remove('hidden');
    }

    function stopCamera() {
        if (state.cameraStream) {
            state.cameraStream.getTracks().forEach(function (track) { track.stop(); });
            state.cameraStream = null;
        }
        if (els.cameraVideo) {
            els.cameraVideo.srcObject = null;
        }
    }

    function closeCameraModal() {
        stopCamera();
        showCameraError('');
        if (els.cameraVideo) {
            els.cameraVideo.classList.remove('hidden');
        }
        if (els.cameraDemoBg) {
            els.cameraDemoBg.classList.add('hidden');
        }
        if (els.cameraDemoBadge) {
            els.cameraDemoBadge.classList.add('hidden');
        }
        if (els.cameraModal) {
            els.cameraModal.classList.add('hidden');
            els.cameraModal.setAttribute('aria-hidden', 'true');
        }
        document.body.classList.remove('overflow-hidden');
        if (els.cameraShutter) els.cameraShutter.disabled = false;
    }

    function openCameraDemoMode() {
        if (!els.cameraModal) return;

        stopCamera();
        els.cameraModal.classList.remove('hidden');
        els.cameraModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');

        if (els.cameraVideo) els.cameraVideo.classList.add('hidden');
        if (els.cameraDemoBg) els.cameraDemoBg.classList.remove('hidden');
        if (els.cameraDemoBadge) els.cameraDemoBadge.classList.remove('hidden');
        if (els.cameraShutter) els.cameraShutter.disabled = true;
        showCameraError('');
    }

    function startCamera() {
        if (!cameraSupported()) {
            showCameraError('Camera is not supported in this browser.');
            if (els.cameraShutter) els.cameraShutter.disabled = true;
            return Promise.reject(new Error('unsupported'));
        }

        stopCamera();
        showCameraError('');
        if (els.cameraShutter) els.cameraShutter.disabled = true;

        var constraints = {
            audio: false,
            video: {
                facingMode: { ideal: state.cameraFacing },
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
        };

        return navigator.mediaDevices.getUserMedia(constraints)
            .then(function (stream) {
                state.cameraStream = stream;
                if (els.cameraVideo) {
                    els.cameraVideo.srcObject = stream;
                    updateCameraMirror();
                    return els.cameraVideo.play();
                }
            })
            .then(function () {
                if (els.cameraShutter) els.cameraShutter.disabled = false;
            })
            .catch(function () {
                showCameraError('Could not access the camera. Check permissions or use Upload Image.');
                if (els.cameraShutter) els.cameraShutter.disabled = true;
                throw new Error('camera_denied');
            });
    }

    function openCameraModal() {
        if (cfg.cameraDemo) {
            openCameraDemoMode();
            return;
        }

        if (!els.cameraModal) {
            fallbackNativeCapture();
            return;
        }

        if (!cameraSupported()) {
            fallbackNativeCapture();
            return;
        }

        if (els.cameraVideo) els.cameraVideo.classList.remove('hidden');
        if (els.cameraDemoBg) els.cameraDemoBg.classList.add('hidden');

        els.cameraModal.classList.remove('hidden');
        els.cameraModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        startCamera().catch(function () {});
    }

    function fallbackNativeCapture() {
        if (!els.fileInput) return;
        els.fileInput.setAttribute('capture', 'environment');
        els.fileInput.click();
    }

    function updateCameraMirror() {
        if (!els.cameraVideo) return;
        els.cameraVideo.style.transform = state.cameraFacing === 'user' ? 'scaleX(-1)' : '';
    }

    function captureFromCamera() {
        if (!els.cameraVideo || !els.cameraCanvas || !state.cameraStream) return;

        var video = els.cameraVideo;
        var canvas = els.cameraCanvas;
        var width = video.videoWidth;
        var height = video.videoHeight;

        if (!width || !height) {
            showCameraError('Camera is still loading. Please wait a moment.');
            return;
        }

        canvas.width = width;
        canvas.height = height;

        var ctx = canvas.getContext('2d');
        if (!ctx) return;

        if (state.cameraFacing === 'user') {
            ctx.translate(width, 0);
            ctx.scale(-1, 1);
        }

        ctx.drawImage(video, 0, 0, width, height);

        canvas.toBlob(function (blob) {
            if (!blob) {
                showCameraError('Could not capture photo. Please try again.');
                return;
            }

            var file = new File([blob], 'hand-photo.jpg', { type: 'image/jpeg', lastModified: Date.now() });
            closeCameraModal();
            setHandFile(file);
        }, 'image/jpeg', 0.92);
    }

    function switchCamera() {
        state.cameraFacing = state.cameraFacing === 'environment' ? 'user' : 'environment';
        startCamera().catch(function () {});
    }

    function loadProducts(search) {
        if (!els.productList) return;
        els.productList.innerHTML = '<p style="grid-column:1/-1;text-align:center;font-size:13px;color:#8A7A76;padding:24px 0;">Loading...</p>';
        setHidden(els.productEmpty, true);

        var url = cfg.routes.products + (search ? ('?search=' + encodeURIComponent(search)) : '');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                state.products = (data && data.products) ? data.products : [];
                renderProductList();
            })
            .catch(function () {
                els.productList.innerHTML = '';
                showError('Could not load nail designs.');
            });
    }

    function renderProductList() {
        if (!els.productList) return;
        if (!state.products.length) {
            els.productList.innerHTML = '';
            setHidden(els.productEmpty, false);
            return;
        }
        setHidden(els.productEmpty, true);

        els.productList.innerHTML = state.products.map(function (p) {
            var selected = state.product && state.product.id === p.id;
            var media = p.image
                ? '<img src="' + escapeAttr(p.image) + '" alt="">'
                : '<div class="vnt-swatch__empty">No image</div>';
            return (
                '<button type="button" class="vnt-design-item' + (selected ? ' is-selected' : '') + '" data-id="' + p.id + '">' +
                '<div class="vnt-swatch">' + media + '<span class="vnt-swatch__check">✓</span></div>' +
                '<div class="vnt-design-label">' + escapeHtml(p.name) + '</div></button>'
            );
        }).join('');

        els.productList.querySelectorAll('.vnt-design-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-id'), 10);
                var picked = state.products.find(function (p) { return p.id === id; });
                if (!picked) return;
                state.product = picked;
                updateProductUi();
                renderProductList();
                loadProductOptions(picked.id);
            });
        });
    }

    function runTryOn() {
        if (!state.handFile || !state.product) return;
        state.processing = true;
        updateGenerateButton();
        showError('');
        setResultView('processing');

        var form = new FormData();
        form.append('hand_image', state.handFile);
        form.append('product_id', String(state.product.id));
        form.append('nail_shape', state.nailShape);
        form.append('nail_length', state.nailLength);
        form.append('_token', cfg.csrf || '');

        fetch(cfg.routes.try, {
            method: 'POST',
            body: form,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, status: r.status, data: data }; }); })
            .then(function (res) {
                if (res.status === 202 && res.data && res.data.async && res.data.trial_id) {
                    trackAsyncTrial(res.data.trial_id);
                    showBackgroundQueuedMessage();
                    return;
                }
                if (!res.ok || !res.data || !res.data.success) {
                    throw new Error((res.data && res.data.message) ? res.data.message : 'Generation failed.');
                }
                state.resultImage = res.data.image;
                showResult();
            })
            .catch(function (err) {
                setResultView('idle');
                showError(err.message || 'Something went wrong. Please try again.');
            })
            .finally(function () {
                state.processing = false;
                updateGenerateButton();
                loadHistory();
            });
    }

    function trackAsyncTrial(trialId) {
        if (window.VirtualNailNotifier) {
            window.VirtualNailNotifier.addPending(trialId, {
                product_name: state.product ? state.product.name : 'Virtual try-on',
            });
        }
        state.pendingTrialId = trialId;
        if (els.backgroundNotice) {
            setHidden(els.backgroundNotice, false);
        }
    }

    function showBackgroundQueuedMessage() {
        setResultView('processing');
        if (els.resultProcessing) {
            var spinText = els.resultProcessing.querySelector('.vnt-spin-text');
            if (spinText) {
                spinText.textContent = 'Generating in the background...';
            }
        }
        showError('');
        if (window.showToast) {
            window.showToast('Preview is generating. You can browse the site — we will alert you when it is ready.', 'success');
        }
    }

    function pollTrialStatus(trialId) {
        if (!cfg.routes.trialStatus) return Promise.resolve(null);
        var url = cfg.routes.trialStatus.replace('__UUID__', encodeURIComponent(trialId));
        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).then(function (r) { return r.json(); });
    }

    function applyTrialResult(data) {
        if (!data || data.status !== 'completed') return;
        state.resultImage = data.result_url || data.image;
        if (data.hand_url && els.beforeImage) {
            els.beforeImage.src = data.hand_url;
        }
        if (window.VirtualNailNotifier) {
            window.VirtualNailNotifier.removePending(data.id);
        }
        state.pendingTrialId = null;
        if (els.backgroundNotice) setHidden(els.backgroundNotice, true);
        showResult();
    }

    function loadHistory() {
        if (!cfg.routes.history || !els.historyList) return;
        fetch(cfg.routes.history, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var trials = (data && data.trials) ? data.trials : [];
                renderHistory(trials);
            })
            .catch(function () {});
    }

    function renderHistory(trials) {
        if (!els.historyList || !els.historyWrap) return;
        if (!trials.length) {
            els.historyList.innerHTML = '';
            setHidden(els.historyWrap, true);
            return;
        }
        setHidden(els.historyWrap, false);
        els.historyList.innerHTML = trials.map(function (trial) {
            var statusLabel = trial.status === 'completed' ? 'Ready' : (trial.status === 'failed' ? 'Failed' : 'Processing');
            var thumb = trial.result_url
                ? '<img src="' + escapeAttr(trial.result_url) + '" alt="" class="vnt-history__thumb">'
                : '<span class="vnt-history__thumb vnt-history__thumb--pending">' + (trial.status === 'processing' || trial.status === 'pending' ? '…' : '!') + '</span>';
            var productName = trial.product ? trial.product.name : 'Try-on';
            return (
                '<button type="button" class="vnt-history__item" data-trial="' + escapeAttr(trial.id) + '" data-status="' + escapeAttr(trial.status) + '">' +
                thumb +
                '<span class="vnt-history__meta">' +
                '<span class="vnt-history__name">' + escapeHtml(productName) + '</span>' +
                '<span class="vnt-history__sub">' + escapeHtml(trial.nail_shape + ' · ' + trial.nail_length) + ' · ' + escapeHtml(statusLabel) + '</span>' +
                '</span></button>'
            );
        }).join('');

        els.historyList.querySelectorAll('.vnt-history__item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var trialId = btn.getAttribute('data-trial');
                var status = btn.getAttribute('data-status');
                if (status === 'completed') {
                    pollTrialStatus(trialId).then(applyTrialResult);
                    return;
                }
                if (status === 'pending' || status === 'processing') {
                    trackAsyncTrial(trialId);
                    showBackgroundQueuedMessage();
                }
            });
        });
    }

    function resumeTrialFromQuery() {
        var params = new URLSearchParams(window.location.search);
        var trialId = params.get('trial');
        if (!trialId) return;
        pollTrialStatus(trialId).then(function (data) {
            if (!data) return;
            if (data.status === 'completed') {
                applyTrialResult(data);
            } else if (data.status === 'pending' || data.status === 'processing') {
                trackAsyncTrial(trialId);
                showBackgroundQueuedMessage();
            } else if (data.status === 'failed') {
                showError(data.error_message || 'Generation failed.');
                setResultView('idle');
            }
        });
    }

    function showResult() {
        if (els.resultImage) {
            els.resultImage.src = state.resultImage || '';
            els.resultImage.classList.remove('is-hidden', 'hidden');
        }
        if (els.beforeImage && state.handPreviewUrl) {
            els.beforeImage.src = state.handPreviewUrl;
            setHidden(els.beforeImage, true);
        }
        state.showingBefore = false;
        if (els.beforeToggle) {
            els.beforeToggle.setAttribute('aria-pressed', 'false');
            els.beforeToggle.textContent = 'Before';
        }
        updateProductUi();
        setResultView('done');
        if (els.resultDone) els.resultDone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function escapeAttr(text) {
        return escapeHtml(text).replace(/"/g, '&quot;');
    }

    function openLightbox(src) {
        if (!els.lightbox || !els.lightboxImg || !src) return;
        els.lightboxImg.src = src;
        setHidden(els.lightbox, false);
        els.lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    }

    function closeLightbox() {
        if (!els.lightbox) return;
        setHidden(els.lightbox, true);
        els.lightbox.setAttribute('aria-hidden', 'true');
        if (els.lightboxImg) els.lightboxImg.removeAttribute('src');
        if (!els.cameraModal || els.cameraModal.classList.contains('hidden')) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    function bindEvents() {
        if (els.captureBtn) {
            els.captureBtn.addEventListener('click', openCameraModal);
        }
        if (els.uploadBtn && els.fileInput) {
            els.uploadBtn.addEventListener('click', function () {
                els.fileInput.removeAttribute('capture');
                els.fileInput.click();
            });
        }
        if (els.dropzone && els.fileInput) {
            els.dropzone.addEventListener('click', function () {
                els.fileInput.removeAttribute('capture');
                els.fileInput.click();
            });
            els.dropzone.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    els.fileInput.click();
                }
            });
        }
        if (els.fileInput) {
            els.fileInput.addEventListener('change', function () {
                if (els.fileInput.files && els.fileInput.files[0]) setHandFile(els.fileInput.files[0]);
            });
        }
        if (els.cameraCancel) {
            els.cameraCancel.addEventListener('click', closeCameraModal);
        }
        if (els.cameraShutter) {
            els.cameraShutter.addEventListener('click', captureFromCamera);
        }
        if (els.cameraSwitch) {
            els.cameraSwitch.addEventListener('click', switchCamera);
        }
        if (els.cameraModal) {
            els.cameraModal.addEventListener('click', function (event) {
                if (event.target === els.cameraModal.firstElementChild) {
                    closeCameraModal();
                }
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            if (els.lightbox && !els.lightbox.classList.contains('is-hidden') && !els.lightbox.classList.contains('hidden')) {
                closeLightbox();
                return;
            }
            if (els.cameraModal && !els.cameraModal.classList.contains('hidden')) {
                closeCameraModal();
            }
        });
        if (els.resultImage) {
            els.resultImage.addEventListener('click', function () {
                openLightbox(els.resultImage.src || state.resultImage);
            });
        }
        if (els.beforeImage) {
            els.beforeImage.addEventListener('click', function () {
                openLightbox(els.beforeImage.src || state.handPreviewUrl);
            });
        }
        if (els.lightboxClose) {
            els.lightboxClose.addEventListener('click', function (event) {
                event.stopPropagation();
                closeLightbox();
            });
        }
        if (els.lightbox) {
            els.lightbox.addEventListener('click', function (event) {
                if (event.target === els.lightbox) closeLightbox();
            });
        }
        if (els.generateBtn) els.generateBtn.addEventListener('click', runTryOn);
        if (els.productSearch) {
            els.productSearch.addEventListener('input', function () {
                clearTimeout(state.searchTimer);
                var q = els.productSearch.value.trim();
                state.searchTimer = setTimeout(function () { loadProducts(q); }, 300);
            });
        }
        if (els.changeDesign) {
            els.changeDesign.addEventListener('click', function () {
                state.product = null;
                updateProductUi();
                loadProducts('');
            });
        }
        if (els.beforeToggle) {
            els.beforeToggle.addEventListener('click', function () {
                state.showingBefore = !state.showingBefore;
                els.beforeToggle.setAttribute('aria-pressed', state.showingBefore ? 'true' : 'false');
                els.beforeToggle.textContent = state.showingBefore ? 'After' : 'Before';
                setHidden(els.beforeImage, !state.showingBefore);
                setHidden(els.resultImage, state.showingBefore);
                if (els.resultImage) els.resultImage.classList.toggle('is-hidden', state.showingBefore);
                if (els.beforeImage) els.beforeImage.classList.toggle('is-hidden', !state.showingBefore);
            });
        }
        if (els.tryAnother) {
            els.tryAnother.addEventListener('click', function () {
                if (state.handFile && state.product) {
                    runTryOn();
                    return;
                }
                state.resultImage = null;
                setResultView('idle');
            });
        }
        if (els.downloadBtn) {
            els.downloadBtn.addEventListener('click', function () {
                var src = state.resultImage || state.handPreviewUrl;
                if (!src) return;
                var a = document.createElement('a');
                a.href = src;
                a.download = 'nail-preview.png';
                a.click();
            });
        }
        bindOptionGroup(els.shapeOptions, 'shape');
        bindOptionGroup(els.lengthOptions, 'length');

        document.addEventListener('virtual-nail:trial-ready', function (event) {
            if (!event.detail) return;
            if (state.pendingTrialId && event.detail.id === state.pendingTrialId) {
                applyTrialResult(event.detail);
            }
            loadHistory();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        cacheElements();
        bindEvents();
        updateProductUi();
        if (!state.product) {
            loadProducts('');
        } else {
            loadProductOptions(state.product.id);
        }
        setResultView('idle');
        loadHistory();
        resumeTrialFromQuery();

        if (cfg.cameraDemo) {
            setTimeout(openCameraDemoMode, 400);
        }
    });

    window.addEventListener('pagehide', stopCamera);
})();
