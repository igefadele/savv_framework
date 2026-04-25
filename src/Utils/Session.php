<?php
namespace Savv\Utils;

class Session
{
    protected static ?self $instance = null;

    protected function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    public function flash(string $key, $value = null)
    {
        if ($value !== null) {
            $this->set("_flash_$key", $value);
            return null;
        }

        $data = $this->get("_flash_$key");
        $this->remove("_flash_$key");
        return $data;
    }
}