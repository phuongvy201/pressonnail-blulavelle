const SW_VERSION = "pon-v1";
const STATIC_CACHE = `static-${SW_VERSION}`;
const RUNTIME_CACHE = `runtime-${SW_VERSION}`;

const STATIC_ASSETS = [
    "/",
    "/manifest.webmanifest",
    "/favicon.png",
    "/images/logo/BABYBLUE.png",
];

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== STATIC_CACHE && key !== RUNTIME_CACHE)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener("fetch", (event) => {
    const { request } = event;

    if (request.method !== "GET") return;

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) return;

    // Avoid caching admin and API routes.
    if (url.pathname.startsWith("/admin") || url.pathname.startsWith("/api")) {
        return;
    }

    // HTML pages: network-first for fresh content, fallback to cache.
    if (request.mode === "navigate") {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const responseClone = response.clone();
                    caches.open(RUNTIME_CACHE).then((cache) => {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) => cached || caches.match("/"))
                )
        );
        return;
    }

    // Static assets: cache-first with background refresh.
    event.respondWith(
        caches.match(request).then((cached) => {
            const networkFetch = fetch(request)
                .then((response) => {
                    if (response && response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(RUNTIME_CACHE).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => cached);

            return cached || networkFetch;
        })
    );
});
