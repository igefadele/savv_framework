<?php
namespace Savv\Providers;

use Savv\Utils\Bus\SavvBus;
use Savv\Utils\Config;
use Savv\Utils\Db\SavvEvent;

class BusServiceProvider {
    public function boot() {
        $redisConfig = Config::get('database.redis');
        if (!$redisConfig) return;

        $redis = new \Redis();
        $redis->connect($redisConfig['host'], $redisConfig['port'] ?? 6379);
        if (!empty($redisConfig['password'])) $redis->auth($redisConfig['password']);

        SavvBus::setDriver($redis);

        // Auto-broadcast events prefixed with 'broadcast:'
        SavvEvent::listen('broadcast.*', function($payload, $event) {
            SavvBus::dispatch($event, $payload);
        });
    }
}