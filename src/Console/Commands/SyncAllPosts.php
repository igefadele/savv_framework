<?php 

namespace Savv\Console\Commands;

use Savv\Services\{ PostService };

class SyncAllPosts
{
    public function execute($args = null) {
        echo PostService::syncAllPosts();
        exit;
    }
}