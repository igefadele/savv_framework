<?php

namespace Savv\Services;

class PostService 
{
    /** 
     * Returns the post file content either as a cached html file or the post-detail.php file 
     * which uses the passed metadata and html content as variables.
    */
    public static function servePost(string $slug) {
        // First check post pre-generated cache files for the post
        $cachedPostsPath = ROOT_PATH . "/storage/framework/posts/" . $slug . ".html";
        if (file_exists($cachedPostsPath)) {
            $cachedHtml = file_get_contents($cachedPostsPath);
            if (strpos($cachedHtml, '<?php') === false) {
                // Set cache headers similar to assets
                // header("Content-Type: text/html");
                // header("Cache-Control: public, max-age=31536000, immutable");
                // header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
                echo $cachedHtml;
                return true;
            }
        }

        // If a matching cached post is not found, then find the md file, parse, add rules, 
        // and return the post-detail.php file for the post.
        $postData = CachePostService::getPostData($slug);
        if (empty($postData)) abort(404, 'Post not found');

        // Render the view
        // The require call inside a method allows us to pass variables into the scope of post-detail.php
        $metadata = $postData['metadata'];
        $content = $postData['content'];
        
        require page_path('/post-detail.php');
    } 
}