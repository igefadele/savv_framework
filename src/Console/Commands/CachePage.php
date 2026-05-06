<?php 

namespace Savv\Console\Commands;

use Savv\Services\{ CachePageService };


class CachePage
{
    public function execute($args = null) {
        if (!isset($args[0])) {
            echo "Error: Please provide a page URI.\n";
            exit(1);
        }

        $uri = $args[0];
        echo CachePageService::cachePage($uri);
        exit;
    }
}