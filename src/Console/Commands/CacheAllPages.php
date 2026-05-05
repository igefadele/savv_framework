<?php 

namespace Savv\Console\Commands;

use Savv\Services\{ PostService, PageService };


class CacheAllPages
{
    public function execute($args = null) {
        echo PageService::cacheAllPages();
        exit;
    }
}