<?php

namespace Savv\Controllers;

use Savv\Services\BlogService;

class BlogController {

    public function syncPost(string $slug): string {
        echo BlogService::syncPost($slug);
        exit;
    }

    // Create the configs/posts.php file based on the markdown files in the posts/ directory
    public function syncAllPosts(): string {
        echo BlogService::syncAllPosts();
        exit;
    }

    public function cachePost(string $slug): string {
        echo BlogService::cachePost($slug);
        exit;
    }

    public function cacheAllPosts(): string {
        echo BlogService::cacheAllPosts();
        exit;
    }
}