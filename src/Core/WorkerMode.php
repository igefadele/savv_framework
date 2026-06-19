<?php
namespace Savv\Core;

class WorkerMode
{
    /**
     * Returns true if the process is running inside a FrankenPHP worker.
     */
    public static function isActive(): bool
    {
        return isset($_SERVER['FRANKENPHP_WORKER']);
    }

    /**
     * Start the FrankenPHP worker loop.
     * Calls $app->run() for every incoming request.
     * Falls back to a single $app->run() call in standard FPM mode.
     */
    public static function serve(Application $app): void
    {
        if (!self::isActive()) {
            // Standard PHP-FPM — nothing changes for existing deployments
            $app->run();
            return;
        }

        // FrankenPHP persistent worker loop
        while (\FrankenPHP\handle_request(function () use ($app) {
            $app->run();
        })) {
            // handle_request() returns false when the worker should shut down
        }
    }
}