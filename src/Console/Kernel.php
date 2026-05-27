<?php
namespace Savv\Console;

use Savv\Console\Commands\{RouteCache, MakeConfig, MakeController, BusWorkCommand};
use Savv\Console\Commands\{
    CachePost, CacheAllPosts, CachePage, CacheAllPages, SyncPost, 
    SyncAllPosts, OptimizeCommand, DbCommand
};

class Kernel
{
    /**
     * The framework's command registry.
     */
    protected array $commands = [
        'make:controller' => MakeController::class,
        'make:config'     => MakeConfig::class, 
        'bus:work'        => BusWorkCommand::class, // Internal connector
        'cache:route'     => RouteCache::class,
        'cache:routes'    => RouteCache::class,
        'cache:post'      => CachePost::class,
        'cache:posts'     => CacheAllPosts::class,
        'cache:page'      => CachePage::class,
        'cache:pages'     => CacheAllPages::class,
        'sync:post'       => SyncPost::class,
        'sync:posts'      => SyncAllPosts::class, 
        'optimize'        => OptimizeCommand::class,

        'db:seed'         => DbCommand::class,
        'db:monitor'      => DbCommand::class,
        'db:wipe'         => DbCommand::class,
        
        'make:migration'  => DbCommand::class,
        'migrate'         => DbCommand::class,
        'migrate:rollback'  => DbCommand::class,
        'migrate:status'    => DbCommand::class,
        'migrate:reset'     => DbCommand::class,
        'migrate:refresh'   => DbCommand::class,
        'migrate:fresh'     => DbCommand::class,
        
        
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
