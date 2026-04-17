<?php
namespace Savv\Utils;

/**
 * Represents the current HTTP request using PHP superglobals as the data source.
 *
 * The request object provides small convenience methods for accessing GET, POST,
 * files, request metadata, and common request checks in a framework-friendly way.
 */
class Request
{
    /**
     * Query string values from `$_GET`.
     *
     * @var array<string, mixed>
     */
    protected array $queryParams;

    /**
     * Form and body values from `$_POST`.
     *
     * @var array<string, mixed>
     */
    protected array $postData;

    /**
     * Server metadata from `$_SERVER`.
     *
     * @var array<string, mixed>
     */
    protected array $serverData;

    /**
     * Uploaded file information from `$_FILES`.
     *
     * @var array<string, mixed>
     */
    protected array $files;

    /**
     * Create a request snapshot from the current PHP superglobals.
     *
     * @return void
     */
    public function __construct()
    {
        $this->queryParams = $_GET;
        $this->postData = $_POST;
        $this->serverData = $_SERVER;
        $this->files = $_FILES;
    }

    /**
     * Create a new request instance for the current HTTP lifecycle.
     *
     * @return self A fresh request object populated from superglobals.
     */
    public static function capture(): self
    {
        return new self();
    }

    /**
     * Get a single input value with POST data taking precedence over query data.
     *
     * @param string $key Input key to resolve.
     * @param mixed $default Value returned when the key does not exist.
     * @return mixed The resolved input value or the provided default.
     */
    public function input(string $key, $default = null)
    {
        return $this->postData[$key] ?? $this->queryParams[$key] ?? $default;
    }

    /**
     * Get all request input values with POST taking precedence over GET.
     *
     * @return array<string, mixed> Combined request input payload.
     */
    public function all(): array
    {
        return array_merge($this->queryParams, $this->postData);
    }

    /**
     * Get all POST data or a specific POST field.
     *
     * @param string|null $key POST field to retrieve. Pass null to return the full POST payload.
     * @param mixed $default Value returned when the requested POST field is missing.
     * @return mixed Either the complete POST array or a single resolved value.
     */
    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->postData;
        }

        return $this->postData[$key] ?? $default;
    }

    /**
     * Retrieve only the specified input keys from the request.
     *
     * Missing keys are included with a null value to preserve the requested shape.
     *
     * @param array<int, string> $keys Input keys to extract.
     * @return array<string, mixed> Associative array containing only the requested keys.
     */
    public function only(array $keys): array
    {
        $data = [];

        foreach ($keys as $key) {
            $data[$key] = $this->input($key);
        }

        return $data;
    }

    /**
     * Retrieve all request input except the specified keys.
     *
     * @param array<int, string> $keys Input keys to omit.
     * @return array<string, mixed> Request input array without the excluded keys.
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Determine if an input field has a non-empty value.
     *
     * The values `'0'` and `0` are treated as filled.
     *
     * @param string $key Input key to inspect.
     * @return bool True when the field contains a meaningful value.
     */
    public function filled(string $key): bool
    {
        $value = $this->input($key);

        return !empty($value) || $value === '0' || $value === 0;
    }

    /**
     * Get all query parameters or a specific query string value.
     *
     * @param string|null $key Query parameter key to retrieve. Pass null to return the full query array.
     * @param mixed $default Value returned when the requested query key is missing.
     * @return mixed Either the complete query parameter array or a single resolved value.
     */
    public function query(string $key = null, $default = null)
    {
        if (null === $key) return $this->queryParams;
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Get the request HTTP method.
     *
     * @return string The request method such as `GET`, `POST`, or `PUT`.
     */
    public function method(): string
    {
        return $this->serverData['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get the normalized request path without the query string.
     *
     * @return string Path portion of the request URI.
     */
    public function path(): string
    {
        $uri = $this->serverData['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH);
    }
    
    /**
     * Determine whether the request was made via AJAX.
     *
     * This checks the `X-Requested-With` header for the conventional
     * `XMLHttpRequest` marker.
     *
     * @return bool True when the request looks like an AJAX request.
     */
    public function ajax(): bool
    {
        return isset($this->serverData['HTTP_X_REQUESTED_WITH']) && 
               strtolower($this->serverData['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
