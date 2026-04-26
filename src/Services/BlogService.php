<?php 

namespace Savv\Services;

use Savv\Packages\Parsedown;

class BlogService
{
    public static function applyAdRules($html, $rules): string {
        $header_html = "";
        $footer_html = "";
        $content_rules = [];

        // 1. Separate Header/Footer and Prep Content Rules
        foreach ($rules as $rule) {
            if (!$rule['enabled'] || !file_exists($rule['snippet'])) continue;

            $snippet = file_get_contents($rule['snippet']);

            if ($rule['type'] === 'header') {
                $header_html .= '<div class="ad-header">' . $snippet . '</div>';
            } elseif ($rule['type'] === 'footer') {
                $footer_html .= '<div class="ad-footer">' . $snippet . '</div>';
            } elseif ($rule['type'] === 'content') {
                // Cache the snippet inside the rule to avoid multiple file reads
                $rule['cached_snippet'] = $snippet;
                $content_rules[] = $rule;
            }
        }

        // 2. Process Content Paragraphs
        $paragraphs = explode('</p>', $html);
        $total_paras = count($paragraphs);
        $output_content = "";
        $injected_count = 0;

        foreach ($paragraphs as $index => $para) {
            $output_content .= $para . '</p>';
            $current_pos = $index + 1;  

            // Check every content rule against this specific paragraph position
            foreach ($content_rules as $rule) {
                if ($total_paras < $rule['min_length']) continue;

                $should_inject = false;

                if ($rule['repeating']) {
                    if ($current_pos % $rule['frequency'] === 0 && $current_pos < $total_paras) {
                        $should_inject = true;
                    }
                } else {
                    if ($current_pos === $rule['frequency']) {
                        $should_inject = true;
                    }
                }

                // Prevent more than 5 ads in a single post 
                if ($should_inject && $injected_count < 5) {
                    $output_content .= '<div class="ad-content">' . $rule['cached_snippet'] . '</div>';
                    $injected_count++;
                }
            }
        }

        return $header_html . $output_content . $footer_html;
    }

    /** 
     * This go through the post .md file, separate the metadata and content, and return them as an array.
    */
    public static function splitPostData($path): array|null {
        $rules = config('ads'); // Load ad rules for content injection
        $metadata = [];
        $markdownContent = "# Post not found";

        $file_content = file_get_contents($path);
        
        // Split the file using the --- delimiters
        $parts = preg_split('/^---$/m', $file_content, 3);

        // If the file doesn't have the expected format, treat the whole content as markdown
        if (count($parts) < 3) { 
            $markdownContent = $file_content; 
        } else {
            $markdownContent = trim($parts[2]);
            // Parse the metadata lines
            $lines = explode("\n", trim($parts[1]));
            foreach ($lines as $line) {
                $kv = explode(":", $line, 2);
                if (count($kv) == 2) {
                    $metadata[trim($kv[0])] = trim($kv[1]);
                }
            }
        }  

        $parsedown = new Parsedown();
        $htmlContent = $parsedown->text($markdownContent); 

        // Apply ads rules and injections
        $processedContent = BlogService::applyAdRules($htmlContent, $rules);

        return [
            'metadata' => $metadata,
            'content' => $processedContent
        ];
    } 

    public static function servePost($slug) {
        $allPostConfig = config('posts');
        
        if (!isset($allPostConfig[$slug])) {
            // Fallback: check if file exists directly even if not in config
            $fallbackPath = post_path("/{$slug}.md");
            if (!file_exists($fallbackPath)) {
                abort(404, 'Post not found');
            }
            $postData = self::splitPostData($fallbackPath);
        } else {
            $postConfig = $allPostConfig[$slug];
            $postData = self::splitPostData($postConfig['path']);
            
            // Merge config metadata (like timestamps) into the post data
            $postData['metadata'] = array_merge($postData['metadata'], $postConfig);
        }

        // Render the view
        // The require call inside a method allows us to pass variables into the scope of post-detail.php
        $metadata = $postData['metadata'];
        $content = $postData['content'];
        
        require page_path('/post-detail.php');
    } 


    // Create the configs/posts.php file based on the markdown files in the posts/ directory
    public static function syncPosts() {
        $postFiles = glob(post_path('/*.md'));
        $manifest = [];

        foreach ($postFiles as $file) {
            $data = self::getMetadataOnly($file);
            $meta = $data['metadata'] ?? [];
            
            // Get file system times
            $fileCreated = date("Y-m-d H:i:s", filectime($file));
            $fileUpdated = date("Y-m-d H:i:s", filemtime($file));

            $slug = $meta['slug'] ?? strtolower(str_replace(' ', '-', basename($file, '.md')));
            
            $manifest[$slug] = [
                'slug'      => $slug,
                'title'     => $meta['title'] ?? basename($file, '.md'),
                'path'      => $file,
                'createdAt' => isset($meta['date']) ? $meta['date'] . " 00:00:00" : $fileCreated,
                'updatedAt' => $fileUpdated,
                'author'    => $meta['author'] ?? 'Admin',
                'category'  => $meta['category'] ?? 'General',
                'tags'      => $meta['tags'] ?? "",
                'excerpt'   => $meta['excerpt'] ?? "" ,
                'keyphrase' => $meta['keyphrase'] ?? "",
            ];
        }

        $content = "<?php\n\n// Generated for Savv Framework - " . date('Y-m-d H:i:s') . "\nreturn " . var_export($manifest, true) . ";";
        
        $configPath = ROOT_PATH . '/configs/posts.php';
        if (file_put_contents($configPath, $content)) {
            echo "Successfully synced " . count($manifest) . " posts to config.";
        } else {
            echo "Failed to write posts config.";
        }
        
    }

    /** 
     * Lightweight helper to just get front-matter 
    */
    public static function getMetadataOnly($path): array {
        $content = file_get_contents($path);
        $parts = preg_split('/^---$/m', $content, 3);
        if (count($parts) < 3) return [];
        
        $metadata = [];
        $lines = explode("\n", trim($parts[1]));
        foreach ($lines as $line) {
            $kv = explode(":", $line, 2);
            if (count($kv) == 2) $metadata[trim($kv[0])] = trim($kv[1]);
        }
        return $metadata;
    }
}