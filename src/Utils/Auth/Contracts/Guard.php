<?php

namespace Savv\Utils\Auth\Contracts;

/**
 * The Guard contract for different auth strategies.
 */
interface Guard {
    public function check(): bool;
    public function user(): ?Authenticatable;
    public function validate(array $credentials): bool;
    public function login(Authenticatable $user): void;
    public function logout(): void;
}