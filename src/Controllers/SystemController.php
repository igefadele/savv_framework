<?php 

namespace Savv\Controllers;

class SystemController 
{
    public function getManifestFile() {
        header('Content-Type: application/json');
        $pwa = config('pwa');

        // We filter the config so we only echo valid Manifest keys
        $manifest = [
            "name"             => $pwa['name'] ?? 'Savv App',
            "short_name"       => $pwa['short_name'] ?? 'Savv',
            "description"      => $pwa['description'] ?? '',
            "id"               => "./",
            "start_url"        => "./",
            "scope"            => "./",
            "display"          => $pwa['display'] ?? "standalone",
            "display_override" => ["standalone", "browser"],
            "orientation"      => "portrait-primary",
            "background_color" => $pwa['background_color'] ?? "#ffffff",
            "theme_color"      => $pwa['theme_color'] ?? "#000000",
            "icons"            => $pwa['icons'] ?? []
        ];

        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function getServiceWorkerFile() {
        header('Content-Type: application/javascript');
        $config = config('pwa');
        $cacheName = strtolower($config['short_name']) . '-cache-' . $config['version'];
        $precache = json_encode($config['precache']);
        ?>
const CACHE_NAME = '<?= $cacheName ?>';
const OFFLINE_URL = '/offline';
const PRECACHE_URLS = <?= $precache ?>;

self.addEventListener('install', (event) => {
event.waitUntil((async () => {
const cache = await caches.open(CACHE_NAME);
await cache.addAll(PRECACHE_URLS);
await self.skipWaiting();
})());
});

self.addEventListener('activate', (event) => {
event.waitUntil((async () => {
const cacheNames = await caches.keys();
await Promise.all(
cacheNames.filter(name => name !== CACHE_NAME)
.map(name => caches.delete(name))
);
await self.clients.claim();
})());
});

self.addEventListener('fetch', (event) => {
if (event.request.method !== 'GET') return;

const requestUrl = new URL(event.request.url);
const isSameOrigin = requestUrl.origin === self.location.origin;

if (event.request.mode === 'navigate') {
event.respondWith((async () => {
try {
const networkResponse = await fetch(event.request);
const cache = await caches.open(CACHE_NAME);
cache.put(event.request, networkResponse.clone());
return networkResponse;
} catch (error) {
const cachedResponse = await caches.match(event.request);
return cachedResponse || caches.match(OFFLINE_URL);
}
})());
return;
}

if (!isSameOrigin) return;

event.respondWith((async () => {
const cachedResponse = await caches.match(event.request);
if (cachedResponse) return cachedResponse;

try {
const networkResponse = await fetch(event.request);
if (networkResponse.ok) {
const cache = await caches.open(CACHE_NAME);
cache.put(event.request, networkResponse.clone());
}
return networkResponse;
} catch (error) {
return null;
}
})());
});
<?php
        exit;
    }
}