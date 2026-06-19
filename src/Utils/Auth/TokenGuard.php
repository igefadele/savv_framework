<?php

namespace Savv\Utils\Auth;

use Savv\Utils\Auth\Contracts\Authenticable;
use Savv\Utils\Auth\Contracts\Guard;
use Savv\Utils\Auth\Contracts\UserProvider;

/**
 * Token Guard: API-based authentication (Bearer Tokens).
 */
class TokenGuard implements Guard {
    protected ?Authenticable $user = null;
    protected UserProvider $provider;
    protected string $inputKey = 'Authorization';

    public function __construct(UserProvider $provider) {
        $this->provider = $provider;
    }

    public function user(): ?Authenticable {
        if ($this->user !== null) return $this->user;

        // Extract token from Bearer Header
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            $this->user = $this->provider->retrieveByToken($matches[1]);
        }

        return $this->user;
    }

    public function check(): bool {
        return $this->user() !== null;
    }

    public function validate(array $credentials): bool { return false; } // Tokens are usually pre-validated
    public function login(Authenticable $user): void {} // Stateless
    public function logout(): void {} // Stateless
}