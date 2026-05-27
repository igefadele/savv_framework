<?php

namespace Savv\Utils\Auth;

/**
 * Authorization Layer (The Spatie-style Gate)
 */
class Gate {
    protected AuthManager $auth;

    public function __construct(AuthManager $auth) {
        $this->auth = $auth;
    }

    /**
     * Check if the current user has a specific permission for a guard.
     */
    public function allows(string $permission, ?string $guard = null): bool {
        $guardName = $guard ?: $this->auth->getDefaultGuardName();
        $user = $this->auth->guard($guardName)->user();
        
        if (!$user) return false;

        // Global "Super Admin" check
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission($permission, $guardName);
    }

    public function denies(string $permission, ?string $guard = null): bool {
        return !$this->allows($permission, $guard);
    }
}
