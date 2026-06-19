<?php

namespace Savv\Utils\Auth\Contracts;

/**
 * Defines how users are retrieved from storage (DB, JSON, etc).
 */
interface UserProvider {
    public function retrieveById(int|string $id): ?Authenticable;
    public function retrieveByToken(string $token): ?Authenticable;
    public function retrieveByCredentials(array $credentials): ?Authenticable;
    public function validateCredentials(Authenticable $user, array $credentials): bool;
}