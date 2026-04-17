<?php 
use Savv\Utils\{Request, Response, Config, Validator, Router, Log};

if (!function_exists('request')) {
    /**
     * Resolve the shared request instance or fetch a single input value from it.
     *
     * When no key is provided, the cached {@see Request} instance is returned.
     * When a key is provided, the helper proxies to {@see Request::input()}.
     *
     * @param string|null $key Input key to retrieve. Pass null to get the request object.
     * @param mixed $default Fallback value used when the key does not exist.
     * @return \Savv\Utils\Request|mixed Request instance or resolved input value.
     */
    function request($key = null, $default = null) {
        static $instance = null;
        if ($instance === null) {
            $instance = Request::capture();
        }

        if ($key === null) return $instance;
        return $instance->input($key, $default);
    }
}


if (!function_exists('response')) {
    /**
     * Create a new response instance for HTML, JSON, redirects, or view rendering.
     *
     * @param string $content Initial response body content.
     * @param int $status HTTP status code for the response.
     * @return \Savv\Utils\Response New response instance.
     */
    function response($content = '', $status = 200) {
        return new Response($content, $status);
    }
}

if (!function_exists('config')) {
    /**
     * Retrieve a configuration value using dot notation.
     *
     * @param string $key Dot-notated configuration key.
     * @param mixed $default Fallback value returned when the key is missing.
     * @return mixed The resolved configuration value or the provided default.
     */
    function config(string $key, $default = null) {
        return Config::get($key, $default);
    }
}

/**
 * Build an absolute path to a file within the root `views` directory.
 *
 * @param string $path Optional relative path inside the views directory.
 * @return string Absolute filesystem path to the requested view location.
 */
function view_path($path = '') {
    return ROOT_PATH . '/views' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
}

/**
 * Validate an input array and immediately terminate with a JSON error payload on failure.
 *
 * The returned array is reduced to only the keys defined in the rules array, which
 * makes it convenient for request sanitization after validation succeeds.
 *
 * @param array<string, mixed> $data Input data to validate.
 * @param array<string, string> $rules Validation rules keyed by field name.
 * @return array<string, mixed> Validated data limited to keys declared in the rules.
 */
if (!function_exists('validate')) {
    function validate(array $data, array $rules): array {
        if (!Validator::validate($data, $rules)) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "errors" => Validator::getErrors()
            ]);
            exit;
        }

        // Return only the keys defined in the rules (similar to Laravel's $request->validated())
        return array_intersect_key($data, $rules);
    }
}

if (!function_exists('route')) {
    /**
     * Generate a URL for a named route.
     *
     * @param string $name Registered route name.
     * @param array<string, scalar> $params Route placeholder replacements.
     * @return string Generated relative URL, or `#` when the route name is unknown.
     */
    function route(string $name, array $params = []) {
        return Router::getInstance()->route($name, $params);
    }
}

/**
 * Resolve the shared router instance for route registration or URL generation.
 *
 * @return \Savv\Utils\Router The singleton router instance.
 */
if (!function_exists('router')) {
    function router() {
        return Router::getInstance();
    }
}

if (!function_exists('logger')) {
    /**
     * Write an informational log entry or return a log utility instance.
     *
     * Passing a message writes an `info` log immediately. Passing no message returns
     * a log instance so other methods such as `error()` or `debug()` can be called.
     *
     * @param string|null $message Message to write as an info log entry.
     * @param array<string, mixed> $context Structured log context.
     * @return \Savv\Utils\Log|void Log instance when no message is supplied; otherwise nothing.
     */
    function logger($message = null, array $context = []) {
        if (is_null($message)) {
            return new Log;
        }
        
        Log::info($message, $context);
    }
}


/**
 * Injects PWA meta tags and manifest link into the <head>.
 */
function savv_pwa_head() {
    $config = config('pwa');
    echo '<link rel="manifest" href="/manifest.json">' . PHP_EOL;
    echo '<meta name="theme-color" content="' . ($config['theme_color'] ?? '#000000') . '">' . PHP_EOL;
    echo '<meta name="mobile-web-app-capable" content="yes">' . PHP_EOL;
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . PHP_EOL;
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . PHP_EOL;
    echo '<link rel="apple-touch-icon" href="' . ($config['icons'][0]['src'] ?? '') . '">' . PHP_EOL;
}

/**
 * Injects the Service Worker registration script.
 */
function savv_pwa_footer() {
    echo '<script>
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", () => {
                navigator.serviceWorker.register("/sw.js", { scope: "/" })
                    .then(reg => console.log("Service Worker Registered"))
                    .catch(err => console.log("Service Worker Registration Failed", err));
            });

            navigator.serviceWorker.addEventListener("controllerchange", () => {
                console.log("New version available, reloading...");
                window.location.reload();
            });
        }
    </script>' . PHP_EOL;
}

/**
 * Injects the Service Worker registration assets and script at once in the head.
 */
function savv_pwa_init() {
    $config = config('pwa');
    $theme = $config['theme_color'] ?? '#000000';
    
    // 1. Meta Tags for Head
    $html = '' . PHP_EOL;
    $html .= '<link rel="manifest" href="/manifest.json">' . PHP_EOL;
    $html .= '<meta name="theme-color" content="'.$theme.'">' . PHP_EOL;
    $html .= '<meta name="mobile-web-app-capable" content="yes">' . PHP_EOL;
    $html .= '<meta name="apple-mobile-web-app-capable" content="yes">' . PHP_EOL;
    $html .= '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . PHP_EOL;
    $html .= '<link rel="apple-touch-icon" href="' . ($config['icons'][0]['src'] ?? '') . '">' . PHP_EOL;

    // 2. Registration Script
    $html .= '<script>
    if ("serviceWorker" in navigator) {
        window.addEventListener("load", () => {
            navigator.serviceWorker.register("/sw.js", {
                    scope: "/"
                })
                .then(reg => console.log("' . $config['short_name'] . ' Service Worker Registered"))
                .catch(err => console.log("Service Worker Registration Failed", err));
        });

        navigator.serviceWorker.addEventListener("controllerchange", () => {
            console.log("New version available, reloading...");
            window.location.reload();
        });
    }
    </script>' . PHP_EOL;

    echo $html;
}