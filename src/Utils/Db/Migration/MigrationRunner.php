<?php

namespace Savv\Utils\Db\Migration;

use PDO;

class MigrationRunner {
    protected PDO $db;
    protected array $paths;

    public function __construct(PDO $db, ?array $paths = null) {
        $this->db = $db;
        $this->paths = $paths ?? [
            dirname(__DIR__, 4) . '/framework/database/migrations',
            ROOT_PATH . '/database/migrations',
        ];
        $this->ensureMigrationTableExists();
    }

    private function ensureMigrationTableExists(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;");
    }

    public function migrate(bool $force = false): void {
        $files = $this->getMigrationFiles();
        $ran = $this->getRanMigrations();
        $batch = $this->getNextBatchNumber();
        $executedCount = 0;

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            if (in_array($migrationName, $ran)) continue;

            echo "Migrating: {$migrationName}\n";
            $migrationInstance = require $file;
            $migrationInstance->up($this->db);

            $stmt = $this->db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
            $stmt->execute([$migrationName, $batch]);
            
            echo "\e[32mMigrated:\e[0m  {$migrationName}\n";
            $executedCount++;
        }

        if ($executedCount === 0) echo "\e[33mNothing to migrate.\e[0m\n";
    }

    public function rollback(): void {
        $lastBatch = $this->db->query("SELECT MAX(batch) FROM migrations")->fetchColumn();
        if (!$lastBatch) {
            echo "\e[33mNothing to rollback.\e[0m\n";
            return;
        }

        $stmt = $this->db->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($migrations as $migrationName) {
            $this->executeDown($migrationName);
        }
    }

    public function reset(): void {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id DESC");
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($migrations)) {
            echo "\e[33mNothing to reset.\e[0m\n";
            return;
        }

        foreach ($migrations as $migrationName) {
            $this->executeDown($migrationName);
        }
    }

    public function wipe(): void {
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        // Fetch all user tables from database schema context
        $stmt = $this->db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->db->exec("DROP TABLE IF EXISTS `{$table}`;");
            echo "\e[31mDropped Table:\e[0m {$table}\n";
        }

        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "\e[32mDatabase wiped successfully.\e[0m\n";
    }

    public function seed(?string $specificClass): void {
        $seedPath = ROOT_PATH . '/database/seeders/';
        
        if ($specificClass) {
            $file = $seedPath . $specificClass . '.php';
            if (!file_exists($file)) {
                echo "\e[31mSeeder class '{$specificClass}' not found.\e[0m\n";
                return;
            }
            $this->runSeederFile($file);
            return;
        }

        // Run default DatabaseSeeder workflow if class argument wasn't specified
        $mainSeeder = $seedPath . 'DatabaseSeeder.php';
        if (file_exists($mainSeeder)) {
            $this->runSeederFile($mainSeeder);
        } else {
            echo "\e[33mNo seeders found to execute.\e[0m\n";
        }
    }

    public function monitor(): void {
        echo "\e[36m--- Database Diagnostics & Monitoring ---\e[0m\n";
        
        // Fetch engine status definitions
        $status = $this->db->query("SHOW STATUS LIKE 'Threads_connected'")->fetch(PDO::FETCH_ASSOC);
        echo "Active Connections (Threads): " . ($status['Value'] ?? 'Unknown') . "\n";

        $tablesStmt = $this->db->query("SHOW TABLE STATUS");
        $tableInfos = $tablesStmt->fetchAll(PDO::FETCH_ASSOC);

        echo "\n\e[33mTable Space Diagnostics:\e[0m\n";
        printf("%-25s %-10s %-10s\n", "Table Name", "Rows Count", "Data Size");
        echo str_repeat("-", 50) . "\n";
        
        foreach ($tableInfos as $info) {
            $sizeKB = round($info['Data_length'] / 1024, 2);
            printf("%-25s %-10d %-10s\n", $info['Name'], $info['Rows'], "{$sizeKB} KB");
        }
    }

    /**
     * Displays a clean diagnostic table outlining the execution state of all migrations.
     */
    public function status(): void {
        $files = $this->getMigrationFiles();

        if (empty($files)) {
            echo "\e[33mNo migrations found on disk.\e[0m\n";
            return;
        }

        // Query historical entries and format them into a searchable key-value map [migration_name => batch]
        try {
            $stmt = $this->db->query("SELECT migration, batch FROM migrations");
            $databaseRanMap = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (\Exception $e) {
            $databaseRanMap = [];
        }

        echo "\n\e[36m+------+-------------------------------------------------------+-------+\e[0m\n";
        printf("\e[36m| %-4s | %-53s | %-5s |\e[0m\n", "Ran?", "Migration Name", "Batch");
        echo "\e[36m+------+-------------------------------------------------------+-------+\e[0m\n";

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            
            if (isset($databaseRanMap[$migrationName])) {
                $statusSymbol = "\e[32mYes\e[0m"; // Green Yes if executed
                $batchValue = $databaseRanMap[$migrationName];
            } else {
                $statusSymbol = "\e[31mNo\e[0m";  // Red No if pending
                $batchValue = "-";
            }

            // Pad layout accurately to align table borders cleanly
            printf("| %-13s | %-53s | %-5s |\n", $statusSymbol, $migrationName, $batchValue);
        }

        echo "\e[36m+------+-------------------------------------------------------+-------+\e[0m\n\n";
    }

    private function executeDown(string $migrationName): void {
        echo "Rolling back: {$migrationName}\n";
        $file = $this->findMigrationFile($migrationName);
        
        if ($file) {
            $migrationInstance = require $file;
            $migrationInstance->down($this->db);
        }

        $deleteStmt = $this->db->prepare("DELETE FROM migrations WHERE migration = ?");
        $deleteStmt->execute([$migrationName]);
        echo "\e[31mRolled back:\e[0m {$migrationName}\n";
    }

    private function getMigrationFiles(): array {
        $files = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $directory = new \RecursiveDirectoryIterator($path);
            $iterator = new \RecursiveIteratorIterator($directory);

            foreach ($iterator as $info) {
                if ($info->isFile() && $info->getExtension() === 'php') {
                    $files[] = $info->getPathname();
                }
            }
        }

        sort($files);
        return $files;
    }

    private function findMigrationFile(string $name): ?string {
        foreach ($this->getMigrationFiles() as $file) {
            if (basename($file, '.php') === $name) return $file;
        }
        return null;
    }

    private function getRanMigrations(): array {
        try {
            return $this->db->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getNextBatchNumber(): int {
        $lastBatch = $this->db->query("SELECT MAX(batch) FROM migrations")->fetchColumn();
        return $lastBatch ? (int)$lastBatch + 1 : 1;
    }

    private function runSeederFile(string $path): void {
        echo "Running Seeder: " . basename($path) . "\n";
        $seederInstance = require $path;
        $seederInstance->run($this->db);
        echo "\e[32mSeeded:\e[0m " . basename($path) . "\n";
    }
}
