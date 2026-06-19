<?php 

namespace Savv\Services;

use Savv\Services\{CacheRouteService, CachePageService, CachePostService};

class CacheService 
{
    static public function optimize() {
        echo "Optimizing Savv Application...\n";
        echo "Caching all routes...\n";
        CacheRouteService::cacheAllRoutes();
        echo "Caching all pages...\n";
        echo CachePageService::cacheAllPages() . "\n";
        echo "Syncing all posts...\n";
        echo CachePostService::syncAllPosts() . "\n";
        echo "Caching all posts...\n";
        echo CachePostService::cacheAllPosts() . "\n";
        echo "Optimization complete!\n";
    }

    static public function clearCaches() {
        $baseCachePath = ROOT_PATH . '/storage/framework';
        echo "Clearing global framework cache storage...\n";
        CacheClearService::clearDirectory($baseCachePath, false); // Keeps core base folder, clears elements inside
        echo "\e[32mSuccessfully cleared storage/framework/** contents.\e[0m\n";
        exit;
    }
}