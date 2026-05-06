<?php
namespace Savv\Providers;

use Savv\Utils\Bus\SavvBus;
use Savv\Utils\Config;
use Savv\Utils\Db\SavvEvent;

class BusServiceProvider {
    public function boot() {
        try {

            $redisConfig = Config::get('database.redis');
            if (!$redisConfig) return;

            if (!($redisConfig['is_active'] ?? false)) return;

            $redis = new \Redis();
            $redis->connect($redisConfig['host'], $redisConfig['port'] ?? 6379);
            if (!empty($redisConfig['password'])) $redis->auth($redisConfig['password']);

            SavvBus::setDriver($redis);

            // Auto-broadcast events prefixed with 'broadcast:'
            SavvEvent::listen('broadcast.*', function($payload, $event) {
                SavvBus::dispatch($event, $payload);
            });
        } 
        catch (\Exception $e) {
            logger()->error("BusServiceProvider Boot Failed: " . $e->getMessage());
            abort(500, "An error occurred while initializing the event bus. Please try again later.");
        }
    }
}