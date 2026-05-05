<?php

namespace Savv\Console\Commands;
use Savv\Services\{ PostService, PageService };

class OptimizeCommand
{
    public function execute($args = null) {
        echo "Optimizing Savv Application...\n";
        echo "Caching all routes...\n";
        (new RouteCache)->execute();
        echo "Caching all pages...\n";
        echo PageService::cacheAllPages() . "\n";
        echo "Syncing all posts...\n";
        echo PostService::syncAllPosts() . "\n";
        echo "Caching all posts...\n";
        echo PostService::cacheAllPosts() . "\n";
        echo "Optimization complete!\n";
        exit;
    }
}     