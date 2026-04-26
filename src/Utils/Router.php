<?php
namespace Savv\Utils;

use Savv\Providers\AppProvider;
use Savv\Utils\{Response};
use Savv\Services\BlogService;

/**
 * Registers routes, groups shared route attributes, resolves named URLs,
 * and dispatches the current request through middleware to a callback.
 *
 * The router is implemented as a singleton so route definition files can
 * populate one shared route table during bootstrap.
 */
class Router
{  
    /**
     * Singleton router instance.
     *
     * @var self|null
     */
    protected static ?Router $instance = null;

    /**
     * Registered routes in dispatch order.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $routes = [];

    /**
     * Stack of active route groups used to accumulate prefixes, names, and middleware.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $groupStack = [];

    /**
     * Resolved middleware alias map.
     *
     * @var array<string, class-string>
     */
    protected array $middlewareAliases = [];

    /**
     * Create the router and load middleware aliases from the application provider.
     *
     * The constructor is protected to enforce singleton access through
     * {@see self::getInstance()}.
     *
     * @return void
     */
    protected function __construct()
    {
        $this->middlewareAliases = AppProvider::middlewareAliases();
    }

    /**
     * Get the shared router instance.
     *
     * @return self The singleton router instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Generate a URL for a named route.
     *
     * Route parameters are substituted into placeholders such as `{slug}` in the
     * original route URI before the final path is returned.
     *
     * @param string $name Fully qualified route name.
     * @param array<string, scalar> $params Placeholder replacements keyed by parameter name.
     * @return string Generated relative URL, or `#` when the route name is unknown.
     */
    public function route(string $name, array $params = []): string
    {
        foreach ($this->routes as $route) {
            if ($route['name'] === $name) {
                $uri = $route['original_uri'];
                foreach ($params as $key => $value) {
                    $uri = str_replace("{{$key}}", $value, $uri);
                }
                return '/' . ltrim($uri, '/');
            }
        }
        return '#';
    }

    /**
     * Load internal package route and also scans the routes directory and loads every PHP file found.
     */
    public function loadRouteFiles(): void
    {   
        // 1. Load Internal Framework Routes (PWA, Manifest, etc.)
        // We use __DIR__ to go from src/Utils/ to src/Helpers/
        $internalRoutes = dirname(__DIR__) . '/Helpers/routes.php';
        if (file_exists($internalRoutes)) {
            require_once $internalRoutes;
        }
        
        // 2. Load User/Project Routes
        $routeDir = ROOT_PATH . '/routes';
        if (is_dir($routeDir)) {
            $files = glob($routeDir . '/*.php');
            foreach ($files as $file) {
                require_once $file;
            }
        }
    }

    public function registerRedirections() 
    {
        $redirects = config('redirections') ?? [];
        $router = Router::getInstance();

        foreach ($redirects as $slug => $target) {
            $router->get($slug, function() use ($target) {
                $url = is_array($target) ? $target['url'] : $target;
                $status = is_array($target) ? ($target['status'] ?? 302) : 302;

                return response()->redirect($url, $status);
            });
        }
    }

    /**
     * Load a pre-compiled array of routes (Used by Cache)
     */
    public function loadRawRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    /**
     * Get all registered routes (Used to generate Cache)
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Resolve the middleware currently inherited from nested groups.
     *
     * @return array<int, string> Middleware aliases or class names applied to the active group stack.
     */
    protected function getCurrentMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array)$group['middleware']);
            }
        }
        return $middleware;
    }

    /**
     * Append middleware to the most recently registered route.
     *
     * @param string|array<int, string> $middleware Middleware alias, class name, or list of both.
     * @return self The router instance for fluent chaining.
     */
    public function middleware($middleware): self
    {
        $lastIndex = array_key_last($this->routes);
        if ($lastIndex !== null) {
            $this->routes[$lastIndex]['middleware'] = array_merge(
                $this->routes[$lastIndex]['middleware'], 
                (array)$middleware
            );
        }
        return $this;
    }

    /**
     * Register a route group with shared attributes such as prefix, name, and middleware.
     *
     * @param array<string, mixed> $attributes Shared group attributes.
     * @param callable $callback Callback that receives the router instance for nested route registration.
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        // Add current attributes to the stack
        $this->groupStack[] = $attributes;

        // Execute the routes defined inside the group
        $callback($this);

        // Remove the last attributes from the stack after the group is done
        array_pop($this->groupStack);
    }

    

    /**
     * Resolve the URI prefix inherited from nested route groups.
     *
     * @return string URI prefix without leading or trailing slashes.
     */
    protected function getCurrentPrefix(): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return trim($prefix, '/');
    }

    /**
     * Resolve the route name prefix inherited from nested route groups.
     *
     * @return string Concatenated route name prefix.
     */
    protected function getCurrentNamePrefix(): string
    {
        $namePrefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['name'])) {
                $namePrefix .= $group['name'];
            }
        }
        return $namePrefix;
    }

    /**
     * Register a route entry in the internal route table.
     *
     * Dynamic URI segments wrapped in braces, such as `{slug}`, are converted to
     * named regular expression groups for dispatch-time parameter extraction.
     *
     * @param string $method HTTP method for the route.
     * @param string $uri Route URI pattern relative to the current group prefix.
     * @param callable|array{0:class-string,1:string} $callback Route action callback or controller action pair.
     * @return self The router instance for fluent chaining.
     */
    protected function addRoute(string $method, string $uri, $callback): self
    {
        // 1. Combine Prefixes
        $prefix = $this->getCurrentPrefix();
        $fullUri = trim($prefix . '/' . trim($uri, '/'), '/');
        
        // Ensure root path is represented as an empty string or single slash 
        // depending on how your Request::path() returns the root.
        $fullUri = $fullUri === '' ? '/' : $fullUri;

        // 2. Combine Name Prefixes
        $namePrefix = $this->getCurrentNamePrefix();

        // 3. Convert to Regex
        // This converts {slug} to named capture groups
        $regexUri = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $fullUri);
        
        $regexUri = "#^" . $regexUri . "$#";

        $method = strtoupper($method);
        
        $this->routes[] = [
            'method'       => $method,
            'uri'          => $regexUri,
            'callback'     => $callback,
            'name'         => $namePrefix,
            'original_uri' => $fullUri,
            'middleware'   => $this->getCurrentMiddleware()
        ];

        return $this;
    }


    /**
     * Append a name to the most recently registered route.
     *
     * Group name prefixes are preserved and the provided segment is concatenated.
     *
     * @param string $name Route name segment.
     * @return self The router instance for fluent chaining.
     */
    public function name(string $name): self
    {
        $lastIndex = array_key_last($this->routes);
        // Append the specific name to the group's name prefix
        if ($lastIndex !== null) {
            $this->routes[$lastIndex]['name'] .= $name;
        }
        return $this;
    }

    /**
     * Register a `GET` route.
     *
     * @param string $uri Route URI pattern.
     * @param callable|array{0:class-string,1:string} $callback Route action callback or controller action pair.
     * @return self The router instance for fluent chaining.
     */
    public function get(string $uri, $callback): self
    {
        return $this->addRoute('GET', $uri, $callback);
    }

    /**
     * Register a `POST` route.
     *
     * @param string $uri Route URI pattern.
     * @param callable|array{0:class-string,1:string} $callback Route action callback or controller action pair.
     * @return self The router instance for fluent chaining.
     */
    public function post(string $uri, $callback): self
    {
        return $this->addRoute('POST', $uri, $callback);
    }

    /**
     * Match the incoming request against the route table and execute the pipeline.
     *
     * When a route matches, middleware is wrapped around the route action from
     * last declared to first declared, then the final result is returned. If a
     * {@see Response} instance is returned, it is automatically sent.
     *
     * @param Request $request Current HTTP request instance.
     * @return mixed Returns the route result, `true` for handled non-response routes, or `false` when no route matches.
     */  
    public function dispatch(Request $request)
    {
        $method = $request->method();
        $controller = new \Savv\Controllers\SystemController();

        // This ensures internal matching works whether there's a leading slash or not
        $path = ltrim($request->path(), '/'); 
        $path = ($path === '') ? '/' : $path;

        // FAST-PASS: Intercept PWA System Assets
        // Bypasses regex loop and dynamic discovery for performance and reliability
        if ($method === 'GET') {
            // Trimming /sw.js or /manifest.json can never result into "" or "/" but sw.js or manifest.json respectively
            if ($path === 'sw.js' || $path === 'manifest.json') {
                
                if ($path === 'sw.js') return $controller->getServiceWorkerFile();
                if ($path === 'manifest.json') return $controller->getManifestFile();
            }

            if (strpos($path, 'savv-assets/') === 0) {
                return $controller->getLocalAsset($path);
            }

            if (strpos($path, 'assets/') === 0) {
                return $controller->serveAsset($path);
            }
        }

        // 1. Attempt to match registered routes (Explicit/Cached)
        foreach ($this->routes as $routeData) {
            if ($routeData['method'] !== $method) {
                continue;
            }

            if (preg_match($routeData['uri'], $path, $matches)) {
                return $this->runRoutePipeline($request, $routeData, $matches);
            }
        }

        // 2. Fallback to Dynamic Discovery for GET requests
        if ($method === 'GET') {
            return $this->resolveDynamicView($path);
        }

        return false;
    }
    
    /**
     * Handles middleware pipeline
     */
    protected function runRoutePipeline(Request $request, array $routeData, array $matches)
    {
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        $middlewareStack = $routeData['middleware'];

        // Define the core destination
        $destination = $this->createRouteDestination($routeData['callback'], $params);

        // Wrap in middleware pipeline
        $pipeline = array_reduce(
            array_reverse($middlewareStack),
            function ($next, $middlewareAlias) {
                return function ($request) use ($next, $middlewareAlias) {
                    $class = $this->middlewareAliases[$middlewareAlias] ?? $middlewareAlias;
                    $instance = new $class();
                    return $instance->handle($request, $next);
                };
            },
            $destination
        );

        $result = $pipeline($request);

        // Handle Response object if returned
        if ($result instanceof Response) {
            $result->send();
        }

        return $result ?? true;
    }
    
    /**
     * The Core "Destination": To handle Cacheable Markers
     */
    protected function createRouteDestination($finalCallback, array $params)
    {
        return function ($request) use ($finalCallback, $params) {
            // Handle Cacheable Array Markers
            if (is_array($finalCallback) && isset($finalCallback['__savv_type'])) {
                extract($params);
                switch ($finalCallback['__savv_type']) {
                    case 'redirect':
                        return response()->redirect($finalCallback['url'], $finalCallback['status']);
                    case 'post':
                        $_GET['post_slug'] = $finalCallback['slug'];
                        $_GET['metadata'] = $finalCallback['metadata'];
                        return BlogService::servePost($finalCallback['slug']);
                    case 'view':
                        return require $finalCallback['path'];
                }
            }

            $toInvoke = null;
        
            if (is_array($finalCallback)) {
                [$controller, $action] = $finalCallback;
                $toInvoke = [new $controller(), $action];
            } elseif (is_callable($finalCallback)) {
                $toInvoke = $finalCallback;
            }

            if ($toInvoke) {
                // Use Reflection to see what the method actually wants
                $reflector = is_array($toInvoke) 
                    ? new \ReflectionMethod($toInvoke[0], $toInvoke[1]) 
                    : new \ReflectionFunction($toInvoke);

                $methodParams = $reflector->getParameters();
                $finalArgs = [];


                foreach ($methodParams as $param) {
                    $type = $param->getType();
                    
                    // If the parameter is type-hinted, inject the appropriate instance
                    if ($type && !$type->isBuiltin()) {
                        $typeName = $type->getName();
                        
                        // Smart Injection based on Type-Hint
                        if ($typeName === 'Savv\Utils\Request') {
                            $finalArgs[] = $request;
                            continue;
                        }
                        if ($typeName === 'Savv\Utils\Session') {
                            $finalArgs[] = $session;
                            continue;
                        }
                    }

                    // Fallback to route parameters by name
                    // Pull from the route params ($id, $slug, etc.)
                    // This matches the variable name in the function to the name in the route e.g {id}, {slug}, etc.
                    $name = $param->getName();
                    $finalArgs[] = $params[$name] ?? null;
                }

                return call_user_func_array($toInvoke, $finalArgs);
            }
        };
    }


    /**
    * Dynamic Discovery Fallback
    * This allows files in views/pages/ to work if no explicit route matches
    */
    protected function resolveDynamicView(string $path)
    {
        $slug = ($path === '/' || $path === '') ? 'index' : ltrim($path, '/');
        $viewPath = page_path("/{$slug}.php");
        
        // 1. Check if it's a standard page (e.g., about.php)
        if (file_exists($viewPath)) {
            require $viewPath;
            return true;
        }

        // 2. Check if the slug exists in our posts configuration
        $posts = config('posts') ?? [];
        if (isset($posts[$slug])) {
            BlogService::servePost($slug);
            return true;
        }

        // 3. Ultimate fallback to check if there's a markdown file for this slug in the posts directory
        $postPath = post_path("/{$slug}.md");
        if (file_exists($postPath)) {
            BlogService::servePost($slug);
            return true;
        }

        return false; // This triggers the handleExternalFallbacks() in Application.php
    }
}