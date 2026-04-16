<?php
namespace Savo\Utils;

use Savo\Providers\AppProvider;
use Savo\Utils\{Response};

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
        
        // 2. Combine Name Prefixes
        $namePrefix = $this->getCurrentNamePrefix();

        // 3. Convert to Regex (same as before)
        $regexUri = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $fullUri);
        $regexUri = "#^" . ($regexUri === '' ? '' : $regexUri) . "$#";

        $this->routes[] = [
            'method'   => $method,
            'uri'      => $regexUri,
            'callback' => $callback,
            'name'     => $namePrefix, // Store the accumulated name prefix
            'original_uri' => $fullUri,
            'middleware' => $this->getCurrentMiddleware() // Store middleware
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
        // Trim slashes to ensure 'api/contact' matches 'api/contact/'
        $path = trim($request->path(), '/');

        foreach ($this->routes as $route) {
            // Check method and regex match
            if ($route['method'] === $method && preg_match($route['uri'], $path, $matches)) {
    
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $finalCallback = $route['callback'];
                $middlewareStack = $route['middleware'];

                // This is the core "Next" function
                $destination = function ($request) use ($finalCallback, $params) {
                    if (is_callable($finalCallback)) {
                        return call_user_func_array($finalCallback, $params);
                    }
                    
                    [$controller, $action] = $finalCallback;
                    $instance = new $controller();
                    return call_user_func_array([$instance, $action], $params);
                };

                // Wrap the destination in middleware (working backwards)
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

                if ($result instanceof Response) {
                    $result->send();
                }

                return $result ?? true;
            }
        }

        // No route matched. We return false so index.php can handle the WP fallback.
        return false;
    }
}
