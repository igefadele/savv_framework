<?php
namespace Savv\Utils\Db;

/**
 * SavvCache: The Identity Map
 * Prevents the N+1 problem by storing meta data in memory during the request.
 */
class SavvCache {
    public static $meta = [];

    public static function setMeta(string|int $id, string $key, mixed $val): void {
        self::$meta[$id][$key] = $val;
    }

    public static function getMeta(string|int $id, string $key): mixed {
        if (!$id || !isset(self::$meta[$id])) {
            return null;
        }

        return self::$meta[$id][$key] ?? null;
    }

    public static function has(string|int $id): bool {
        return isset(self::$meta[$id]);
    }

    /** Clear cache after a long process or to free memory */
    public static function flush(): void {
        self::$meta = [];
    }
}