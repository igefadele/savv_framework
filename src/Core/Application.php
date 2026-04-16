<?php
namespace Savo\Core;

use Savo\Utils\Router;
use Savo\Utils\Request;

class Application {
    public static function bootstrap($rootPath) {
        if (!defined('ROOT_PATH')) define('ROOT_PATH', $rootPath);
        
        // Load .env
        if (file_exists(ROOT_PATH . '/.env')) {
            \Dotenv\Dotenv::createImmutable(ROOT_PATH)->load();
        }

        return new self();
    }

    public function run() {
        // Load user routes from their project directory
        $this->loadRoutes();
        $this->registerRedirections();

        $request = Request::capture();
        $handled = Router::getInstance()->dispatch($request);

        if (!$handled) {
            $this->handleFallback($handled);
        }
    }

    protected function handleFallback($handled) {
        if (!$handled && file_exists(__DIR__ . '/wp-blog-header.php')) {
            require __DIR__ . '/wp-blog-header.php';
        } elseif (!$handled) {
            http_response_code(404);
            echo "404 - Page Not Found";
        }
    }

    protected function registerRedirections() 
    {
        // Use your existing config utility to pull the array
        $redirects = config('redirections') ?? [];
        $router = Router::getInstance();

        foreach ($redirects as $slug => $target) {
            $router->get($slug, function() use ($target) {
                // Handle both string URLs and detailed arrays
                $url = is_array($target) ? $target['url'] : $target;
                $status = is_array($target) ? ($target['status'] ?? 302) : 302;

                return response()->redirect($url, $status);
            });
        }
    }
    
    protected function loadRoutes() {
        if (file_exists(ROOT_PATH . '/routes/api.php')) require ROOT_PATH . '/routes/api.php';
        if (file_exists(ROOT_PATH . '/routes/web.php')) require ROOT_PATH . '/routes/web.php';
    }
}