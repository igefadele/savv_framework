<?php 
namespace Savo\Utils;

/**
 * Encapsulates an HTTP response payload, status code, and headers.
 *
 * Response instances can be returned from routes and controllers, then sent by
 * the router once the middleware pipeline completes.
 */
class Response
{
    /**
     * Raw response body content.
     *
     * @var string
     */
    protected string $content;

    /**
     * HTTP status code for the response.
     *
     * @var int
     */
    protected int $status;

    /**
     * Headers to send with the response.
     *
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * Create a new response instance.
     *
     * @param string $content Response body content.
     * @param int $status HTTP status code.
     * @param array<string, string> $headers Response headers keyed by header name.
     */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    /**
     * Set the HTTP status code.
     *
     * @param int $code HTTP status code to send.
     * @return self The current response instance for chaining.
     */
    public function setStatus(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    /**
     * Add or replace a response header.
     *
     * @param string $key Header name.
     * @param string $value Header value.
     * @return self The current response instance for chaining.
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Convert the response payload to JSON and mark the response as JSON.
     *
     * @param array<string, mixed> $data Data to encode as JSON.
     * @param int $status HTTP status code for the JSON response.
     * @return self The current response instance for chaining.
     */
    public function json(array $data, int $status = 200): self
    {
        $this->content = json_encode($data);
        $this->setStatus($status);
        $this->header('Content-Type', 'application/json');
        return $this;
    }

    /**
     * Configure the response as an HTTP redirect.
     *
     * @param string $url Destination URL.
     * @param int $status Redirect status code, usually `302` or `301`.
     * @return self The current response instance for chaining.
     */
    public function redirect(string $url, int $status = 302): self
    {
        $this->setStatus($status);
        $this->header('Location', $url);
        return $this;
    }

    /**
     * Send the status code, headers, and content to the client.
     *
     * @return void
     */
    public function send(): void
    {
        // 1. Send Status Code
        http_response_code($this->status);

        // 2. Send Headers
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        // 3. Output Content
        echo $this->content;
    }

    /**
     * Render a PHP view file into the response content buffer.
     *
     * The supplied data array is extracted into the view scope before the file
     * is required, making each key available as a local variable in the view.
     *
     * @param string $viewPath View path relative to the root `views` directory, without `.php`.
     * @param array<string, mixed> $data Data exposed to the view.
     * @return self The current response instance for chaining.
     */
    public function view(string $viewPath, array $data = []): self
    {
        extract($data);
        ob_start();
        require ROOT_PATH . "/views/{$viewPath}.php";
        $this->content = ob_get_clean();
        
        return $this;
    }
}
