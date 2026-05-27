<?php

namespace Savv\Console\Commands;

use Savv\Utils\Db\Migration\MigrationRunner;
use Savv\Utils\Db\SavvDb;
use PDO;

class DbCommand {
    protected ?PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db;
    }

    public function execute(array $args): void {
        $action = $args[0] ?? 'migrate';
        $runner = new MigrationRunner($this->db ?? $this->resolveDatabaseConnection());
        $force = in_array('--force', $args);

        $destructiveActions = ['migrate:reset', 'migrate:refresh', 'migrate:fresh', 'db:wipe'];
        if (in_array($action, $destructiveActions, true) && $this->environment() === 'production' && !$force) {
            echo "\e[31mApplication In Production! Use --force to run destructive commands.\e[0m\n";
            return;
        }

        switch ($action) {
            case 'migrate':
                $runner->migrate($force);
                break;

            case 'migrate:rollback':
                $runner->rollback();
                break;

            case 'migrate:status':
                $runner->status();
                break;

            case 'migrate:reset':
                $runner->reset();
                break;

            case 'migrate:refresh':
                echo "Rolling back all migrations...\n";
                $runner->reset();
                echo "\nRunning all migrations...\n";
                $runner->migrate($force);
                break;

            case 'migrate:fresh':
                echo "Dropping all database objects...\n";
                $runner->wipe();
                echo "\nRunning all migrations...\n";
                $runner->migrate($force);
                break;

            case 'db:wipe':
                $runner->wipe();
                break;

            case 'db:seed':
                $class = null;
                foreach ($args as $arg) {
                    if (str_starts_with($arg, '--class=')) {
                        $class = explode('=', $arg)[1] ?? null;
                    }
                }
                $runner->seed($class);
                break;

            case 'db:monitor':
                $runner->monitor();
                break;

            case 'make:migration':
                $name = $args[1] ?? null;
                if (!$name) {
                    echo "\e[31mError: Provide a migration name.\e[0m\n";
                    return;
                }
                $table = null;
                foreach ($args as $arg) {
                    if (str_starts_with($arg, '--table=')) {
                        $table = explode('=', $arg)[1] ?? null;
                    }
                }
                $this->makeMigrationFile($name, $table);
                break;

            default:
                echo "Unknown command.\n";
                break;
        }
    }

    private function resolveDatabaseConnection(): PDO {
        $config = config('database');

        if (!$config || !($config['is_active'] ?? true)) {
            throw new \RuntimeException('Database is not configured or is inactive.');
        }

        return SavvDb::getInstance($config)->pdo();
    }

    private function environment(): string {
        return $_ENV['APP_ENV']
            ?? $_ENV['ENVIRONMENT']
            ?? getenv('APP_ENV')
            ?: (getenv('ENVIRONMENT') ?: 'local');
    }

    private function makeMigrationFile(string $name, ?string $table): void {
        $dir = ROOT_PATH . '/database/migrations';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $fullPath = $dir . '/' . $fileName;

        if ($table) {
            $stub = "<?php\n\nreturn new class {\n    public function up(PDO \$db): void {\n        \$sql = \"CREATE TABLE {$table} (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\";\n        \n        \$db->exec(\$sql);\n    }\n\n    public function down(PDO \$db): void {\n        \$db->exec(\"DROP TABLE IF EXISTS {$table};\");\n    }\n};\n";
        } else {
            $stub = "<?php\n\nreturn new class {\n    public function up(PDO \$db): void {\n        // \$db->exec(\"...\");\n    }\n\n    public function down(PDO \$db): void {\n        // \$db->exec(\"...\");\n    }\n};\n";
        }

        file_put_contents($fullPath, $stub);
        echo "\e[32mCreated Migration:\e[0m ./database/migrations/{$fileName}\n";
    }
}
