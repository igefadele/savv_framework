<?php

namespace Savv\Controllers;

use Savv\Services\{PostService, PageService};
use Savv\Console\Commands\RouteCache;

class CacheController {

    /** 
     * Sync a single post and add its metadata record to the configs/posts.php array.
     * @param string $slug: the slug of the post, e.g `how-to-savv-website`.
     */ 
    public function syncPost(string $slug): string {
        echo PostService::syncPost($slug);
        exit;
    }

    /** 
     * Create the configs/posts.php file based on the markdown files in the posts/ directory
    */
    public function syncAllPosts(): string {
        echo PostService::syncAllPosts();
        exit;
    }

    /**
     * Cache a single post and create the corresponding html file in /storage/framework/posts directory.
     * @param string $slug: the slug of the post, e.g `how-to-savv-website`.
     */
    public function cachePost(string $slug): string {
        echo PostService::cachePost($slug);
        exit;
    }

    /**
     * Cache all posts and create corresponding html files inside /storage/framework/posts 
     */
    public function cacheAllPosts(): string {
        echo PostService::cacheAllPosts();
        exit;
    }

    /**
     * Cache all routes and create the cached array record inside /storage/framework/routes.php 
     */
    public function cacheRoutes() {
        (new RouteCache())->execute();
        exit;
    }

    /**
     * Cache a page and create corresponding html file inside /storage/framework/pages
     * @param string $uri: the path of the page, e.g `aboute, services/web-development, etc`.
     */
    public function cachePage(string $uri) {
        echo PageService::cachePage($uri);
        exit;
    }
    
    /**
     * Cache all pages and create corresponding html files inside /storage/framework/pages 
     */
    public function cacheAllPages() {
        echo PageService::cacheAllPages();
        exit;
    }
}