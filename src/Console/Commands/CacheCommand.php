<?php 

namespace Savv\Console\Commands;

use Savv\Services\{CacheService, CacheRouteService, CachePageService, CachePostService};

class CacheCommand 
{
    /**
     * Execute caching/syncing options based on action string mapping.
     */
    public function execute(array $args, string $commandName = ''): void {
        // Fallback to commandName parameter if args string array is sliced out from Kernel routing
        $action = !empty($commandName) ? $commandName : ($args[0] ?? 'optimize');
        $target = $args[0] ?? null; // For commands like cache:post [slug]

        switch ($action) {
            case 'optimize':
                CacheService::optimize();
                break;

            case 'cache:posts':
                echo "Caching posts...\n";
                echo CachePostService::cacheAllPosts();
                echo "\e[32mSuccessfully cached posts\e[0m\n";
                break;

            case 'cache:pages':
                echo "Caching pages...\n";
                echo CachePageService::cacheAllPages();
                echo "\e[32mSuccessfully cached pages.\e[0m\n";
                break;

            case 'cache:routes':
            case 'cache route':
                echo "Caching routes...\n";
                CacheRouteService::cacheAllRoutes();
                echo "\e[32mSuccessfully cached routes.\e[0m\n";
                break;

            case 'cache:post':
                if (!$target) {
                    echo "\e[31mError: Provide a post file slug to cache.\e[0m\n";
                    return;
                }
                echo CachePostService::cachePost($target);
                echo "\e[32mSuccessfully cached post.\e[0m\n";
                break;

            case 'cache:page':
                if (!$target) {
                    echo "\e[31mError: Provide a page file slug to cache.\e[0m\n";
                    return;
                }
                echo CachePageService::cachePage($target);
                echo "\e[32mSuccessfully cached page.\e[0m\n";
                break;

            case 'sync:posts':
                echo "Syncing all posts...\n";
                echo CachePostService::syncAllPosts();
                echo "Posts synced successfully...\n";
                break;

            case 'sync:post':
                if (!$target) {
                    echo "\e[31mError: Provide a post file slug to sync.\e[0m\n";
                    return;
                }
                echo CachePostService::syncPost($target);
                echo "\e[32mSuccessfully synced post.\e[0m\n";
                break;

            default:
                echo "\e[31mUnknown cache management target option.\e[0m\n";
                break;
        }
    }
}