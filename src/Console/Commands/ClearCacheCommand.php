<?php

namespace Savv\Console\Commands;
use Savv\Services\CacheClearService;

class ClearCacheCommand {

    /**
     * Execute cache clearance options based on action string mapping.
     */
    public function execute(array $args, string $commandName = ''): void {
        // Fallback to commandName parameter if args string array is sliced out from Kernel routing
        $action = !empty($commandName) ? $commandName : ($args[0] ?? 'cache:clear');
        $target = $args[0] ?? null; // For commands like cache:clear:post [slug]

        $baseCachePath = ROOT_PATH . '/storage/framework';

        switch ($action) {
            case 'cache:clear':
                echo "Clearing global framework cache storage...\n";
                CacheClearService::clearDirectory($baseCachePath, false); // Keeps core base folder, clears elements inside
                echo "\e[32mSuccessfully cleared storage/framework/** contents.\e[0m\n";
                break;

            case 'cache:clear:posts':
                echo "Clearing posts cache...\n";
                CacheClearService::clearDirectory($baseCachePath . '/posts', true);
                echo "\e[32mSuccessfully removed posts cache folder hierarchy.\e[0m\n";
                break;

            case 'cache:clear:pages':
                echo "Clearing pages cache...\n";
                CacheClearService::clearDirectory($baseCachePath . '/pages', true);
                echo "\e[32mSuccessfully removed pages cache folder hierarchy.\e[0m\n";
                break;

            case 'cache:clear:routes':
                echo "Removing compiled routes layout map...\n";
                CacheClearService::deleteFile($baseCachePath . '/routes.php');
                break;

            case 'cache:clear:post':
                if (!$target) {
                    echo "\e[31mError: Provide a post file name/slug to clear.\e[0m\n";
                    return;
                }
                // Strip out trailing extensions if provided by accident
                $slug = str_replace('.html', '', $target);
                CacheClearService::deleteFile($baseCachePath . '/posts/' . $slug . '.html');
                break;

            case 'cache:clear:page':
                if (!$target) {
                    echo "\e[31mError: Provide a page file name/slug to clear.\e[0m\n";
                    return;
                }
                $slug = str_replace('.html', '', $target);
                CacheClearService::deleteFile($baseCachePath . '/pages/' . $slug . '.html');
                break;

            case 'cache:clear:route':
                if (!$target) {
                    echo "\e[31mError: Provide a route URI/slug to remove.\e[0m\n";
                    return;
                }
                CacheClearService::removeRouteFromCache($baseCachePath . '/routes.php', $target);
                break;

            default:
                echo "\e[31mUnknown cache management target option.\e[0m\n";
                break;
        }
    }
}
