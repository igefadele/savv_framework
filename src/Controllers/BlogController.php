<?php

namespace Savv\Controllers;

class BlogController {

    /** 
     * Displays the blog posts list page
     * @query int $page: The current page the user is on.
     * @query int $limit: How many posts per page should be returned.
    */
    public function index() {
        $posts = config('posts'); 
        $totalPosts = count($posts);
        $page = (int) (request()->query('page') ?? 1);
        $limit = (int) (request()->query('limit') ?? 10);

        $offset = ($page - 1) * $limit; 
        $paginatedPosts = array_slice($posts, $offset, $limit, true);
        
        return response()->view('pages/blog', [
            'posts' => $paginatedPosts,
            'currentPage' => $page,
            'limit' => $limit,
            'totalPosts' => $totalPosts,
            'totalPages' => ceil($totalPosts / $limit)
        ]);
    }

    /** 
     * Displays the blog posts list page
     * @param int $page: The current page the user is on.
     * @param int $limit: How many posts per page should be returned.
    */
    public function list(int $page = 1, int $limit = 10) {
        $posts = config('posts'); 
        $totalPosts = count($posts);
        $page = $page ?? 1;
        $limit = $limit ?? 10;

        $offset = ($page - 1) * $limit; 
        $paginatedPosts = array_slice($posts, $offset, $limit, true);
        
        return response()->view('pages/blog', [
            'posts' => $paginatedPosts,
            'currentPage' => $page,
            'limit' => $limit,
            'totalPosts' => $totalPosts,
            'totalPages' => ceil($totalPosts / $limit)
        ]);
    }
}