<?php 

namespace Savv\Console\Commands;

use Savv\Services\{ CachePostService };



class CacheAllPosts
{
    public function execute($args = null) {
        echo CachePostService::cacheAllPosts();
        exit;
    }
}