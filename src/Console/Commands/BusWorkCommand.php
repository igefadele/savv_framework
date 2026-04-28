<?php
namespace Savv\Console\Commands;

use Savv\Utils\Db\SavvEvent;

class BusWorkCommand {
    public function execute(array $args): void {
        // Access the instance created in the 'savv' entry file
        global $app; 

        if (!$app) {
            echo "Error: Application not bootstrapped correctly.\n";
            exit(1);
        }

        try {
            $redis = $app->getRedis();

            if (!$redis) {
                echo "Savv Bus Error: Redis is not configured or the drivers are missing.\n";
                echo "Check your configs/database.php for 'redis' settings.\n";
                exit(1); 
            }

            $channel = 'savv_global_bus';
            echo "Savv Bus Worker listening...\n";

            while (true) {
                $message = $redis->brPop([$channel], 0);
                if ($message) {
                    $packet = json_decode($message[1], true);
                    echo "[".date('Y-m-d H:i:s')."] Received: {$packet['event']} from {$packet['source']}\n";
                    SavvEvent::fire("bus:{$packet['event']}", $packet['data']);
                }
            }
        } catch (\Exception $e) {
            echo "Bus Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}