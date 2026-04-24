<?php
namespace Savv\Core;

use Savv\Utils\Router;
use Savv\Utils\Request;

class Application {
    public static function bootstrap($rootPath, $publicPath = null) {
        if (!defined('ROOT_PATH')) define('ROOT_PATH', $rootPath);
        if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', $publicPath ?? $rootPath);

        // Load .env
        if (file_exists(ROOT_PATH . '/.env')) {
            \Dotenv\Dotenv::createImmutable(ROOT_PATH)->load();
        }

        return new self();
    }

    public function run() {
        $cacheFile = ROOT_PATH . '/storage/framework/routes.php';
        $router = Router::getInstance();

        if (file_exists($cacheFile)) {
            $router->loadRawRoutes(require $cacheFile);
        } else {
            // Load user route files and redirections config from their project directory
            $router->loadRouteFiles();
            $router->registerRedirections();
        } 

        $request = Request::capture();
        $handled = Router::getInstance()->dispatch($request);

        if (!$handled) {
            $this->handleExternalFallbacks();
        }
    }

    /**
     * Fallback:
     * Iterates through external CMS installations defined in config.
     */
    protected function handleExternalFallbacks(): void
    {
        $installations = config('installations') ?? [];

        foreach ($installations as $cms => $settings) {
            // Only attempt if the installation is marked active
            if (!empty($settings['active']) && file_exists($settings['path'])) {
                
                // For WordPress specifically, we require the header
                // Note: We 'return' here because the CMS usually takes over the exit process
                require $settings['path'];
                return; 
            }
        }

        // Final 404 if no CMS claimed the request
        $this->abort404();
    }

    /**
     * Standard 404 handler if everything else fails.
     */
    protected function abort404(): void
    {
        http_response_code(404);
        
        // Check if user has a custom 404 view
        $custom404 = ROOT_PATH . '/views/404.php';
        if (file_exists($custom404)) {
            require $custom404;
        } else {
            echo "404 - Page not found!.";
        }
        exit;
    }
}