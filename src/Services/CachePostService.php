<?php 

namespace Savv\Services;

use Savv\Packages\Parsedown;

class CachePostService
{
    public static function applyAdRules(string $html, ?array $rules = null): string {
        if (empty($rules)) {
            return $html;
        }

        $header_html = "";
        $footer_html = "";
        $content_rules = [];

        // 1. Separate Header/Footer and Prep Content Rules
        foreach ($rules as $rule) {
            if (
                !is_array($rule) ||
                empty($rule['enabled']) ||
                empty($rule['snippet']) ||
                !file_exists($rule['snippet'])
            ) {
                continue;
            }

            $snippet = file_get_contents($rule['snippet']);
            $type = $rule['type'] ?? '';

            if ($type === 'header') {
                $header_html .= '<div class="ad-header">' . $snippet . '</div>';
            } elseif ($type === 'footer') {
                $footer_html .= '<div class="ad-footer">' . $snippet . '</div>';
            } elseif ($type === 'content') {
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
                $frequency = (int) ($rule['frequency'] ?? 0);
                if ($frequency < 1 || $total_paras < (int) ($rule['min_length'] ?? 0)) continue;

                $should_inject = false;

                if (!empty($rule['repeating'])) {
                    if ($current_pos % $frequency === 0 && $current_pos < $total_paras) {
                        $should_inject = true;
                    }
                } else {
                    if ($current_pos === $frequency) {
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
     * This go through the post .md file, splits the markdown file data, 
     * separating the FrontMatter as the blog metadata and the rest as the blog content
     * and return them as an array.
    */
    public static function splitPostMdData(string $path): array|null {
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

        return [
            'metadata' => $metadata,
            'content' => $markdownContent
        ];
    } 

    /** 
     * Takes the md file, separate the metadata and content, 
     * convert the content to html after applying the ads and snippet rules
     * and return them as an array.
    */
    public static function splitPostData(string $path): array|null {
        $rules = config('ads', []); // Load ad rules for content injection
        if (!is_array($rules)) {
            $rules = [];
        }

        $data = self::splitPostMdData($path);
        $metadata = $data['metadata'];
        $content = $data['content'];

        $parsedown = new Parsedown();
        $htmlContent = $parsedown->text($content); 

        // Apply ads rules and injections
        $processedContent = self::applyAdRules($htmlContent, $rules);

        return [
            'metadata' => $metadata,
            'content' => $processedContent
        ];
    } 

    /** 
     * Returns an array that contains the post metadata and the html content
    */
    public static function getPostData(string $slug): array {
        $allPostConfig = config('posts');
        $postData = [];
        
        if (!isset($allPostConfig[$slug])) {
            // Fallback: check if file exists directly even if not in config
            $fallbackPath = post_path("/{$slug}.md");
            if (!file_exists($fallbackPath)) {
                return $postData;
            }
            $postData = self::splitPostData($fallbackPath);
        } else {
            $postConfig = $allPostConfig[$slug];
            $postData = self::splitPostData($postConfig['path']);
            
            // Merge config metadata (like timestamps) into the post data
            $postData['metadata'] = array_merge($postData['metadata'], $postConfig);
        }

        return $postData;
    } 

    /** 
     * Sync a single post and add its metadata record to the posts.php config
    */
    public static function syncPost(string $slug): string {
        $postPath = post_path("/{$slug}.md");
        if (!file_exists($postPath)) {
            return "Post file not found for slug: {$slug}";
        }

        $allPostConfig = config('posts', []);
        $updatedConfig = self::addMetaToPostArrayConfig($postPath, $allPostConfig);

        $content = "<?php\n\n// Generated for Savv Framework - " . date('Y-m-d H:i:s') . "\nreturn " . var_export($updatedConfig, true) . ";";
        
        $configPath = ROOT_PATH . '/configs/posts.php';
        if (file_put_contents($configPath, $content)) {
            return "Successfully synced post '{$slug}' to config.";
        } else {
            return "Failed to write posts config.";
        }
    }

    
    /** 
     * Create the configs/posts.php file based on the markdown files recursively.
     * Enforces double quotes around metadata values to handle apostrophes seamlessly.
     */
    public static function syncAllPosts(): string {
        $baseDir = post_path(); 
        $manifest = [];

        if (!is_dir($baseDir)) {
            return "Error: Posts directory does not exist at " . $baseDir;
        }

        $directory = new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'md') {
                $filePath = $fileInfo->getRealPath();
                $manifest = self::addMetaToPostArrayConfig($filePath, $manifest);
            }
        }

        // Sort by created_at DESC before saving
        uasort($manifest, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        
        // Generate the base code via var_export
        $exported = var_export($manifest, true);

        // Convert array string values from single quotes to double quotes securely
        // Matches values starting with ' and ending with ', keeping nested characters intact
        $exportedWithDoubleQuotes = preg_replace_callback("/'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'/", function($matches) {
            // Strip out single quote escape slashes, escape double quotes, wrap in clean double quotes
            $cleanContent = str_replace(["\\'", '"'], ["'", '\\"'], $matches[1]);
            return '"' . $cleanContent . '"';
        }, $exported);

        // Make the array pointer formatting to match clean double quote style definitions
        $exportedWithDoubleQuotes = preg_replace("/\"([a-zA-Z0-9_\-]+)\" =>/", "'$1' =>", $exportedWithDoubleQuotes);

        $content = "<?php\n\n// Generated for Savv Framework - " . date('Y-m-d H:i:s') . "\nreturn " . $exportedWithDoubleQuotes . ";";
        
        $configPath = ROOT_PATH . '/configs/posts.php';
        if (file_put_contents($configPath, $content)) {
            return "Successfully synced " . count($manifest) . " posts to config with double quotes.";
        } else {
            return "Failed to write posts config.";
        }
    }



    /** 
     * Add the metadata of a post markdown file to the posts.php config array manifest.
     * Preserves absolute system file paths while protecting slugs from nested collisions.
     */
    public static function addMetaToPostArrayConfig(string $file, array $manifest = []): array {
        $data = self::splitPostMdData($file); 
        $meta = $data['metadata'];
        
        // Get file system times
        $fileCreated = date("Y-m-d H:i:s", filectime($file));
        $fileUpdated = date("Y-m-d H:i:s", filemtime($file));

        // 1. Calculate relative folder structure to safely isolate slugs and categories
        $baseDir = realpath(post_path());
        $realFile = realpath($file);
        $relativeDir = '';
        
        if ($baseDir && $realFile && str_starts_with($realFile, $baseDir)) {
            $subPath = ltrim(substr($realFile, strlen($baseDir)), DIRECTORY_SEPARATOR);
            $dirPart = dirname($subPath);
            $relativeDir = ($dirPart === '.') ? '' : $dirPart . '/';
        }

        // 2. Generate unique slug combining relative folder depth if metadata slug is absent
        if (isset($meta['slug'])) {
            $slugFromMeta = ltrim($meta['slug'], '/');
            $slug = (empty($relativeDir)) ? $slugFromMeta : $relativeDir . $slugFromMeta;
        } else {
            $filenamePart = strtolower(str_replace(' ', '-', basename($file, '.md')));
            $slug = strtolower(str_replace('\\', '/', $relativeDir)) . $filenamePart;
        }

        $manifest[$slug] = [
            'slug'           => $slug,
            'title'          => $meta['title'] ?? basename($file, '.md'),
            'path'           => $file, // Kept as the exact, raw, absolute system path
            'created_at'     => isset($meta['date']) ? $meta['date'] . " 00:00:00" : $fileCreated,
            'updated_at'     => $fileUpdated,
            'author'         => $meta['author'] ?? 'Admin',
            'category'       => $meta['category'] ?? (empty($relativeDir) ? 'General' : ucwords(str_replace('/', ' > ', rtrim($relativeDir, '/')))),
            'tags'           => $meta['tags'] ?? "",
            'excerpt'        => $meta['excerpt'] ?? "",
            'keyphrase'      => $meta['keyphrase'] ?? "",
            'featured_image' => $meta['featured_image'] ?? asset('/images/logos/logo.png'),
        ];

        return $manifest;
    }



    /** 
     * Generate the post cache html files inside /storage/framework/posts
    */
    public static function cachePost(string $slug): string {
        $cachePath = ROOT_PATH . "/storage/framework/posts";
        if (!is_dir($cachePath)) mkdir($cachePath, 0777, true);

        $postData = self::getPostData($slug);
        if (empty($postData)) abort(404, 'Post not found');

        $metadata = $postData['metadata'];
        $content = $postData['content'];

        $previousRequestUri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/' . $slug;

        ob_start();
        require page_path('post-detail.php');
        $html = ob_get_clean();

        if ($previousRequestUri === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $previousRequestUri;
        }

        $filename = $cachePath . DIRECTORY_SEPARATOR . $slug . '.html';
        $dataToPut = "<!-- SavvBlog Cache: " . date('Y-m-d H:i:s') . " -->\n" . $html;

        if (file_put_contents($filename, $dataToPut)){
            return "Cache created successfully!";
        }

        return "Error, post not cached"; 
    }

    /** 
     * 
    */
    public static function cacheAllPosts(): string {
        $allPostConfig = config('posts');
        if (empty($allPostConfig)) {
            return "No posts found to cache.";
        }

        $results = [];
        foreach ($allPostConfig as $slug => $post) {
            $result = self::cachePost($slug);
            $results[] = "Caching '{$slug}': " . $result . "\n";
        }

        return implode("\n", $results);
    }
}
