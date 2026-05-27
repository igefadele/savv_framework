<?php

namespace Savv\Utils\Auth;

use Savv\Utils\Auth\Contracts\Authenticatable;
use Savv\Utils\Auth\Contracts\Guard;
use Savv\Utils\Auth\Contracts\UserProvider;

/**
 * Session Guard: Traditional web-based authentication.
 */
class SessionGuard implements Guard {
    protected ?Authenticatable $user = null;
    protected string $name;
    protected UserProvider $provider;

    public function __construct(string $name, UserProvider $provider) {
        $this->name = $name;
        $this->provider = $provider;
    }

    public function user(): ?Authenticatable {
        if ($this->user !== null) return $this->user;

        $id = $_SESSION["savv_auth_{$this->name}"] ?? null;
        if ($id) {
            $this->user = $this->provider->retrieveById($id);
        }
        return $this->user;
    }

    public function check(): bool {
        return $this->user() !== null;
    }

    public function validate(array $credentials): bool {
        $user = $this->provider->retrieveByCredentials($credentials);
        return $user && $this->provider->validateCredentials($user, $credentials);
    }

    public function login(Authenticatable $user): void {
        $_SESSION["savv_auth_{$this->name}"] = $user->getAuthId();
        $this->user = $user;
    }

    public function logout(): void {
        unset($_SESSION["savv_auth_{$this->name}"]);
        $this->user = null;
    }
}