# Savo

Savo is a lightweight PHP framework for brand sites, marketing sites, studio websites, landing pages, and other presentation-first products that still need modern web capabilities.

It is intentionally small. The goal is to keep the developer experience familiar without dragging in a large framework, deep abstractions, or layers of boilerplate just to render pages, handle forms, or ship a polished public website.

Savo leans on plain modern PHP, file-based page routing, small utility classes, and a direct coding style that is easy to read, easy to trace, and easy to extend.

## Why Savo

- Lightweight core with a very small dependency surface.
- File-based routing for page views so common website pages do not need controller ceremony.
- Custom route support for API endpoints and advanced web actions.
- Familiar helper-driven syntax inspired by modern PHP frameworks.
- Built-in request, response, config, validation, routing, and logging utilities.
- SPA-like page transitions using Swup for faster-feeling navigation.
- PWA-ready setup with `manifest.json`, `sw.js`, and offline assets.
- Easy-to-understand structure suited to freelancers, agencies, studios, and product teams shipping brochure and marketing sites.

## What Savo Is Best For

- Agency and studio websites
- Product landing pages
- Marketing websites
- Company profile sites
- Campaign microsites
- Public brand platforms with a few dynamic endpoints

If your project needs complex ORM workflows, enterprise module systems, or a very large plugin ecosystem, a full-stack framework may be a better fit. Savo is optimized for clarity, speed, and low overhead.

## Project Structure

```text
app/        Your application code: controllers, constants, middleware, services, utilities
configs/    Plain PHP config arrays accessed through config('file.key')
public/     Public document root, entry file, assets, manifest, service worker
routes/     Explicit route definitions for API and custom web routes
savo/       Core framework files and helpers
storage/    Runtime files such as logs
views/      PHP views, layouts, and partials
vendor/     Composer dependencies you add to the project
```

### Directory Notes

- `public/` should be the web server document root.
- On shared hosting, keep everything above `public/` outside `public_html` where possible.
- `savo/` contains the framework core and is generally expected to stay stable.
- `app/` is your playground. Add your custom app logic there freely.
- `views/` contains page views, layouts, partials, and hosted docs pages.

## Installation

### Requirements

- PHP CLI / PHP runtime available on the server
- Composer

### Setup

```bash
composer install
composer dump-autoload
```

Create a `.env` file and provide the values required by your configs, especially for mail if you are using the contact flow.

Point your local or production server document root to:

```text
public/
```

## Bootstrap Flow

The framework boots through [public/index.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/public/index.php:1):

1. Defines `ROOT_PATH`
2. Loads Composer autoloading
3. Loads environment variables with `phpdotenv`
4. Registers routes from `routes/api.php` and `routes/web.php`
5. Captures the request and dispatches it through the router
6. Falls back to WordPress when a route is not handled and `wp-blog-header.php` exists

## Routing

Savo supports two routing styles.

### 1. File-Based Page Routing

The default web routing flow is intentionally simple. If a request comes in for `/about`, Savo looks for:

```text
views/about.php
```

That means most website pages can be created by dropping a file into `views/` without first registering a controller or route.

Current example from [routes/web.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/routes/web.php:1):

```php
router()->get('{slug}', function ($slug) {
    $viewPath = ROOT_PATH . '/views/' . $slug . '.php';

    if (file_exists($viewPath)) {
        require $viewPath;
        return true;
    }

    return false;
});
```

### 2. Explicit Routes

For custom flows such as forms, JSON APIs, redirects, or middleware-protected endpoints, use `routes/web.php` or `routes/api.php`.

```php
use App\Controllers\ContactController;

router()->group(['prefix' => 'api', 'name' => 'api.'], function ($router) {
    $router->post('contact-submit', [ContactController::class, 'submit'])
        ->name('submit.contact');
});
```

### Named Routes

```php
$url = route('api.submit.contact');
```

### Route Parameters

```php
router()->get('blog/{slug}', function ($slug) {
    return response("Viewing {$slug}");
})->name('blog.show');

$url = route('blog.show', ['slug' => 'hello-world']);
```

## Middleware

Savo supports route middleware and middleware groups.

### Register Middleware Aliases

Define aliases in [app/Constants/MiddlewareConstants.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/app/Constants/MiddlewareConstants.php:1):

```php
namespace App\Constants;

class MiddlewareConstants
{
    public static $aliases = [
        'auth' => \App\Middleware\Authenticate::class,
    ];
}
```

### Apply to a Group

```php
router()->group(['prefix' => 'api', 'middleware' => 'auth'], function ($router) {
    $router->get('user-data', function () {
        return response()->json(['data' => 'secret info']);
    });
});
```

### Apply to a Single Route

```php
router()->post('contact', [ContactController::class, 'submit'])
    ->middleware('auth')
    ->name('submit.contact');
```

### Middleware Class Example

```php
namespace App\Middleware;

use Savo\Utils\Request;

class Authenticate
{
    public function handle(Request $request, callable $next)
    {
        if (!isset($_SESSION['user_id'])) {
            return response()->redirect('/login');
        }

        return $next($request);
    }
}
```

## Helpers

Savo ships with a small set of global helpers in [savo/Helpers/helpers.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/savo/Helpers/helpers.php:1).

### `request()`

Returns the request instance or a single input value.

```php
$request = request();
$name = request('name', 'Guest');
$payload = request()->only(['name', 'email', 'message']);
```

### `response()`

Creates a response object.

```php
return response('<h1>Hello World</h1>', 200)
    ->header('X-Powered-By', 'Savo');

return response()->json([
    'status' => 'success',
    'data' => ['user_id' => 42],
], 201);

return response()->redirect('/contact');
```

### `config()`

Reads config values from `configs/*.php`.

```php
$smtpHost = config('mail.smtp.host');
```

### `validate()`

Validates an array and automatically returns a `422` JSON error response on failure.

```php
$validated = validate(request()->all(), [
    'name' => 'required',
    'email' => 'required|email',
    'message' => 'required|min:10',
]);
```

### `route()`

Generates a URL from a named route.

```php
$submitUrl = route('api.submit.contact');
```

### `router()`

Returns the shared router instance used for route registration.

```php
router()->get('/', function () {
    require ROOT_PATH . '/views/index.php';
});
```

### `logger()`

Writes info logs or gives access to the log utility.

```php
logger('Contact form received', ['email' => request('email')]);
logger()->error('Mail failed', ['reason' => 'SMTP timeout']);
```

## Core Utilities

### Request

[savo/Utils/Request.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/savo/Utils/Request.php:1) provides:

- `input()`
- `all()`
- `post()`
- `only()`
- `except()`
- `filled()`
- `query()`
- `method()`
- `path()`
- `ajax()`

### Response

[savo/Utils/Response.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/savo/Utils/Response.php:1) provides:

- `setStatus()`
- `header()`
- `json()`
- `redirect()`
- `send()`
- `view()`

### Config

[savo/Utils/Config.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/savo/Utils/Config.php:1) loads plain PHP arrays from `configs/` and caches them per request.

### Validator

[savo/Utils/Validator.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/savo/Utils/Validator.php:1) supports:

- `required`
- `email`
- `min:n`
- `max:n`
- `numeric`
- `url`

### Log

[savo/Utils/Log.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/savo/Utils/Log.php:1) writes daily log files to:

```text
storage/logs/YYYY-MM-DD.log
```

## Configs

Savo configs are plain PHP arrays stored in `configs/`.

Example from [configs/mail.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/configs/mail.php:1):

```php
return [
    'smtp' => [
        'host' => $_ENV['SMTP_HOST'] ?? null,
        'port' => $_ENV['SMTP_PORT'] ?? null,
        'user' => $_ENV['SMTP_USER'] ?? null,
        'password' => $_ENV['SMTP_PASSWORD'] ?? null,
        'from' => $_ENV['SMTP_FROM'] ?? null,
        'to' => $_ENV['SMTP_TO'] ?? null,
    ],
];
```

Access them with:

```php
config('mail.smtp.from');
```

## Example Contact API Flow

The current starter project includes a contact submission endpoint powered by [app/Controllers/ContactController.php](/Users/MrFIA/Documents/WORKSPACE/WORDPRESS/savadub/app/Controllers/ContactController.php:1).

What it does:

- Accepts `POST /api/contact-submit`
- Pulls only the expected fields from the request
- Uses a honeypot field to silently ignore obvious bots
- Validates required inputs
- Sends the message through PHPMailer using SMTP config
- Logs failures to `storage/logs`
- Returns JSON responses for frontend handling

## Views and Layouts

Views are standard PHP files inside `views/`.

Most pages in this starter project follow this pattern:

```php
$pageTitle = 'About — Savo';
$pageDescription = '...';
$extraCSS = '<link rel="stylesheet" href="/assets/css/about.css">';

ob_start();
?>
    <section>...</section>
<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/index.php';
```

This keeps templating simple and avoids introducing another rendering layer.

## Frontend Enhancements

The starter includes progressive frontend enhancements for fast-feeling public sites:

- Swup page transitions for SPA-like navigation
- AOS for content reveal animations
- Service worker registration
- Web manifest support
- Offline fallback assets

These are starter choices, not hard framework requirements. You can keep, replace, or remove them depending on the project.

## Autoloading Your Own Code

If you add:

- namespaced classes under `app/`, register them in Composer `psr-4` when needed
- non-class PHP files, add them to the Composer `autoload.files` array

Then run:

```bash
composer dump-autoload
```

## Deployment Notes

- Point your server to `public/`
- Keep framework and app files above the public web root where possible
- Make sure `storage/logs` is writable
- Provide required `.env` values in production
- Cache or optimize server-level delivery as needed for your hosting environment

## Philosophy

Savo is for developers who want:

- less setup
- less abstraction
- less boilerplate
- more readable PHP
- more control over what ships
- a framework that stays out of the way on content-heavy public websites

If that sounds like your kind of workflow, Savo is doing its job.
