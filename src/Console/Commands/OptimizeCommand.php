<?php

namespace Savv\Console\Commands;
use Savv\Services\{ CachePostService, CachePageService };

class OptimizeCommand
{
    public function execute($args = null) {
        echo "Optimizing Savv Application...\n";
        echo "Caching all routes...\n";
        (new RouteCache)->execute();
        echo "Caching all pages...\n";
        echo CachePageService::cacheAllPages() . "\n";
        echo "Syncing all posts...\n";
        echo CachePostService::syncAllPosts() . "\n";
        echo "Caching all posts...\n";
        echo CachePostService::cacheAllPosts() . "\n";
        echo "Optimization complete!\n";
        exit;
    }
}     