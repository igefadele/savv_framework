<?php

namespace Savv\Utils\Auth\Traits;

use PDO;

/**
 * Trait HasPermissions
 * Assumes the using class can expose a PDO connection and auth identifier.
 */
trait HasPermissions
{
    /**
     * Runtime cache to prevent duplicate database queries during the same request lifecycle.
     * Structure: ['guard_name' => ['permission_1', 'permission_2']]
     * @var array|null
     */
    protected ?array $permissionCache = null;

    /**
     * Runtime role cache.
     * Structure: ['guard_name' => ['role_1', 'role_2']]
     *
     * @var array|null
     */
    protected ?array $roleCache = null;

    abstract protected function getDb(): PDO;

    abstract public function getAuthId(): int|string;

    /**
     * Check if the model has a specific permission under a given guard.
     */
    public function hasPermission(string $permission, string $guard = 'web'): bool
    {
        // 1. If it's a Super Admin, bypass checking permissions entirely
        if (method_exists($this, 'isSuperAdmin') && $this->isSuperAdmin()) {
            return true;
        }

        // 2. Load permissions into runtime cache if not already loaded
        $this->loadPermissionsCache($guard);

        // 3. Perform a fast array lookup
        return isset($this->permissionCache[$guard]) && in_array($permission, $this->permissionCache[$guard], true);
    }

    /**
     * Check if the model belongs to a specific role.
     */
    public function hasRole(string $role, string $guard = 'web'): bool
    {
        $this->loadRolesCache($guard);

        return isset($this->roleCache[$guard]) && in_array($role, $this->roleCache[$guard], true);
    }

    /**
     * Check if the model has at least one of the given roles.
     *
     * @param array<int, string> $roles
     */
    public function hasAnyRole(array $roles, string $guard = 'web'): bool
    {
        $this->loadRolesCache($guard);

        return count(array_intersect($roles, $this->roleCache[$guard] ?? [])) > 0;
    }

    /**
     * Check if the model has every given role.
     *
     * @param array<int, string> $roles
     */
    public function hasAllRoles(array $roles, string $guard = 'web'): bool
    {
        $this->loadRolesCache($guard);

        return empty(array_diff($roles, $this->roleCache[$guard] ?? []));
    }

    /**
     * Return all role names assigned to this model for a guard.
     *
     * @return array<int, string>
     */
    public function getRoleNames(string $guard = 'web'): array
    {
        $this->loadRolesCache($guard);

        return $this->roleCache[$guard] ?? [];
    }

    /**
     * Pulls all direct permissions AND role-inherited permissions from the DB 
     * for this specific guard and dumps them into memory.
     */
    protected function loadPermissionsCache(string $guard): void
    {
        if (isset($this->permissionCache[$guard])) {
            return;
        }

        $db = $this->getDb();
        $modelId = $this->getAuthId();
        $modelType = $this->getModelType();

        // Query 1: Get permissions granted via Roles
        $rolePermsStmt = $db->prepare("
            SELECT p.name FROM permissions p
            JOIN role_has_permissions rhp ON p.id = rhp.permission_id
            JOIN model_has_roles mhr ON rhp.role_id = mhr.role_id
            WHERE mhr.model_id = :model_id 
              AND mhr.model_type = :model_type 
              AND p.guard_name = :guard_name
        ");
        
        $rolePermsStmt->execute([
            'model_id'   => $modelId,
            'model_type' => $modelType,
            'guard_name' => $guard
        ]);
        $rolePermissions = $rolePermsStmt->fetchAll(PDO::FETCH_COLUMN);

        // Query 2: Get permissions granted Directly to the model
        $directPermsStmt = $db->prepare("
            SELECT p.name FROM permissions p
            JOIN model_has_permissions mhp ON p.id = mhp.permission_id
            WHERE mhp.model_id = :model_id 
              AND mhp.model_type = :model_type 
              AND p.guard_name = :guard_name
        ");
        
        $directPermsStmt->execute([
            'model_id'   => $modelId,
            'model_type' => $modelType,
            'guard_name' => $guard
        ]);
        $directPermissions = $directPermsStmt->fetchAll(PDO::FETCH_COLUMN);

        // Merge arrays and filter out duplicates
        $allPermissions = array_unique(array_merge($rolePermissions, $directPermissions));

        // Store in runtime memory cache
        $this->permissionCache[$guard] = $allPermissions;
    }

    /**
     * Load role names for this model and guard into memory.
     */
    protected function loadRolesCache(string $guard): void
    {
        if (isset($this->roleCache[$guard])) {
            return;
        }

        $stmt = $this->getDb()->prepare("
            SELECT r.name FROM roles r
            JOIN model_has_roles mhr ON r.id = mhr.role_id
            WHERE mhr.model_id = :model_id
              AND mhr.model_type = :model_type
              AND r.guard_name = :guard_name
        ");

        $stmt->execute([
            'model_id'   => $this->getAuthId(),
            'model_type' => $this->getModelType(),
            'guard_name' => $guard
        ]);

        $this->roleCache[$guard] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Clear the runtime memory cache (Useful if you alter permissions during a test/seed).
     */
    public function clearPermissionCache(): void
    {
        $this->permissionCache = null;
        $this->roleCache = null;
    }

    /**
     * Helper to retrieve the current class string for the polymorphic schema.
     */
    protected function getModelType(): string
    {
        return static::class;
    }
}
