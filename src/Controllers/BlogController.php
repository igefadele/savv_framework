<?php

namespace Savv\Controllers;

use Savv\Services\BlogService;

class BlogController {

    // Create the configs/posts.php file based on the markdown files in the posts/ directory
    public function syncPosts() {
        echo BlogService::syncPosts();
        exit;
    }
}