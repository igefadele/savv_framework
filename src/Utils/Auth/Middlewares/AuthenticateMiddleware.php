<?php

namespace Savv\Utils\Auth\Middlewares;

use Savv\Utils\Auth\AuthManager;
use Savv\Utils\Request;

/**
 * Authenticate Middleware for Savv Framework.
 * Resolves the user identity and attaches it to the Request attributes.
 */
class AuthenticateMiddleware
{
    protected ?AuthManager $auth;

    public function __construct(?AuthManager $auth = null)
    {
        $this->auth = $auth;
    }

    /**
     * Process an incoming server request.
     */
    public function handle(Request $request, callable $next)
    {
        $auth = $this->auth ?? AuthManager::fromConfig();

        // 1. Determine which guard to use based on route or headers
        // If the path starts with /api or has an Authorization header, use 'api'
        $path = $request->path();
        $isApi = str_starts_with($path, '/api') || (bool) $request->server('HTTP_AUTHORIZATION');
        
        $guardName = $isApi ? 'api' : 'web';
        $guard = $auth->guard($guardName);

        // 2. Resolve User
        $user = $guard->user();

        // 3. Attach identity to the Request for easy access in Controllers
        // Usage: $request->attribute('user');
        $request = $request->withAttribute('user', $user)
                           ->withAttribute('auth_guard', $guardName);

        // 4. (Optional) Strict check for protected routes
        // If I want to force login for all routes using this middleware:
        /*
        if (!$user) {
             // Return a 401 for API or redirect for Web
             return $isApi ? new JsonResponse(['error' => 'Unauthenticated'], 401) : new RedirectResponse('/login');
        }
        */

        return $next($request);
    }
}
