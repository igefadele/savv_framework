<?php 
namespace Savv\Providers;

/**
 * Provides framework-level configuration that is shared across the core runtime.
 *
 * At the moment this provider is responsible for exposing the middleware aliases
 * available to the router. Internal framework aliases are merged with any aliases
 * defined by the application in {@see \App\Constants\MiddlewareConstants}.
 */
class AppProvider 
{
    /**
     * Built-in middleware aliases reserved for the framework namespace.
     *
     * @var array<string, class-string>
     */
    protected static array $savoMiddlewareAliases = [];

    /**
     * Return every middleware alias available to the router.
     *
     * Framework aliases are merged with application aliases so projects can
     * register custom middleware without editing the core router.
     *
     * @return array<string, class-string>
     */
    public static function middlewareAliases(): array
    {
        return array_merge(self::$savoMiddlewareAliases, config('middlewares'));
    }
}