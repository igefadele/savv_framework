# Changelog

All notable changes to the Savv Web Framework are documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.5.1] - 2026-04-23

### Update

- **Utils/Router - dispatch() | safer $path slashing for failsave route comparision espesially for nested routes not using dynamicDiscovery.


## [2.5.0] - 2026-04-23

### Added

- **Utils/Request - Added server() and file() methods.


## [2.4.4] - 2026-04-22

### Updated

- **Helpers - layouts() | Optimised the internal scripts usage for savv_scripts()


## [2.4.3] - 2026-04-22

### Changed

- **Systemcontroller - getLocalAsset() | Optimised the cache expiration date
- **Systemcontroller - getServiceWorkerFile() | Updated the cache content addition to a safer logic if any precache value is missing from the user app.


## [2.4.2] - 2026-04-21

### FIXED

- **Helpers - asset() helper | To use PUBLIC_PATH global constant directly

## [2.4.1] - 2026-04-20

### Added

- **Helpers - abort() helper function.


## [2.4.0] — 2026-04-20

### Added

- **Asset Loading: Further optimised the asset loading helpers and router for more faster asset loading time.


## [2.1.0] — 2026-04-17

### Added

- **PWA Engine** — `SystemController` now dynamically generates `GET /manifest.json` and `GET /sw.js` from `configs/pwa.php`. The service worker implements install, activate, cache-first fetch, and offline fallback. Both routes are auto-registered via `src/Helpers/routes.php` on every boot — zero developer configuration required.
- **Layout Helpers** — New `savv_head()` and `savv_scripts()` helper functions in `src/Helpers/layouts.php`. `savv_head()` injects PWA meta tags, Bootstrap 5 CSS, Bootstrap Icons, and AOS CSS. `savv_scripts()` injects Bootstrap 5 JS, AOS (auto-initialized), Swup with HeadPlugin and ScrollPlugin, counter animation engine, and service worker registration — all in a single function call.
- **Route Caching via CLI** — New `route:cache` CLI command compiles all routes, file-based view routes (`views/pages/`), redirections, and posts into a single serialized PHP manifest at `storage/framework/routes.php` for production-grade dispatch performance.
- **CLI `make:controller` command** — Scaffolds a new controller class in `app/Controllers/` with the correct namespace and stub.
- **Console Kernel** — `Savv\Console\Kernel` added as the CLI entry point. Dispatches `make:controller`, `make:config`, and `route:cache` commands.
- **Dynamic View Discovery** — `Router::resolveDynamicView()` now handles GET requests that match no registered route by scanning `views/pages/` for a matching `.php` file. No route declaration needed for standard website pages.
- **Multi-CMS Fallback Chain** — `Application::handleExternalFallbacks()` now iterates a `configs/installations.php` config array instead of hardcoding a WordPress path. Supports multiple CMS targets with per-entry `active` flags. Final fallback renders `views/404.php` or returns a plain 404 string.
- **`view_path()` helper** — Returns an absolute filesystem path within the `views/` directory.
- **`AppProvider`** — `Savv\Providers\AppProvider` centralizes middleware alias resolution. Framework-level aliases are merged with application aliases from `configs/middlewares.php` via `AppProvider::middlewareAliases()`.
- **`Log::warning()` and `Log::debug()`** — Two new log levels added to `Savv\Utils\Log` alongside the existing `info()` and `error()`.
- **`Request::$files`** — `$_FILES` is now captured in `Request::__construct()` alongside `$_GET`, `$_POST`, and `$_SERVER`.
- **Route cache loading** — `Router::loadRawRoutes(array $routes)` and `Router::getRoutes(): array` added to support the cache read/write flow.

### Changed

- **Namespace** — All framework classes moved from the old `Savo\` namespace to `Savv\`. Composer autoload PSR-4 prefix updated to `"Savv\\": "src/"`.
- **Source directory** — All framework source files relocated from `savv/` to `src/`. File paths are now `src/Utils/Router.php`, `src/Helpers/helpers.php`, etc.
- **Middleware configuration** — Middleware aliases moved from `app/Constants/MiddlewareConstants.php` (user-space) to `configs/middlewares.php` (config layer), resolved by `AppProvider::middlewareAliases()`.
- **Bootstrap flow** — `Application::run()` now checks for a route cache before loading route files. Redirections are registered after route files when running in dynamic mode.
- **Router dispatch** — `dispatch()` now attempts explicit/cached routes first, then falls back to `resolveDynamicView()` for GET requests, then triggers `handleExternalFallbacks()`.
- **Cacheable route markers** — The router's `createRouteDestination()` method handles serializable `__savv_type` array markers (`redirect`, `post`, `view`) produced by the route cache compiler, enabling closures-free route caching.
- **`validate()` return value** — Now returns only the keys declared in the rules array (similar to Laravel's `$request->validated()`), not the full input payload.

### Fixed

- The WordPress fallback path is no longer hardcoded. CMS targets are now configurable via `configs/installations.php`.

---

## [2.0.0] — 2026-04-16

### Added

- **Dynamic Redirections** — Support for `configs/redirections.php` to handle "Pretty Link" style URL redirects automatically without route declarations.
- **CLI Scaffolding** — New `php savv make:config` command to generate config file boilerplate in `configs/`.

### Changed

- **Router Logic** — Optimized regex matching for faster route dispatching under high traffic.
- **Application Bootstrapping** — Refined `Application::run()` to register redirections before processing custom web and API routes.

### Fixed

- Resolved a fatal error where chaining `->name()` after a route definition returned `null` when the route helper did not return `$this`.

---

## [1.0.0] — 2026-04-01

### Added

- Initial stable release of the Savv Web Framework core.
- Core utilities: `Router`, `Request`, `Response`, `Log`, `Validator`, and `Config`.
- Global helper functions: `router()`, `request()`, `response()`, `config()`, `validate()`, `route()`, and `logger()`.
- File-based page routing — requests resolve to matching view files without controller or route registration.
- Explicit route support via `routes/web.php` and `routes/api.php`.
- Named routes and route parameters (`{slug}`).
- Middleware support for individual routes and route groups.
- Config loading from plain PHP arrays in `configs/`, with per-request caching.
- Daily log file output to `storage/logs/YYYY-MM-DD.log`.
- WordPress "Waterfall" fallback — unhandled requests pass through to WordPress when `wp-blog-header.php` exists.
- Composer installation via Packagist (`savadub/savv`) and VCS repository.

---

*For full upgrade instructions and migration notes, see the [documentation](https://savv.savadub.com).*
