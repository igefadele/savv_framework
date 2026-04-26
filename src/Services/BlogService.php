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

        foreach ($paragraphs as $index => $para) {
            $output_content .= $para . '</p>';
            $current_pos = $index + 1;
            $injected_count = 0;
            

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


}