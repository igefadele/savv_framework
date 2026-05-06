<?php 

namespace Savv\Console\Commands;

use Savv\Services\{ CachePostService };

class CachePost
{
    public function execute($args = null) {
        if (!isset($args[0])) {
            echo "Error: Please provide a post slug.\n";
            exit(1);
        }

        $slug = $args[0];
        echo CachePostService::cachePost($slug);
        exit;
    }
}