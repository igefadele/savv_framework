<?php

namespace Savv\Controllers;

use Savv\Services\PostService;

class BlogController {

    public function index($page = 1) {
        $posts = include ROOT_PATH . '/configs/posts.php';
        $perPage = 10;
        
        // Use high-performance array_slice for pagination
        $paginatedPosts = array_slice($posts, ($page - 1) * $perPage, $perPage);
        
        return response()->view('frontend.list', ['posts' => $paginatedPosts]);
    }
}