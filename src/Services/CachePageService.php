<?php

namespace Savv\Services;

class CachePageService
{
    /** 
     * Generate the post cache html files inside /storage/framework/posts
    */
    public static function cachePage(string $uri): string {
        $cachePath = ROOT_PATH . "/storage/framework/pages";
        if (!is_dir($cachePath)) mkdir($cachePath, 0777, true);

        $uri = trim($uri, '/');
        $page = $uri === '' ? 'index' : $uri;
        $pagePath = page_path($page . '.php');

        if (!file_exists($pagePath)) {
            return "Error, page not found";
        }

        $previousRequestUri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = $uri === '' ? '/' : '/' . $uri;

        ob_start();
        require $pagePath;
        $html = ob_get_clean();

        if ($previousRequestUri === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $previousRequestUri;
        }

        $filename = $cachePath . DIRECTORY_SEPARATOR . ($uri === '' ? 'index' : $uri) . '.html';
        if (!is_dir(dirname($filename))) mkdir(dirname($filename), 0777, true);

        $dataToPut = "<!-- Savv Pages Cache: " . date('Y-m-d H:i:s') . " -->\n" . $html;

        if (file_put_contents($filename, $dataToPut)){
            return "Page Cache created successfully!";
        }

        return "Error, page not cached"; 
    }

    /** 
     * 
    */
    public static function cacheAllPages(): string {
        $allPages = self::compilePages();
        $dynamicPages = config('app.dynamic_pages') ?? [];

        if (empty($allPages)) {
            return "No pages found to cache.";
        }

        $results = [];
        foreach ($allPages as $uri => $path) {
            if (in_array($uri, $dynamicPages)) {
                $results[] = "Skipping dynamic page '{$path}'";
                continue;
            }
            $result = self::cachePage($uri);
            $results[] = "Caching '{$path}': " . $result . "\n";
        }

        return implode("\n", $results);
    }


    protected static function compilePages(): array|null {
        $viewDir = ROOT_PATH . '/views/pages';
        if (!is_dir($viewDir)) return null;

        $pages = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewDir));
        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') continue;
            
            $relative = str_replace([$viewDir, '.php'], '', $file->getPathname());
            $uri = trim($relative, '/');
            $uri = ($uri === 'index') ? '' : $uri;

            $pages[$uri] = $file->getPathname();
        }
        return $pages;
    } 
}
