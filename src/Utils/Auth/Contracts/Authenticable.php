<?php

namespace Savv\Utils\Auth\Contracts;

/**
 * Represents the user identity in the auth system.
 */
interface Authenticable
{
    public function getAuthId(): int|string;
    public function getAuthPassword(): string;
    public function hasPermission(string $permission, string $guard): bool;
}
