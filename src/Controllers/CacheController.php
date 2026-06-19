<?php

namespace Savv\Controllers;

use Savv\Services\{CacheService, CachePostService, CachePageService, CacheClearService, CacheRouteService};

class CacheController {
    
    /**
     * Sync and cache all routes, pages, and posts. 
     * For routes: Caches all routes and create the cached array record inside /storage/framework/routes.php 
     * For pages: Caches all pages and creates corresponding html files for each page and store them in /storage/framework/pages
     * For posts: Syncs and caches all posts and create corresponding html files for each page and store them in /storage/framework/posts
     * and also creates the synced posts array inside /configs/posts.php
     */
    public function optimize() {
        CacheService::optimize();
    }

    public function clearCaches() {
        CacheService::clearCaches();
    }

    /**
     * Cache all routes and create the cached array record inside /storage/framework/routes.php 
     */
    public function cacheRoutes() {
        CacheRouteService::cacheAllRoutes();
        exit;
    }

    public function clearRoutes() {
        $baseCachePath = ROOT_PATH . '/storage/framework';
        echo "Removing compiled routes layout map...\n";
        CacheClearService::deleteFile($baseCachePath . '/routes.php');        
        echo "\e[32mSuccessfully cleared storage/framework/routes.php.\e[0m\n";
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

    public function clearPost() {
        $baseCachePath = ROOT_PATH . '/storage/framework';
        echo "Clearing global framework cache storage...\n";
        CacheClearService::clearDirectory($baseCachePath, false); // Keeps core base folder, clears elements inside
        echo "\e[32mSuccessfully cleared storage/framework/** contents.\e[0m\n";
        exit;
    }

    /**
     * Cache all posts and create corresponding html files inside /storage/framework/posts 
     */
    public function cacheAllPosts(): string {
        echo CachePostService::cacheAllPosts();
        exit;
    } 

    public function clearAllPosts() {
        $baseCachePath = ROOT_PATH . '/storage/framework';
        echo "Clearing posts cache...\n";
        CacheClearService::clearDirectory($baseCachePath . '/posts', true);
        echo "\e[32mSuccessfully removed posts cache folder hierarchy.\e[0m\n";
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

    public function clearPage() {
        $baseCachePath = ROOT_PATH . '/storage/framework';
        echo "Clearing global framework cache storage...\n";
        CacheClearService::clearDirectory($baseCachePath, false); // Keeps core base folder, clears elements inside
        echo "\e[32mSuccessfully cleared storage/framework/** contents.\e[0m\n";
        exit;
    }
    
    /**
     * Cache all pages and create corresponding html files inside /storage/framework/pages 
     */
    public function cacheAllPages() {
        echo CachePageService::cacheAllPages();
        exit;
    }

    public function clearAllPages() {
        $baseCachePath = ROOT_PATH . '/storage/framework';
        echo "Clearing pages cache...\n";
        CacheClearService::clearDirectory($baseCachePath . '/pages', true);
        echo "\e[32mSuccessfully removed pages cache folder hierarchy.\e[0m\n";
        exit;
    }
}