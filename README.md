# Savo Framework

Savo Framework is a lightweight PHP framework package for building brand websites, marketing websites, studio sites, landing pages, and other public-facing web experiences without the weight of a large full-stack framework.

It is built around plain modern PHP, direct logic, small utilities, and a familiar developer experience. The goal is to give developers the convenience and structure they expect from modern frameworks while keeping the codebase easy to understand, easy to trace, and easy to extend.

Savo Framework is the core package.

If you want to start a new Savo application quickly, use **Savo Starter**, which is the starter skeleton project that already depends on this package and provides the app structure, routes, views, configs, and public entry setup.

## Package Model

Savo is split into two parts:

### 1. Savo Framework

This repository contains the installable framework package.

It provides the core runtime and utilities such as:

- application bootstrapping support
- routing
- request handling
- response helpers
- config loading
- validation
- logging
- helper functions
- other core framework behavior

The framework is installed as a Composer dependency and lives inside `vendor/` in a real application.

### 2. Savo Starter

This is the starter project developers download when they want to create a new Savo app.

It contains the application-level structure, typically things like:

- `app/`
- `configs/`
- `routes/`
- `views/`
- `public/`
- environment setup
- starter assets and example pages

The starter project already includes Savo Framework as a dependency, so developers can begin building immediately.

## Why Savo

- Lightweight core with minimal overhead
- Built for real websites, not bloated application scaffolding
- Plain PHP code with familiar framework-style syntax
- Easy to understand for both experienced developers and newcomers
- File-based page routing support for quick website development
- Custom routing support for APIs and advanced behavior
- Clean helper-driven API for common tasks
- Suitable for starter kits, agencies, studios, and brochure or marketing sites

## Best Use Cases

Savo Framework is especially suitable for:

- brand websites
- marketing sites
- landing pages
- studio or agency websites
- corporate websites
- brochure websites
- websites with a few dynamic endpoints
- public-facing web apps that do not need a heavy framework

If you need a deeply layered enterprise framework with a large built-in ecosystem, Savo may not be the right tool. Savo is optimized for clarity, speed, and low complexity.

## Installation

### Install via Packagist

Install Savo Framework with:

```bash
composer require savadub/savo
```

### Install via GitHub Repository URL

Developers can also install the framework directly from its GitHub repository by adding a VCS repository entry to `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/igefadele/savo_framework"
    }
  ],
  "require": {
    "savadub/savo": "dev-main"
  }
}
```

Then run:

```bash
composer update
```

## Recommended Way to Start a New Project

The recommended way to begin a new Savo project is to use **Savo Starter**, not this package repository directly.

Why:

- the starter already has the correct folder structure
- the starter already depends on Savo Framework
- the starter includes the app-level files developers are expected to edit
- developers can begin building pages and features immediately

Suggested flow:

1. Download or clone `Savo Starter` from `https://github.com/igefadele/savo_starter`
2. Run `composer install`
3. Configure environment values
4. Point the server document root to `public/`
5. Start building inside the starter app structure

## Framework Responsibilities

Savo Framework is responsible for the reusable core logic that powers Savo applications.

That includes:

- core classes under `src/`
- framework helpers
- routing engine
- request and response utilities
- validation utilities
- config access utilities
- logging utilities
- application provider and bootstrapping support

This separation keeps application code out of the framework package and makes the framework easier to maintain, version, and reuse.

## Starter Project Responsibilities

Savo Starter is where developers build their real applications.

That project should hold the project-specific code such as:

- controllers
- middleware
- route files
- configs
- views
- public assets
- layouts and partials
- application constants
- custom services

In other words:

- **Savo Framework** provides the engine
- **Savo Starter** provides the starting app shell

## Typical Architecture

In a real Savo project, the structure now looks more like this:

```text
my-savo-app/
app/
configs/
public/
routes/
views/
vendor/
  savadub/
    savo/
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
router()->get('{slug}', [ContactController::class, 'submit'])->name('submit.contact');
```

Or explicitly as:

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


## Developer Experience

Savo aims to feel familiar without becoming heavy.

Developers can expect:

- a simple and readable coding model
- direct PHP logic
- minimal abstraction
- framework-like helper syntax
- fast onboarding
- less boilerplate for page-driven websites

The framework is intentionally designed so that someone coming from Laravel-like conventions can feel comfortable, while someone with no prior framework experience can still understand what is going on.


## Philosophy

Savo is for developers who want:

- less boilerplate
- less abstraction
- less framework bloat
- more readable PHP
- more direct control
- a framework focused on modern websites

Savo does not try to do everything.

It tries to do the important website-building things clearly, cleanly, and without getting in your way.

## In Short

Use this repository when you want the **Savo Framework package** itself.

Use **Savo Starter** when you want to begin building a real Savo application.


