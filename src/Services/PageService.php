<?php

namespace Savv\Services;

class PageService
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
        if (empty($allPages)) {
            return "No pages found to cache.";
        }

        $results = [];
        foreach ($allPages as $uri => $path) {
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


    /** 
     * Returns the post file content either as a cached html file or the post-detail.php file 
     * which uses the passed metadata and html content as variables.
    */
    public static function servePage(string $uri) {
        // First check post pre-generated cache files for the post
        $uri = trim($uri, '/');
        $page = ($uri === '') ? 'index' : $uri;
        $cachedPagesPath = ROOT_PATH . "/storage/framework/pages/" . $page . ".html";
        if (file_exists($cachedPagesPath)) {
            $cachedHtml = file_get_contents($cachedPagesPath);
            if (strpos($cachedHtml, '<?php') === false) {
                // Set cache headers similar to assets
                header("Content-Type: text/html");
                header("Cache-Control: public, max-age=31536000, immutable");
                header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
                echo $cachedHtml;
                return true;
            }
        }

        // If a matching cached page is not found, look for the page php file in the views/pages directory and serve it with the page metadata if exists
        $pagePath = page_path($page . '.php');
        if (!file_exists($pagePath)) {
            return false;
        }

        return require $pagePath;
    } 
}
