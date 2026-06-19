<?php

namespace Savv\Utils\Auth\Contracts;

use Savv\Utils\Auth\Contracts\Authenticable;

/**
 * The Guard contract for different auth strategies.
 */
interface Guard {
    public function check(): bool;
    public function user(): ?Authenticable;
    public function validate(array $credentials): bool;
    public function login(Authenticable $user): void;
    public function logout(): void;
}