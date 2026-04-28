<?php
namespace Savv\Utils\Db;

class SavvEvent {
    protected static $listeners = [];

    public static function listen($event, callable $callback) {
        self::$listeners[$event][] = $callback;
    }

    public static function fire($event, $payload = null) {
        if (isset(self::$listeners[$event])) {
            foreach (self::$listeners[$event] as $callback) {
                if ($callback($payload) === false) return false;
            }
        }
        return true;
    }
}