<?php

namespace Savv\Controllers;

use Savv\Services\{CachePostService, CachePageService};
use Savv\Console\Commands\RouteCache;

class CacheController {
    
    /**
     * Cache all routes and create the cached array record inside /storage/framework/routes.php 
     */
    public function cacheRoutes() {
        (new RouteCache())->execute();
        exit;
    }
    
    /** 
     * Sync a single post and add its metadata record to the configs/posts.php array.
     * @param string $slug: the slug of the post, e.g `how-to-savv-website`.
     */ 
    public function syncPost(string $slug): string {
        echo CachePostService::syncPost($slug);
        exit;
    }

    /** 
     * Create the configs/posts.php file based on the markdown files in the posts/ directory
    */
    public function syncAllPosts(): string {
        echo CachePostService::syncAllPosts();
        exit;
    }

    /**
     * Cache a single post and create the corresponding html file in /storage/framework/posts directory.
     * @param string $slug: the slug of the post, e.g `how-to-savv-website`.
     */
    public function cachePost(string $slug): string {
        echo CachePostService::cachePost($slug);
        exit;
    }

    /**
     * Cache all posts and create corresponding html files inside /storage/framework/posts 
     */
    public function cacheAllPosts(): string {
        echo CachePostService::cacheAllPosts();
        exit;
    } 

    /**
     * Cache a page and create corresponding html file inside /storage/framework/pages
     * @param string $uri: the path of the page, e.g `aboute, services/web-development, etc`.
     */
    public function cachePage(string $uri) {
        echo CachePageService::cachePage($uri);
        exit;
    }
    
    /**
     * Cache all pages and create corresponding html files inside /storage/framework/pages 
     */
    public function cacheAllPages() {
        echo CachePageService::cacheAllPages();
        exit;
    }
}