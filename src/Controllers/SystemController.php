<?php 

namespace Savv\Controllers;

use Savv\Utils\Request;
use Savv\Packages\Parsedown;
use Savv\Services\BlogService;

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
        await Promise.allSettled(
            PRECACHE_URLS.map(url => 
                cache.add(url).catch(err => console.warn(`Failed to cache: ${url}`, err))
            )
        );
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


    public function serveAsset($path) {
        // 1. Construct the full system path
        // Ensure we sanitize this to prevent directory traversal attacks!
        $fullPath = PUBLIC_PATH . '/' . $path;
        
        // Ensure the file is actually inside the assets folder
        if (!file_exists($fullPath) || is_dir($fullPath)) {
            abort(404, 'Asset not found');
        }

        // 2. Determine Mime Type
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $mimes = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'svg'   => 'image/svg+xml'
        ];
        $contentType = $mimes[$extension] ?? 'application/octet-stream';

        // 3. Inject "Zero-Config" Cache Headers
        // max-age is in seconds (31536000 = 1 year)
        header("Content-Type: $contentType");
        header("Cache-Control: public, max-age=31536000, immutable");
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
        
        // 4. Stream the file
        readfile($fullPath);
        exit;
    }


    public function getLocalAsset(string $path) { 
        $fileName = str_replace('savv-assets/', '', $path);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        // Path to savv's internal assets

        $filePath = __DIR__ . '/../assets/' . $extension . '/' . $fileName;

        if (file_exists($filePath)) {
            $mimeType = ($extension === 'css') ? 'text/css' : 'application/javascript';
            header("Content-Type: $mimeType");
            // max-age is in seconds (31536000 = 1 year)
            header("Cache-Control: public, max-age=31536000, immutable");
            header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
            readfile($filePath);
            exit;
        } 
    }
}