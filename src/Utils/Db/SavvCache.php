<?php
namespace Savv\Utils\Db;

/**
 * SavvCache: The Identity Map
 * Prevents the N+1 problem by storing meta data in memory during the request.
 */
class SavvCache {
    public static $meta = [];

    public static function setMeta($id, $key, $val) {
        self::$meta[$id][$key] = $val;
    }

    public static function getMeta($id, $key) {
        return self::$meta[$id][$key] ?? null;
    }

    public static function has($id) {
        return isset(self::$meta[$id]);
    }

    // Clear cache after a long process or to free memory
    public static function flush() {
        self::$meta = [];
    }
}