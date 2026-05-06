<?php 

namespace Savv\Console\Commands;

use Savv\Services\{ CachePostService };

class SyncAllPosts
{
    public function execute($args = null) {
        echo CachePostService::syncAllPosts();
        exit;
    }
}