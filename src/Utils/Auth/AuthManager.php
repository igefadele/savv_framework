<?php

namespace Savv\Utils\Auth;

use Savv\Utils\Auth\Contracts\Guard;
use Savv\Utils\Auth\Contracts\UserProvider;

/**
 * The Auth Manager (The "NextAuth" inspired Hub)
 */
class AuthManager {
    protected array $config;
    protected array $guards = [];
    protected array $customDrivers = [];
    protected UserProvider $provider;
    protected static ?self $resolvedInstance = null;

    public function __construct(array $config, UserProvider $provider) {
        $this->config = $config;
        $this->provider = $provider;
    }

    /**
     * Resolve the framework auth manager from config/auth.php.
     */
    public static function fromConfig(?array $config = null, ?UserProvider $provider = null): self {
        if ($config === null && $provider === null && self::$resolvedInstance !== null) {
            return self::$resolvedInstance;
        }

        $config ??= config('auth', []);
        $provider ??= self::resolveProvider($config);

        $manager = new self($config, $provider);

        if (func_num_args() === 0) {
            self::$resolvedInstance = $manager;
        }

        return $manager;
    }

    /**
     * Clear the cached config-resolved manager.
     */
    public static function forgetResolvedInstance(): void {
        self::$resolvedInstance = null;
    }

    /**
     * Resolve the requested guard.
     */
    public function guard(?string $name = null): Guard {
        $name = $name ?: $this->getDefaultGuardName();

        if (isset($this->guards[$name])) {
            return $this->guards[$name];
        }

        return $this->guards[$name] = $this->createGuard($name);
    }

    public function getDefaultGuardName(): string {
        return $this->config['default'] ?? 'web';
    }

    protected function createGuard(string $name): Guard {
        $driver = $this->config['guards'][$name]['driver'] ?? null;

        return match ($driver) {
            'session' => new SessionGuard($name, $this->provider),
            'token'   => new TokenGuard($this->provider),
            default   => throw new \Exception("Auth driver [{$driver}] not defined."),
        };
    }

    /**
     * Proxies calls to the default guard.
     */
    public function __call($method, $parameters) {
        return $this->guard()->$method(...$parameters);
    }

    protected static function resolveProvider(array $config): UserProvider {
        $provider = $config['provider'] ?? null;

        if ($provider instanceof UserProvider) {
            return $provider;
        }

        if (is_string($provider) && class_exists($provider)) {
            $instance = new $provider();

            if (!$instance instanceof UserProvider) {
                throw new \InvalidArgumentException("Auth provider [{$provider}] must implement UserProvider.");
            }

            return $instance;
        }

        throw new \RuntimeException('Auth provider not configured. Set auth.provider to a UserProvider class or instance.');
    }
}
