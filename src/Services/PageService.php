<?php

namespace Savv\Services;

class PageService
{
    /** 
     * Returns the post file content either as a cached html file or the post-detail.php file 
     * which uses the passed metadata and html content as variables.
    */
    public static function servePage(string $uri) {
        // First check post pre-generated cache files for the post
        $dynamicPages = config('app.dynamic_pages') ?? [];
        $uri = trim($uri, '/');
        $page = ($uri === '') ? 'index' : $uri;
        $cachedPagesPath = storage_path("framework/pages/" . $page . ".html");
        
        if (!in_array($page, $dynamicPages) && file_exists($cachedPagesPath)) {
            $cachedHtml = file_get_contents($cachedPagesPath);
            if (strpos($cachedHtml, '<?php') === false) {
                // Set cache headers similar to assets
                // header("Content-Type: text/html");
                // header("Cache-Control: public, max-age=31536000, immutable");
                // header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
                echo $cachedHtml;
                return true;
            }
        }

        // If a matching cached page is not found, look for the page php file in 
        // the views/pages directory and serve it with the page metadata if exists
        $pagePath = page_path($page . '.php');
        if (!file_exists($pagePath)) {
            return false;
        }

        require $pagePath;
        return true;
    } 
}