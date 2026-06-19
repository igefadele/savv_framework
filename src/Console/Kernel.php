<?php
namespace Savv\Console;

use Savv\Console\Commands\{MakeConfig, MakeController, BusWorkCommand};
use Savv\Console\Commands\{DbCommand, CacheCommand, ClearCacheCommand};

class Kernel
{
    /**
     * The framework's command registry.
     */
    protected array $commands = [
        'make:controller'    => MakeController::class,
        'make:config'        => MakeConfig::class, 
        'bus:work'           => BusWorkCommand::class, // Internal connector

        'db:seed'            => DbCommand::class,
        'db:monitor'         => DbCommand::class,
        'db:wipe'            => DbCommand::class,
        
        'make:migration'     => DbCommand::class,
        'migrate'            => DbCommand::class,
        'migrate:rollback'   => DbCommand::class,
        'migrate:status'     => DbCommand::class,
        'migrate:reset'      => DbCommand::class,
        'migrate:refresh'    => DbCommand::class,
        'migrate:fresh'      => DbCommand::class,

        'optimize'           => CacheCommand::class,
        'cache:post'         => CacheCommand::class,
        'cache:posts'        => CacheCommand::class,
        'cache:page'         => CacheCommand::class,
        'cache:pages'        => CacheCommand::class,
        'cache:route'        => CacheCommand::class,
        'cache:routes'       => CacheCommand::class,
        'sync:post'          => CacheCommand::class,
        'sync:posts'         => CacheCommand::class,  

        'cache:clear'        => ClearCacheCommand::class,
        'cache:clear:posts'  => ClearCacheCommand::class,
        'cache:clear:pages'  => ClearCacheCommand::class,
        'cache:clear:routes' => ClearCacheCommand::class,
        'cache:clear:post'   => ClearCacheCommand::class,
        'cache:clear:page'   => ClearCacheCommand::class,
        'cache:clear:route'  => ClearCacheCommand::class,
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

        if ($command instanceof DbCommand) {
            $command->execute(array_merge([$commandName], array_slice($args, 2)));
            return;
        }

        if ($command instanceof CacheCommand || $command instanceof ClearCacheCommand) {
            $command->execute(array_slice($args, 2), $commandName);
            return;
        } 
        
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
