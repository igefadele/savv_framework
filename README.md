# Savv Web Framework

> Savv is a zero-config, zero-build PHP engine engineered for building high-performance brand websites, studio portfolios, and public-facing web experiences. It is designed for developers, businesses, and individuals who demand the speed of a static site and the resilience of a lean PHP core, without the "build-tool tax."

>> The whole framework package folder is less than 1Mb. It's only 526Kb (~0.5Mb) as at v2.1.0

[![Packagist](https://img.shields.io/packagist/v/savadub/savv)](https://packagist.org/packages/savadub/savv)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.0-8892BF)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Why Savv Web?

Modern PHP frameworks are powerful — but they carry enormous overhead when all you need is a clean, fast, presentation-first website. Savv Web was built to fill that gap without compromise.

### How Savv Web Outpaces the Field

| Feature | Savv Web | Typical Full-Stack Framework | Static Site Generator |
|---|---|---|---|
| File-based routing — zero config | ✅ | ❌ | ✅ |
| No build tool required | ✅ | ❌ | ❌ |
| PWA built-in — no setup needed | ✅ | ❌ | ❌ |
| URL redirections — no settings required | ✅ | ❌ | ❌ |
| SSG-feel speed without build steps | ✅ | ❌ | ✅ |
| Deploys on any server, any compute tier | ✅ | ⚠️ | ✅ |
| Edit live in production — no rebuild wait | ✅ | ❌ | ❌ |

**File-based routing with zero configuration.** Drop a PHP file into `views/pages/` and it resolves as a URL automatically. No route registration, no controllers, no config file to touch. The router discovers it at runtime via dynamic view resolution.

**No build tool whatsoever.** No Node.js. No npm. No webpack, Vite, or any compiler pipeline. Install Composer, run `composer install`, point your server at `public/`, and you are live.

**PWA baked in — nothing to configure.** The framework self-registers `GET /manifest.json` and `GET /sw.js` routes via `SystemController` and `src/Helpers/routes.php` — wired automatically on every boot. The service worker and manifest are generated dynamically from a single `configs/pwa.php` file. The `savv_head()` and `savv_scripts()` helpers inject all PWA meta tags, service worker registration, SPA transitions, and AOS animations in two function calls. Nothing else is required from you.

**Built-in URL redirections — no plugin, no settings page.** Define a key-value array in `configs/redirections.php`. The framework reads it at bootstrap time and registers redirect routes automatically. `yourdomain.com/fb` redirects to Facebook with one line of config.

**SSG-feel speed without build steps.** The `php savv route:cache` CLI command compiles all routes, views, redirections, and posts into a single serialized PHP array at `storage/framework/routes.php`. On every subsequent request, the router loads this manifest directly — no filesystem scanning, no dynamic discovery overhead. Static-site-level dispatch performance with a fully dynamic, editable codebase underneath.

**Deploys on any server, any compute tier.** Plain PHP runs equally well on shared hosting, budget VPS, bare metal, and enterprise cloud. No Node.js runtime. No special server modules. No memory-hungry application containers. If the server runs PHP 8, it runs Savv Web.

**Edit files live, in production, instantly.** There is no build process between your files and your live site. Edit a view, a config, or a page and reload. No waiting. No pipeline required for content changes — critical when something needs to be fixed in seconds.

---

## What Savv Web Is Best For

- Brand and corporate websites
- Agency and studio portfolios
- Marketing and campaign sites
- Product landing pages
- Company profile and brochure sites
- Public-facing platforms with a few dynamic endpoints

If your project needs deep ORM workflows, an admin panel ecosystem, or a large plugin system, a full-stack framework will serve you better. Savv Web is optimized for **clarity, speed, and minimal overhead** on content-driven sites.

---

## Package Model

Savv Web is split into two repositories to keep responsibilities clean.

### Savv Web Framework ← *you are here*

The installable core package. Namespace: `Savv\`. All source lives in `src/`.

Contains the Application bootstrapper, Router (singleton), Request, Response, Config, Validator, Log, SystemController (PWA engine), Console Kernel, CLI commands, and all framework helpers.

### Savv Web Starter

The **[Savv Web Starter](https://github.com/igefadele/savv_starter)** is the ready-to-use project skeleton that already depends on this package. It is the **recommended starting point** for every new project.

---

## Quick Start

```bash
git clone https://github.com/igefadele/savv_starter my-project
cd my-project
composer install
```

Point your server document root to `public/` and start building immediately.

---

## Installing the Framework Directly

### Via Packagist

```bash
composer require savadub/savv
```

### Via GitHub (VCS)

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/igefadele/savv_framework"
    }
  ],
  "require": {
    "savadub/savv": "dev-main"
  }
}
```

```bash
composer update
```

---

## Project Structure

```text
my-savv-app/
├── app/
│   ├── Controllers/
│   └── Middleware/
│
├── configs/
│   ├── mail.php
│   ├── middlewares.php
│   ├── posts.php
│   ├── pwa.php
│   ├── redirections.php
│   └── installations.php    # External CMS/website integrations
│
├── public/                  # ← Web server document root
│   └── index.php            # 3-line entry point
│
├── routes/
│   ├── web.php
│   └── api.php
│
├── storage/
│   ├── framework/
│   │   └── routes.php       # Route cache (generated by CLI)
│   └── logs/
│       └── 2026-04-17.log
│
├── views/
│   ├── layouts/
│   │   └── index.php        # Master layout wrapper
│   ├── pages/               # File-based routing root
│   │   ├── index.php        # → /
│   │   ├── about.php        # → /about
│   │   ├── offline.php      # → /offline (PWA fallback page)
│   │   ├── posts.php        # → /posts (blog listing page)
│   │   └── post-detail.php  # → /post-detail (blog post detail page)
│   ├── partials/
│   │   ├── head.php
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── scripts.php
│   └── posts/               # Markdown post files
│
└── .env
```

---

## Bootstrap Flow

The entire application entry point is three lines:

```php
// public/index.php
define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/vendor/autoload.php';

$app = \Savv\Core\Application::bootstrap(dirname(__DIR__));
$app->run();
```

`Application::run()` executes this sequence on every request:

1. **Route cache check** — looks for `storage/framework/routes.php`
2. **Cache hit** → loads routes directly via `Router::loadRawRoutes()` (fast path — no file scanning)
3. **Cache miss** → calls `Router::loadRouteFiles()` which:
   - Loads internal framework routes from `src/Helpers/routes.php` (registers `/manifest.json` and `/sw.js`)
   - Loads all files in `routes/*.php` (`web.php`, `api.php`)
   - Calls `Router::registerRedirections()` to read `configs/redirections.php` and register redirect closures
4. **Request capture** — `Request::capture()` snapshots `$_GET`, `$_POST`, `$_SERVER`, `$_FILES`
5. **Dispatch** — `Router::dispatch()` matches against explicit registered routes
6. **Dynamic discovery** — if no explicit route matches a `GET` request, the router looks for a matching file in `views/pages/`
7. **CMS fallback** — if still unmatched, `handleExternalFallbacks()` checks `configs/installations.php` and hands off to any active CMS (e.g. WordPress)
8. **404** — `abort404()` renders `views/404.php` or outputs a plain 404 string

---

## Routing

### 1. File-Based Routing — Zero Configuration

The most common way to add a page. Place any `.php` file in `views/pages/` and it is accessible as a URL with no other steps:

```text
views/pages/index.php      → GET /
views/pages/about.php      → GET /about
views/pages/services.php   → GET /services
views/pages/blog/post.php  → GET /blog/post
```

Nothing to register. Nothing to configure. The router's `resolveDynamicView()` method handles discovery automatically.

The user is free to arrange the page files as they like, but they must ensure the main page file is inside the `views/pages/` directory. Page parts (partials, sections, components) can be anywhere in the `views/` directory or its subfolders. Just make sure to import the partials, sections, parts, and partials correctly into the said page file inside `views/pages/`.

### 2. Explicit Web Routes

For routes that need custom logic before rendering, use `routes/web.php`:

```php
router()->get('/', function () {
    require ROOT_PATH . '/views/pages/index.php';
});

router()->get('blog/{slug}', function ($slug) {
    // custom pre-render logic
    require ROOT_PATH . '/views/pages/blog.php';
})->name('blog.show');
```

When returning a page file from a custom route, the Router `view()` method can be used:

```php
router()->get('about', function () {
    return router()->view('pages/about');
});
```

### 3. API Routes

```php
// routes/api.php
use App\Controllers\ContactController;

router()->group(['prefix' => 'api', 'name' => 'api.'], function ($router) {
    $router->post('contact-submit', [ContactController::class, 'submit'])
           ->name('submit.contact');
});
```

### Named Routes and Route Parameters

```php
// Define
router()->get('blog/{slug}', function ($slug) { ... })->name('blog.show');

// Generate URL
$url = route('blog.show', ['slug' => 'getting-started']);
// → /blog/getting-started
```

Returns `#` when the route name is not found.

### Route Caching

Compile all routes into a performance-optimized static manifest for production:

```bash
php savv route:cache
```

Saves to `storage/framework/routes.php`. The router uses this on every boot — eliminating all dynamic file scanning. To clear the cache, delete the file. Regenerate it after adding new pages or routes.

---

## Blogging

Savv Web supports built-in blogging. Any `.md` file placed inside `views/posts/` becomes a blog post and is accessible at `domain.com/{slug}`, e.g., `domain.com/how-to-savv-website`.

### Blog Post Format

Each Markdown file must start with frontmatter in the following format:

```
---
title: Savv Website
slug: savv-website
date: 2026-04-17
author: Ige Fadele
status: published  # can be draft, trashed, published
category: blogging
---

# Your Blog Post Content

Write your post content here in Markdown.
```

The frontmatter fields are:
- `title`: The post title
- `slug`: The URL slug (must be unique)
- `date`: Publication date in YYYY-MM-DD format
- `author`: Author name
- `status`: Publication status (`published`, `draft`, or `trashed`)
- `category`: Post category

Only posts with `status: published` are accessible publicly.

### Blog Pages

Use the provided page files in `views/pages/` for your blog:
- `posts.php`: The blog listing page (e.g., `/posts`)
- `post-detail.php`: The individual post detail page (e.g., `/how-to-savv-website`)

You can customize these pages to display posts as needed.

---

## PWA — Built In, No Action Required

The framework self-registers two routes via `src/Helpers/routes.php` on every boot:

```
GET /manifest.json  →  SystemController::getManifestFile()
GET /sw.js          →  SystemController::getServiceWorkerFile()
```

`SystemController` dynamically generates both responses from `configs/pwa.php`. The service worker implements install, activate, cache-first fetch, and offline fallback to `/offline`.

Configure your PWA entirely in one file:

```php
// configs/pwa.php
return [
    'name'             => 'My Brand',
    'short_name'       => 'Brand',
    'description'      => 'What my site does.',
    'version'          => 'v1',      // Bump this to bust the service worker cache
    'theme_color'      => '#081065',
    'background_color' => '#ffffff',
    'display'          => 'standalone',
    'icons'            => [
        ['src' => '/assets/images/icons/icon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/assets/images/icons/icon-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
    ],
    'precache' => [
        '/',
        '/offline',
        '/assets/css/main.css',
        '/assets/js/main.js',
    ],
];
```

That is the only step. No additional code needed.

---

## Layout Helpers

`src/Helpers/layouts.php` provides two helper functions that inject the full frontend stack in a single call each.

### `savv_head()`

Call inside your `<head>`. Injects:
- PWA manifest link (`/manifest.json`)
- `theme-color` and mobile web app meta tags
- Apple touch icon meta tag
- Bootstrap 5 CSS (CDN)
- Bootstrap Icons CSS (CDN)
- AOS (Animate On Scroll) CSS (CDN)

```php
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?></title>
    <?php savv_head(); ?>
</head>
```

### `savv_scripts()`

Call before `</body>`. Injects:
- Bootstrap 5 JS bundle (CDN)
- AOS JS — auto-initialized (700ms, ease-out-cubic, once, offset 60)
- Swup with HeadPlugin and ScrollPlugin — SPA-feel page transitions
- PWA service worker registration
- Re-runs AOS and counters on every Swup page swap
- Calls `window.initPageScripts()` on each page swap if defined

```php
    <?php savv_scripts(); ?>
    <script src="/assets/js/main.js"></script>
</body>
```

After this, your site has SPA-feel navigation, and a fully registered PWA — no extra JavaScript written.

---

## Views and Layouts

Pages follow a simple capture-and-include pattern:

```php
// views/pages/about.php

$pageTitle       = 'About — My Brand';
$pageDescription = 'Who we are and what we build.';

ob_start();
?>
    <section>
        <h1>About Us</h1>
        <p>Our story here.</p>
    </section>
<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/index.php';
```

The layout wraps `$content` with your header, footer, and script partials. Also `view_path()`, `view_page()`, and `view_post()` helper are also available:

```php
$path = view_path('pages/about.php');
// → /absolute/root/views/pages/about.php
```

Or

```php
$path = page_path('about.php');
// → /absolute/root/views/pages/about.php
```

And if it's a post:

```php
$path = post_path('how-to-savv-website.md');
// → /absolute/root/views/posts/how-to-savv-website.md
```
---
### The #savv ID

#### Note this if you do not use the Savv Starter project:

It's IMPORTANT you wrap your main element or outermost container in #savv
This is for your app/page to benefit from the SPA feel and SSG-like navigation speed that Savv provides. If you use the Starter project then you don't need to care as this is already added there in the /views/layouts/inde.php, like:

```php
    <main id="savv" class="transition-fade">
        <?php echo $content; ?>
    </main>
```

---


## The `savv:init` Event

Savv dispatches a custom browser event called `savv:init` every time a page is initialized.

This happens in two scenarios:

1. **On the initial page load**
2. **After Savv dynamically swaps page content (client-side navigation)**

---

### Why This Matters

Because Savv performs partial page updates without full reloads, any JavaScript that relies on DOM elements needs to be re-initialized after each page swap.

Instead of relying on `DOMContentLoaded` (which only fires once), Savv provides a consistent lifecycle hook:

> **`savv:init` = "The page is ready. Run your UI logic now."**

---

### How It Works Internally

Savv dispatches the event like this:

```js
document.dispatchEvent(new CustomEvent('savv:init'));
```

This fires automatically on initial load and after every Swup page transition. You never call it yourself — just listen for it.

---

### Usage Example

Define your application logic in a reusable function:

```js
// Runs on initial load AND after every page swap
const myAppLogic = () => {
    console.log('Savv page ready. Initializing components...');

    // Example: initialize counters, sliders, tooltips, etc.
    const counters = document.querySelectorAll('.counter-element');
    // ... your component logic here
};
```

Then listen for the event:

```js
document.addEventListener('savv:init', myAppLogic);
```

---

### Best Practices

- Wrap all DOM-dependent logic inside a named function
- Always bind to `savv:init` instead of `DOMContentLoaded`
- Keep your logic **idempotent** — safe to run multiple times without side effects
- Avoid registering new event listeners *inside* your logic function, as it will run on every page swap

---

### Common Mistake

❌ This only runs once and will miss all subsequent page swaps:

```js
document.addEventListener('DOMContentLoaded', myAppLogic);
```

✅ This runs correctly on every page load and navigation:

```js
document.addEventListener('savv:init', myAppLogic);
```

---

### When Should You Use This?

Use `savv:init` whenever your code depends on:

- DOM elements (counters, modals, accordions, sliders)
- UI libraries (carousels, tooltips, date pickers)
- Rebinding event listeners after navigation
- Re-initializing any third-party plugin

---

### Summary

| Event | Fires When | Use Case |
|---|---|---|
| `DOMContentLoaded` | Once on initial page load | Traditional multi-page websites |
| `savv:init` | Every page load + every page swap | Savv-powered applications |

By using `savv:init`, your frontend logic stays consistent, predictable, and fully compatible with Savv's dynamic navigation system.


---

## URL Redirections

```php
// configs/redirections.php
return [
    'fb'      => 'https://facebook.com/yourpage',          // 302
    'careers' => ['url' => 'https://jobs.example.com', 'status' => 301],
];
```

`yourdomain.com/fb` redirects automatically. No controller. No route file edit. Registered at bootstrap by `Router::registerRedirections()`.

---

## Middleware

Define aliases in `configs/middlewares.php`:

```php
return [
    'auth' => \App\Middleware\Authenticate::class,
];
```

Apply to a group:

```php
router()->group(['prefix' => 'dashboard', 'middleware' => 'auth'], function ($router) {
    $router->get('overview', function () {
        require ROOT_PATH . '/views/pages/dashboard.php';
    });
});
```

Apply to a single route:

```php
router()->post('contact', [ContactController::class, 'submit'])
    ->middleware('auth')
    ->name('submit.contact');
```

Writing a middleware:

```php
namespace App\Middleware;

use Savv\Utils\Request;

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

---

## Global Helper Functions

All helpers auto-loaded via `src/Helpers/helpers.php`.

### `request()`

Singleton request instance or direct input access.

```php
request()                          // Request instance (singleton)
request('name', 'Guest')           // input value with default
request()->only(['name', 'email']) // subset
request()->all()                   // merged POST + GET
request()->post('field')           // POST only
request()->query('page')           // GET only
request()->method()                // 'GET', 'POST', etc.
request()->path()                  // '/about'
request()->filled('email')         // bool — true if non-empty ('0' counts)
request()->ajax()                  // bool — checks X-Requested-With header
```

### `response()`

Creates a new `Savv\Utils\Response` instance.

```php
response('<h1>Hello</h1>', 200)
response()->json(['status' => 'success'], 201)
response()->redirect('/thank-you')
response()->redirect('/new-url', 301)
response()->header('X-Powered-By', 'Savv Web')
response()->view('pages/about', ['title' => 'About'])
```

### `config()`

Dot-notation access to `configs/*.php`. Cached per request.

```php
config('mail.smtp.host')
config('pwa.theme_color')
config('pwa.version')
config('redirections.facebook')
```

### `validate()`

Validates and terminates with `422 JSON` on failure. Returns only declared keys on success.

```php
$validated = validate(request()->all(), [
    'name'    => 'required',
    'email'   => 'required|email',
    'message' => 'required|min:10|max:2000',
    'budget'  => 'numeric',
    'website' => 'url',
]);
```

Rules: `required`, `email`, `min:n`, `max:n`, `numeric`, `url`

### `route()`

```php
route('api.submit.contact')
route('blog.show', ['slug' => 'getting-started'])
// → /blog/getting-started
```

### `router()`

```php
router()->get('/', fn() => require ROOT_PATH . '/views/pages/index.php');
router()->post('submit', [FormController::class, 'handle'])->name('form.submit');
```

### `logger()`

```php
logger('Form submitted', ['email' => request('email')]);  // info
logger()->error('Mail failed', ['reason' => $e->getMessage()]);
logger()->warning('Slow query', ['ms' => 850]);
logger()->debug('Route matched', ['path' => request()->path()]);
```

Writes to `storage/logs/YYYY-MM-DD.log`. Format:
```
[2026-04-17 14:30:01] INFO: Form submitted {"email":"user@example.com"}
```

### `view_path()`

```php
view_path('pages/about.php')
// → /var/www/my-project/views/pages/about.php
```

---

## Core Utility Classes

### `Savv\Utils\Request`

| Method | Description |
|--------|-------------|
| `capture()` | Static factory — builds instance from superglobals |
| `input($key, $default)` | POST precedence over GET |
| `all()` | Merged GET + POST |
| `post($key, $default)` | POST data only |
| `only(array $keys)` | Subset of inputs |
| `except(array $keys)` | All inputs minus excluded keys |
| `filled($key)` | Non-empty check (`'0'` and `0` count as filled) |
| `query($key, $default)` | Query string values |
| `method()` | HTTP method string |
| `path()` | Request path without query string |
| `ajax()` | Detects `X-Requested-With: XMLHttpRequest` |

### `Savv\Utils\Response`

| Method | Description |
|--------|-------------|
| `setStatus(int $code)` | Set HTTP status code |
| `header($key, $value)` | Add response header |
| `json(array $data, int $status)` | JSON response, sets `Content-Type: application/json` |
| `redirect(string $url, int $status)` | HTTP redirect (default 302) |
| `view(string $viewPath, array $data)` | Render a PHP view file into content buffer |
| `send()` | Output status, headers, and body |

### `Savv\Utils\Config`

Loads PHP array files from `configs/` and caches them in a static property for the request lifecycle. Dot-notation access: first segment = filename, remaining segments = nested keys.

### `Savv\Utils\Validator`

`Validator::validate(array $data, array $rules): bool` — Returns true on pass. `Validator::getErrors(): array` — Returns field-keyed error messages from the last run.

### `Savv\Utils\Log`

Static methods: `info()`, `error()`, `warning()`, `debug()`. All write to `storage/logs/YYYY-MM-DD.log`. Log directory is created automatically if absent. Uses `FILE_APPEND | LOCK_EX` for safe concurrent writes.

### `Savv\Utils\Router`

Singleton. Supports `GET`, `POST`, named routes, route parameters (`{slug}`), route groups with prefix/name/middleware inheritance, middleware pipeline (PSR-style `handle($request, $next)`), dynamic view discovery fallback, and a serializable route cache format (`getRoutes()` / `loadRawRoutes()`).

### `Savv\Controllers\SystemController`

Framework-internal. Handles the PWA manifest and service worker routes. Reads `configs/pwa.php`. Not intended to be extended or called from application code.

---

## CLI Commands

Run from the project root:

```bash
php savv <command> [arguments]
```

| Command | Description |
|---------|-------------|
| `route:cache` | Compiles all routes into `storage/framework/routes.php` for production |
| `make:config <name>` | Scaffolds a blank config file in `configs/` |
| `make:controller <Name>` | Scaffolds a controller class in `app/Controllers/` |

### `route:cache` in detail

Compiles: explicit routes from `routes/*.php`, file-based view routes from `views/pages/`, redirections from `configs/redirections.php`, and post routes from `configs/posts.php`.

```bash
php savv route:cache
# → storage/framework/routes.php generated
```

Delete `storage/framework/routes.php` to return to dynamic mode. Regenerate after adding new pages or changing routes.

### `make:controller` output

```php
<?php

namespace App\Controllers;

use Savv\Utils\Request;

class BlogController
{
    public function index()
    {
        return response()->view('index');
    }
}
```

---

## External CMS Fallback

Savv Web allows you to run external CMS platforms like WordPress, or other websites/web apps under the hood of the framework. This is perfect when you need extensive blogging capabilities, e-commerce features, or any other functionality provided by mature CMS platforms, while still benefiting from Savv Web's lightweight routing and PWA features for your main site.

Instead of duct-taping different routing and redirection setups on the server, simply add an `installations.php` config file and set the entry file of the external CMS or website/web app, along with its live status. Savv Web will automatically pick up and transmit all requests meant for that external system without any additional server configuration.

Define CMS/website handoff targets in `configs/installations.php`:

```php
return [
    'wordpress' => [
        'active' => true,
        'path'   => '/var/www/wordpress/wp-blog-header.php',
    ],
    'ecommerce' => [
        'active' => false,
        'path'   => '/var/www/shop/index.php',
    ],
];
```

When no route matches in Savv Web, `handleExternalFallbacks()` iterates this config and requires the first active installation's path. If nothing claims the request, a 404 is returned. Custom 404 views are supported at `views/404.php`.

This seamless integration allows you to:
- Use WordPress for advanced blogging or CMS features
- Run e-commerce platforms alongside your Savv Web site
- Serve other websites or web apps under the same domain
- Maintain clean URLs without complex server rewrites

---

## Configuration Reference

### `configs/pwa.php`

```php
return [
    'name'             => 'My App',
    'short_name'       => 'App',
    'description'      => 'App description.',
    'version'          => 'v1',        // Bump to bust service worker cache
    'theme_color'      => '#000000',
    'background_color' => '#ffffff',
    'display'          => 'standalone',
    'icons'            => [...],
    'precache'         => ['/', '/offline', '/assets/css/main.css'],
];
```

### `configs/redirections.php`

```php
return [
    'fb'    => 'https://facebook.com/yourpage',
    'docs'  => ['url' => 'https://docs.example.com', 'status' => 301],
];
```

### `configs/middlewares.php`

```php
return [
    'auth' => \App\Middleware\Authenticate::class,
];
```

### `configs/mail.php`

```php
return [
    'smtp' => [
        'host'     => $_ENV['SMTP_HOST']     ?? null,
        'port'     => $_ENV['SMTP_PORT']     ?? null,
        'user'     => $_ENV['SMTP_USER']     ?? null,
        'password' => $_ENV['SMTP_PASSWORD'] ?? null,
        'security' => 'tls',
        'from'     => $_ENV['SMTP_FROM']     ?? null,
        'to'       => $_ENV['SMTP_TO']       ?? null,
    ],
];
```

### `configs/posts.php`

```php
return [
    'getting-started'   => 'Getting Started with Savv Web',
    'routing-deep-dive' => 'Routing Deep Dive',
];
```

### `configs/installations.php`

```php
return [
    'wordpress' => [
        'active' => true,
        'path'   => '/var/www/wordpress/wp-blog-header.php',
    ],
    'ecommerce' => [
        'active' => false,
        'path'   => '/var/www/shop/index.php',
    ],
];
```

Each installation entry contains:
- `active`: Boolean flag to enable/disable the integration
- `path`: Absolute path to the entry file of the external CMS/website/web app

When active, unmatched requests will be handed off to the external system.


---

## Database

Savv Web includes a lightweight, high-performance database layer built on four tightly designed classes. It gives you a modern ORM experience — fluent querying, eager loading, dirty-state tracking, and relationships — while adding negligible overhead and keeping the entire implementation readable and traceable.

The database layer lives under `Savv\Utils\Db\` and is available via global helpers (`savvQuery()`, `savvDb()`) in addition to static model methods.

---

### Architecture

The database layer is built on four core components, each with a single well-defined responsibility.

| Class | Responsibility |
|-------|---------------|
| `SavvDb` | Singleton PDO connection manager. All queries go through prepared statements. |
| `SavvModel` | Abstract base class for your models. Provides CRUD, dirty-state tracking, and relationship descriptors. |
| `SavvQuery` | Fluent query builder. Handles filtering, ordering, pagination, joins, eager loading, and model hydration. |
| `SavvCache` | In-memory identity map. Caches meta-data during the request lifecycle to prevent redundant queries. |

**The Identity Map (`SavvCache`).** To solve the N+1 query problem common in meta-data-heavy architectures, `SavvCache` stores fetched meta records in memory keyed by object ID. Subsequent accesses within the same request hit memory, not the database.

**Blueprint Relationships.** Relationship methods (`hasMany`, `belongsTo`, etc.) do not execute queries immediately. They return a descriptor array — a "blueprint" — that the eager-loading engine uses to batch all related records into a single query per relationship. Database load drops from O(N) to O(1 + number of relations).

**Dirty State Tracking.** `SavvModel` stores the original state of each model at load time. On `save()`, only columns that have actually changed are sent to the database. After a successful save, the original state is reset, preventing redundant identical writes on subsequent calls.

**Explicit Hydration.** `SavvQuery::setModel()` tells the builder exactly which class to instantiate for each result row. No convention guessing. No magic. Full type safety.

---

### Configuration

Add a `configs/database.php` file to your project:

```php
// configs/database.php

return [
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'savv_db',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
```

Initialize the connection once — typically in your bootstrap or a service provider — by passing the config to `SavvDb::getInstance()`:

```php
use Savv\Utils\Db\SavvDb;

SavvDb::getInstance(config('database'));
```

After that first call, `SavvDb::getInstance()` (with no arguments) returns the same singleton connection throughout the rest of the request.

---

### Defining Models

Extend `SavvModel` and declare the `$table` property:

```php
namespace App\Models;

use Savv\Utils\Db\SavvModel;

class Post extends SavvModel {
    protected static $table = 'posts';

    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments() {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
```

```php
namespace App\Models;

use Savv\Utils\Db\SavvModel;

class User extends SavvModel {
    protected static $table = 'users';

    public function posts() {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function profile() {
        return $this->hasOne(Profile::class, 'user_id');
    }
}
```

---

### CRUD Operations

**Find by ID**

```php
$post = Post::find(1);
echo $post->title;
```

**Create**

```php
$user = new User([
    'username' => 'ige_fadele',
    'email'    => 'ige@savadub.com',
    'status'   => 'active',
]);
$user->save();
// $user->id is now populated from lastInsertId()
```

**Update**

Only the columns that changed are sent to the database:

```php
$user = User::find(1);
$user->status = 'inactive'; // only this column will be updated
$user->save();
```

**Delete**

```php
$user = User::find(1);
$user->delete();
```

---

### Fluent Querying

Use `Model::query()` or the global `savvQuery()` helper to build expressive queries:

```php
// Via model
$users = User::query()
    ->select(['id', 'username', 'email'])
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->get();

// Via global helper
$posts = savvQuery('posts')
    ->where('is_published', 1)
    ->orderBy('created_at', 'DESC')
    ->get();
```

**Available builder methods**

| Method | Description |
|--------|-------------|
| `select($columns)` | Specify columns to fetch. Accepts a string or array. |
| `where($column, $value, $operator)` | Add a WHERE clause. Default operator is `=`. |
| `whereIn($column, $values)` | Add a WHERE IN clause. |
| `orderBy($column, $direction)` | Set ORDER BY. Default direction is `DESC`. |
| `join($table, $first, $second, $type)` | Add a JOIN. Default type is `INNER`. |
| `get()` | Execute and return all matched model instances. |
| `first()` | Execute and return the first matched result only. |
| `count()` | Return the count of matched rows as an integer. |
| `exists()` | Return `true` if at least one matching row exists. |
| `paginate($perPage, $page)` | Return a paginated result array. |

---

### Pagination

`paginate()` returns a structured array ready to use in your views:

```php
$result = User::query()
    ->where('status', 'active')
    ->paginate(15, $_GET['page'] ?? 1);

// $result contains:
// [
//     'data'         => [...],  // array of model instances
//     'total'        => 120,    // total matching rows
//     'per_page'     => 15,
//     'current_page' => 1,
//     'last_page'    => 8,
// ]
```

---

### Eager Loading

Load relationships upfront to avoid the N+1 problem. Savv fetches all related records in **one additional query per relationship**, regardless of how many parent models are in the result.

```php
// 2 queries total: one for posts, one for their authors
$posts = Post::query()
    ->with(['author'])
    ->get();

foreach ($posts as $post) {
    echo $post->author->name; // no extra query triggered
}

// Multiple relationships — still one extra query per relation
$posts = Post::query()
    ->with(['author', 'comments'])
    ->get();
```

---

### Relationships

#### `hasOne` — One-to-One

```php
// In User model
public function profile() {
    return $this->hasOne(Profile::class, 'user_id');
}

// Usage
$profile = User::find(1)->profile;
```

#### `hasMany` — One-to-Many

```php
// In Post model
public function comments() {
    return $this->hasMany(Comment::class, 'post_id');
}

// Usage
$comments = Post::find(1)->comments;
```

#### `belongsTo` — Inverse / Many-to-One

```php
// In Comment model
public function post() {
    return $this->belongsTo(Post::class, 'post_id');
}

// Usage
$post = Comment::find(1)->post;
```

#### `hasManyThrough` — Deep Relationships

Useful for structures like Country → Users → Posts, where you need the final collection without stepping through intermediate models manually. The method uses a standard `INNER JOIN` and returns a blueprint the eager-loading engine can batch.

```php
// In Country model
public function posts() {
    return $this->hasManyThrough(
        Post::class,    // target
        User::class,    // intermediate
        'country_id',   // foreign key on users table (links to Country)
        'user_id'       // foreign key on posts table (links to User)
    );
}

// Usage
$countryPosts = Country::find(1)->posts;
```

---

### Raw Queries

For advanced cases — transactions, DDL statements, or anything outside the builder — use `savvDb()` directly:

```php
// Raw query with parameters
savvDb()->query("UPDATE sessions SET expired = 1 WHERE last_seen < ?", [time() - 3600]);

// Within a transaction
$db = savvDb();
$db->query("START TRANSACTION");

try {
    $db->query("INSERT INTO orders (user_id, total) VALUES (?, ?)", [$userId, $total]);
    $db->query("UPDATE inventory SET stock = stock - 1 WHERE product_id = ?", [$productId]);
    $db->query("COMMIT");
} catch (\Exception $e) {
    $db->query("ROLLBACK");
    logger()->error('Transaction failed', ['reason' => $e->getMessage()]);
}
```

---

### The Identity Map — Meta Data

`SavvCache` is used internally by `SavvQuery::getWithMeta()` to batch-fetch meta records (from a `{table}_meta` table) alongside primary records. This is particularly useful for WordPress-style architectures where entities have a separate meta table.

```php
// Fetches users + their meta in 2 queries total, not N+1
$items = savvQuery('users')->getWithMeta([1, 2, 3, 4, 5]);

// Access meta on a model via __get — hits the cache, not the DB
echo $user->display_name;
```

You can also write to or read from the cache directly:

```php
use Savv\Utils\Db\SavvCache;

SavvCache::setMeta($userId, 'avatar_url', '/uploads/avatar.jpg');
$avatar = SavvCache::getMeta($userId, 'avatar_url');

// Clear the cache after a long-running process to free memory
SavvCache::flush();
```

---

### Global Database Helpers

| Helper | Returns | Description |
|--------|---------|-------------|
| `savvQuery($table)` | `SavvQuery` | Start a fluent query on any table. |
| `savvDb()` | `SavvDb` | Access the raw PDO wrapper for queries and transactions. |

---

### Security

All queries executed through `SavvDb::query()` — including every query generated by the builder and the model — use PDO prepared statements with bound parameters. User input passed through `where()`, `whereIn()`, `save()`, or raw `savvDb()->query()` calls is never interpolated into the SQL string. SQL injection protection is on by default with no extra configuration needed.

---

## Savv Event

`Savv\Utils\Db\SavvEvent` is the framework's lightweight in-memory event dispatcher. It lets you register listeners and fire events anywhere in the request lifecycle without introducing a separate event container or queue dependency.

It is also the bridge between model lifecycle hooks and cross-service bus messages. Local listeners receive events immediately in-process, and the bus worker re-emits remote packets back into the same dispatcher using a `bus:` prefix.

### Basic Usage

```php
use Savv\Utils\Db\SavvEvent;

SavvEvent::listen('order.placed', function ($payload) {
    // React to the event
});

SavvEvent::fire('order.placed', [
    'order_id' => 42,
    'total' => 199.99,
]);
```

### Cross-Service Event Intake

When a remote Savv service publishes an event through the bus worker, the worker fires it locally as `bus:{event}`:

```php
use Savv\Utils\Db\SavvEvent;

SavvEvent::listen('bus:user.created', function ($payload) {
    // Handle an event sent from another Savv app
});
```

Use plain event names for internal application flow, and `bus:`-prefixed names for events arriving from other services.

---

## Savv Observer

`Savv\Utils\Db\SavvObserver` gives you a clean place to register model event hooks. Each observer class defines an `observe()` method, and Savv boots those observers during application startup.

Observers work especially well when you want database changes to trigger side effects such as notifications, auditing, cache updates, or outbound bus dispatches for other Savv services.

### Registering Observers

Create `configs/observers.php` in your app and map each model to its observer:

```php
<?php

return [
    \App\Models\User::class => \App\Observers\UserObserver::class,
];
```

### Example Observer

```php
namespace App\Observers;

use App\Models\User;
use Savv\Utils\Bus\SavvBus;
use Savv\Utils\Db\SavvEvent;
use Savv\Utils\Db\SavvObserver;

class UserObserver extends SavvObserver
{
    public function observe()
    {
        User::created(function ($user) {
            SavvBus::dispatch('user.created', [
                'id' => $user->id,
                'email' => $user->email,
            ]);
        });

        SavvEvent::listen('bus:user.created', function ($payload) {
            // Handle the same event when it comes from another service
        });
    }
}
```

Because `SavvModel` already exposes helpers like `created()`, `updated()`, and `deleted()`, observers become the natural place to centralize domain reactions without scattering callbacks across controllers and models.

---

## Savv Bus Service

`Savv\Utils\Bus\SavvBus` is Savv's transport layer for multi-service communication. It allows independent Savv applications to publish events onto a shared bus so other services can receive and react to them asynchronously.

Out of the box, the bus follows this flow:

1. Your app dispatches an event with `SavvBus::dispatch()`.
2. The packet is pushed onto the shared Redis list `savv_global_bus`.
3. A long-running worker consumes the packet.
4. The worker re-fires the event locally as `bus:{event}` through `SavvEvent`.

This keeps services decoupled: the sender does not need to know which other service is listening, or whether there are multiple listeners.

### Redis Configuration

The bus provider activates automatically when `database.redis` exists in `configs/database.php`. Without Redis config, the bus remains dormant and the rest of the framework still runs normally.

```php
return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'savv_db',
    'username' => 'root',
    'password' => '',

    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
    ],
];
```

Savv will prefer the native `Redis` PHP extension and fall back to `Predis\Client` when available.

### Dispatching to Other Services

```php
use Savv\Utils\Bus\SavvBus;

SavvBus::dispatch('invoice.paid', [
    'invoice_id' => 501,
    'customer_id' => 88,
    'amount' => 45000,
]);
```

Each dispatched packet contains the event name, the application name from `config('app.name')`, the payload, and a timestamp.

### Running the Worker

To receive and relay bus messages, run the framework CLI worker:

```bash
php public/savv bus:work
```

The worker blocks on the shared bus and, for each incoming packet, fires `bus:{event}` inside the current app. That means one service can publish `user.created`, while another service listens for `bus:user.created` and reacts locally.

### Production Process Management

For reliable multi-service communication, keep the worker alive with a process manager such as Supervisor:

```ini
[program:savv-bus-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/public/savv bus:work
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/project/logs/bus-worker.log
```

If your deployment environment does not provide Redis, you can skip the worker entirely and continue using Savv in its normal single-application mode.


---

## Deployment

- Point your server document root to `public/`
- Keep all application files above the public web root
- Ensure `storage/logs/` and `storage/framework/` are writable by the web server (the `routes.php` cache will be written into `storage/framework/`)
- Provide all required values in `.env` for production
- Run `php savv route:cache` before going live
- Server block samples for **Apache, Nginx, Caddy, and LiteSpeed** are included in the starter at `public/server-block-samples/`

---

## Autoloading

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/"
    },
    "files": [
      "app/helpers.php"
    ]
  }
}
```

```bash
composer dump-autoload
```

---

## Philosophy

Most websites do not need a full-stack framework. They need clean routing, a request/response model, config management, validation, and a sensible structure — and they need to be fast, deployable anywhere, and editable without a build pipeline.

Savv Web delivers exactly that. Nothing more, nothing less.

It feels familiar to developers coming from Laravel conventions while remaining readable enough that someone new to frameworks can trace the entire codebase in an afternoon.

> **Savv Web is for developers who value readability, directness, and control — without the ceremony.**

---

## Links

| Resource | URL |
|----------|-----|
| Framework Repository | [github.com/igefadele/savv_framework](https://github.com/igefadele/savv_framework) |
| Starter Repository | [github.com/igefadele/savv_starter](https://github.com/igefadele/savv_starter) |
| Documentation | [savv.savadub.com](https://savv.savadub.com) |
| Packagist | [packagist.org/packages/savadub/savv](https://packagist.org/packages/savadub/savv) | 
Ige Fadele | [igefadele.savadub.com](https://igefadele.savadub.com) |
Savadub LLC | [savadub.com](https://savadub.com) |
WhatsApp | [wa.me/2349032348435](https://wa.me/2349032348435) |
LinkedIn | [linkedin.com/in/igefadele](https://linkedin.com/in/igefadele) |
---

## Contributing

Pull requests, issue reports, and suggestions are welcome. Please open an issue before submitting large changes.

---

## License

MIT — see [LICENSE](LICENSE).

---

*Built by [Savadub](https://savadub.com) — a Global Venture & Talent Studio.*
