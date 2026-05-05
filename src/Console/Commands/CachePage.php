<?php 

namespace Savv\Console\Commands;

use Savv\Services\{ PageService };


class CachePage
{
    public function execute($args = null) {
        if (!isset($args[0])) {
            echo "Error: Please provide a page URI.\n";
            exit(1);
        }

        $uri = $args[0];
        echo PageService::cachePage($uri);
        exit;
    }
}