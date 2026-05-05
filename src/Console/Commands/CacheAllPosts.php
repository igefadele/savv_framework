<?php 

namespace Savv\Console\Commands;

use Savv\Services\{ PostService };



class CacheAllPosts
{
    public function execute($args = null) {
        echo PostService::cacheAllPosts();
        exit;
    }
}