<?php
namespace Savo\Utils;

/**
 * Loads and caches configuration arrays from the {@see ROOT_PATH}/configs directory.
 *
 * Config values are accessed using dot notation where the first segment maps to
 * the config filename and the remaining segments traverse nested array keys.
 */
class Config
{
    /**
     * Cached config payloads keyed by config filename.
     *
     * @var array<string, array<mixed>>
     */
    protected static array $cache = [];

    /**
     * Retrieve a configuration value using dot notation.
     *
     * Example: `config('mail.smtp.host')` will load `configs/mail.php` and return
     * the nested `smtp.host` value when available.
     *
     * @param string $key Dot-notated config key where the first segment is the filename.
     * @param mixed $default Fallback value returned when the config file or key is missing.
     * @return mixed The resolved configuration value or the provided default.
     */
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $filename = array_shift($parts);

        if (!isset(self::$cache[$filename])) {
            // Use the global ROOT_PATH constant here
            $path = ROOT_PATH . "/configs/{$filename}.php";
            
            if (!file_exists($path)) {
                return $default;
            }
            
            self::$cache[$filename] = require $path;
        }

        $items = self::$cache[$filename];

        foreach ($parts as $segment) {
            if (is_array($items) && array_key_exists($segment, $items)) {
                $items = $items[$segment];
            } else {
                return $default;
            }
        }

        return $items;
    }
}
