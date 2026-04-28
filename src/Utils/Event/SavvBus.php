<?php
namespace Savv\Utils\Bus;

class SavvBus {
    protected static $redis;

    public static function setDriver($redisInstance) {
        self::$redis = $redisInstance;
    }


    public static function dispatch($event, array $payload = []) {
        $redis = self::$redis;

        $packet = json_encode([
            'event' => $event,
            'source' => \Savv\Utils\Config::get('app.name', 'SavvApp'),
            'data' => $payload,
            'timestamp' => time()
        ]);

        if ($redis) {
            // Option A: Distributed (Redis)
            return $redis->lPush('savv_global_bus', $packet);
        }

        // Option B: Shared Hosting / Local (No Redis)
        // We can "dispatch" by directly executing the other app's CLI command
        // Example: exec("php /path/to/other/app/public/savv bus:receive '{$event}' '{$data}'");
        
        return false; 
    }
}