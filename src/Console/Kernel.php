<?php
namespace Savv\Console;

use Savv\Console\Commands\{RouteCache, MakeConfig, MakeController};

class Kernel
{
    /**
     * The framework's command registry.
     */
    protected array $commands = [
        'make:controller' => MakeController::class,
        'make:config'     => MakeConfig::class,
        'route:cache'     => RouteCache::class,
    ];

    /**
     * Handle the incoming console command.
     */
    public function handle(array $args): void
    {
        // $args[0] is the script name 'savv', $args[1] is the command name
        $commandName = $args[1] ?? 'help';

        if (!isset($this->commands[$commandName])) {
            $this->printHelp();
            return;
        }

        $class = $this->commands[$commandName];
        $command = new $class();
        
        // Pass all arguments after the command name
        $command->execute(array_slice($args, 2));
    }

    protected function printHelp(): void
    {
        echo "Savv Framework CLI\n";
        echo "------------------\n";
        echo "Available commands:\n";
        foreach (array_keys($this->commands) as $name) {
            echo "  - {$name}\n";
        }
    }
}