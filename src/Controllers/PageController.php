<?php

namespace Savv\Controllers;

use Savv\Console\Commands\RouteCache;
use Savv\Services\PageService;

class PageController 
{
    public function cacheRoutes() {
        (new RouteCache())->execute();
        exit;
    }

    public function cachePage(string $uri) {
        echo PageService::cachePage($uri);
        exit;
    }
    
    public function cacheAllPages() {
        echo PageService::cacheAllPages();
        exit;
    }
}
