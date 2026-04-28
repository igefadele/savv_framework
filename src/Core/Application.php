<?php
namespace Savv\Core;

use Savv\Utils\Router;
use Savv\Utils\Request;

class Application {
    protected $redis = null;

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
        // 1. Persist paths so the CLI knows where the app lives
        $this->persistEnvironmentPaths();

        // 2. Ensure the CLI binary is available (as discussed)
        $this->ensureCliBinaryExists();

        // 3. Configure Database (if applicable)
        $this->configureDatabase(); 

        // Bus Init
        $this->initBusAndObserver();

        $this->loadRouter(); 
    }

    protected function loadRouter() {
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

    protected function initBusAndObserver() {
        (new \Savv\Providers\BusServiceProvider())->boot();

        // Observers Init
        $observers = require config('observers');
        foreach ($observers as $model => $observer) {
            (new $observer())->observe();
        }
    }

    protected function configureDatabase() {
        $dbConfig = config('database');
        if ($dbConfig) {
            \Savv\Utils\Db\SavvDb::getInstance($dbConfig);
        } else {
            // If no DB config, we can still run the app but models won't work
            // I could have thrown an exception here, but I want to allow for use cases where users 
            // just want to use the routing and templating features without a database.
            // throw new \Exception("Database configuration not found. Please provide a valid config/database.php file.");
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

    /**
     * Get a Redis instance, prioritizing the native C extension 
     * and falling back to the Predis PHP library.
     * If redis config is not provided, this will return null, allowing the app to function without Redis.
     */ 
    public function getRedis() 
    {
        if ($this->redis !== null) {
            return $this->redis;
        }

        $config = config('database.redis');
        
        // If no config is provided, we treat Redis as "disabled"
        if (!$config) {
            return null; 
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $pass = $config['password'] ?? null;

        // Try Native Extension
        if (class_exists('\Redis')) {
            $this->redis = new \Redis();
            @$this->redis->pconnect($host, $port);
            if ($pass) @$this->redis->auth($pass);
            return $this->redis;
        }

        // Fallback to Predis
        if (class_exists('\Predis\Client')) {
            $options = $pass ? ['parameters' => ['password' => $pass]] : [];
            $this->redis = new \Predis\Client([
                'scheme' => 'tcp',
                'host'   => $host,
                'port'   => $port,
            ], $options);
            return $this->redis;
        }

        return null;
    }


    protected function persistEnvironmentPaths() {
        $data = [
            'ROOT_PATH'   => ROOT_PATH,
            'PUBLIC_PATH' => PUBLIC_PATH,
        ];
        
        // Save to a non-public framework folder
        $pathFile = __DIR__ . '/../.paths.json';
        file_put_contents($pathFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function ensureCliBinaryExists() {
        $stubPath = PUBLIC_PATH . '/savv';
        
        if (!file_exists($stubPath)) {
            $content = "#!/usr/bin/env php\n<?php\n";
            $content .= "require '" . __DIR__ . "/../framework/bin/savv';";
            
            file_put_contents($stubPath, $content);
            chmod($stubPath, 0755);
        }
    }
}