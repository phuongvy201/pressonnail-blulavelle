(function () {
    'use strict';

    var STORAGE_KEY = 'vntPendingTrials';
    var pollTimer = null;
    var progressEl = null;

    function readPending() {
        try {
            var raw = sessionStorage.getItem(STORAGE_KEY);
            var list = raw ? JSON.parse(raw) : [];
            return Array.isArray(list) ? list.filter(Boolean) : [];
        } catch (e) {
            return [];
        }
    }

    function writePending(list) {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(list));
        updateProgressBanner();
    }

    function addPending(trialId, meta) {
        var list = readPending();
        if (list.some(function (item) { return item.id === trialId; })) {
            updateProgressBanner();
            startPolling();
            return;
        }
        list.push({
            id: trialId,
            product_name: meta && meta.product_name ? meta.product_name : 'Virtual try-on',
            added_at: Date.now(),
        });
        writePending(list);
        startPolling();
    }

    function removePending(trialId) {
        writePending(readPending().filter(function (item) { return item.id !== trialId; }));
    }

    function config() {
        return window.virtualNailNotifierConfig || {};
    }

    function statusUrl(trialId) {
        var base = config().statusUrlTemplate;
        if (!base) return '/api/virtual-nail/trials/' + encodeURIComponent(trialId) + '/status';
        return base.replace('__UUID__', encodeURIComponent(trialId));
    }

    function pageUrl(trialId) {
        var base = config().pageUrl || '/virtual-nail-try';
        if (!trialId) return base;
        return base + (base.indexOf('?') >= 0 ? '&' : '?') + 'trial=' + encodeURIComponent(trialId);
    }

    function pendingUrl() {
        return config().pendingUrl || '/api/virtual-nail/pending';
    }

    function ensureProgressBanner() {
        if (progressEl) return progressEl;

        progressEl = document.createElement('div');
        progressEl.id = 'vnt-progress-banner';
        progressEl.className = 'vnt-progress-banner is-hidden';
        progressEl.setAttribute('role', 'status');
        progressEl.setAttribute('aria-live', 'polite');
        progressEl.innerHTML =
            '<a href="#" class="vnt-progress-banner__link">' +
            '<span class="vnt-progress-banner__spinner" aria-hidden="true"></span>' +
            '<span class="vnt-progress-banner__text">' +
            '<span class="vnt-progress-banner__title">Creating your nail preview…</span>' +
            '<span class="vnt-progress-banner__sub">Working in the background — tap to view status</span>' +
            '</span>' +
            '</a>';

        progressEl.querySelector('.vnt-progress-banner__link').addEventListener('click', function (event) {
            event.preventDefault();
            var list = readPending();
            window.location.href = pageUrl(list.length ? list[0].id : null);
        });

        document.body.appendChild(progressEl);
        return progressEl;
    }

    function updateProgressBanner() {
        var list = readPending();
        var el = ensureProgressBanner();
        if (!list.length) {
            el.classList.add('is-hidden');
            return;
        }

        el.classList.remove('is-hidden');
        var title = el.querySelector('.vnt-progress-banner__title');
        var sub = el.querySelector('.vnt-progress-banner__sub');
        if (list.length === 1) {
            if (title) title.textContent = 'Creating your nail preview…';
            if (sub) sub.textContent = (list[0].product_name || 'Your design') + ' — tap to check status';
        } else {
            if (title) title.textContent = 'Creating ' + list.length + ' nail previews…';
            if (sub) sub.textContent = 'Working in the background — tap to view';
        }
    }

    function syncPendingFromServer() {
        fetch(pendingUrl(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success || !Array.isArray(data.trials)) return;
                var list = readPending();
                data.trials.forEach(function (trial) {
                    if (!trial || !trial.id) return;
                    if (list.some(function (item) { return item.id === trial.id; })) return;
                    list.push({
                        id: trial.id,
                        product_name: trial.product_name || 'Virtual try-on',
                        added_at: Date.now(),
                    });
                });
                writePending(list);
                if (list.length) startPolling();
            })
            .catch(function () {});
    }

    function showReadyAlert(trialId, productName) {
        var container = document.getElementById('toast-container');
        if (!container) return;

        var toast = document.createElement('div');
        toast.className = 'pointer-events-auto rounded-xl shadow-lg border overflow-hidden transform transition-all duration-300 ease-out bg-white border-sky-200 text-slate-900';
        toast.style.animation = 'toastIn 0.35s ease-out';
        toast.innerHTML =
            '<button type="button" class="vnt-ready-toast w-full text-left p-4 flex items-start gap-3 hover:bg-sky-50 transition-colors">' +
            '<span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-sky-500 text-white text-xs font-bold">AI</span>' +
            '<span class="flex-1 min-w-0">' +
            '<span class="block text-sm font-bold text-slate-900">Your nail preview is ready!</span>' +
            '<span class="block text-xs text-slate-600 mt-0.5">' + escapeHtml(productName || 'Tap to view your try-on') + '</span>' +
            '</span>' +
            '<span class="material-symbols-outlined text-sky-500 shrink-0">chevron_right</span>' +
            '</button>' +
            '<button type="button" class="toast-close absolute top-2 right-2 p-1 rounded-lg text-slate-400 hover:text-slate-700" aria-label="Close">' +
            '<span class="material-symbols-outlined text-lg">close</span></button>';

        toast.style.position = 'relative';
        container.appendChild(toast);

        function remove() {
            toast.style.animation = 'toastOut 0.25s ease-in forwards';
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 260);
        }

        toast.querySelector('.vnt-ready-toast').addEventListener('click', function () {
            window.location.href = pageUrl(trialId);
        });
        toast.querySelector('.toast-close').addEventListener('click', function (event) {
            event.stopPropagation();
            remove();
        });

        setTimeout(remove, 20000);

        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                var n = new Notification('Virtual nail preview ready', {
                    body: productName || 'Tap to view your try-on result.',
                    tag: 'vnt-' + trialId,
                });
                n.onclick = function () {
                    window.focus();
                    window.location.href = pageUrl(trialId);
                };
            } catch (e) {}
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function pollOnce() {
        var pending = readPending();
        if (!pending.length) {
            stopPolling();
            updateProgressBanner();
            return;
        }

        updateProgressBanner();

        pending.forEach(function (item) {
            fetch(statusUrl(item.id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.id) return;
                    if (data.status === 'completed') {
                        removePending(item.id);
                        if (!window.__vntHandledTrials) window.__vntHandledTrials = {};
                        if (window.__vntHandledTrials[item.id]) return;
                        window.__vntHandledTrials[item.id] = true;
                        showReadyAlert(item.id, (data.product && data.product.name) || item.product_name);
                        document.dispatchEvent(new CustomEvent('virtual-nail:trial-ready', { detail: data }));
                    } else if (data.status === 'failed') {
                        removePending(item.id);
                        if (window.showToast) {
                            window.showToast(data.error_message || 'Virtual try-on failed.', 'error');
                        }
                    }
                })
                .catch(function () {});
        });
    }

    function startPolling() {
        updateProgressBanner();
        if (pollTimer) return;
        pollOnce();
        pollTimer = setInterval(pollOnce, 4000);
    }

    function stopPolling() {
        if (!pollTimer) return;
        clearInterval(pollTimer);
        pollTimer = null;
    }

    window.VirtualNailNotifier = {
        addPending: addPending,
        removePending: removePending,
        readPending: readPending,
        pollOnce: pollOnce,
        pageUrl: pageUrl,
        updateProgressBanner: updateProgressBanner,
    };

    document.addEventListener('DOMContentLoaded', function () {
        syncPendingFromServer();
        if (readPending().length) startPolling();

        var params = new URLSearchParams(window.location.search);
        if (params.get('trial')) {
            startPolling();
        }
    });
})();
