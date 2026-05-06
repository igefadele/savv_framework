<?php 

namespace Savv\Console\Commands;

use Savv\Services\{ CachePageService };


class CacheAllPages
{
    public function execute($args = null) {
        echo CachePageService::cacheAllPages();
        exit;
    }
}