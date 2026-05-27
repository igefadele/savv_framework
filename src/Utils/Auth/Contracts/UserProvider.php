<?php

namespace Savv\Utils\Auth\Contracts;

/**
 * Defines how users are retrieved from storage (DB, JSON, etc).
 */
interface UserProvider {
    public function retrieveById(int|string $id): ?Authenticatable;
    public function retrieveByToken(string $token): ?Authenticatable;
    public function retrieveByCredentials(array $credentials): ?Authenticatable;
    public function validateCredentials(Authenticatable $user, array $credentials): bool;
}