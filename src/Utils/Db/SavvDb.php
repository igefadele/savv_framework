<?php
namespace Savv\Utils\Db;

use PDO;

/**
 * SavvDb: The Connection Manager
 * Wraps PDO to ensure we use prepared statements and handle errors.
 */
class SavvDb {
    protected static ?self $instance = null;
    protected PDO $pdo;

    public function __construct(array $config) {
        try {
            if ($config['is_active'] ?? false) {
                if (!isset($config['driver'], $config['host'], $config['database'], $config['username'], $config['password'], $config['charset'])) {
                    throw new \InvalidArgumentException("Database configuration is incomplete.");
                }

                $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";                
                $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }
            
        } catch (\PDOException $e) {
            // 1. Log the actual technical error for your eyes only
            logger()->error("Database Connection Failed: " . $e->getMessage());

            // 2. Gracefully inform the user or stop execution with a clean message
            abort(500, "Database connection could not be established. Please try again later.");
        }
    }

    // Singleton access: SavvDb::getInstance($config)
    public static function getInstance(?array $config = null): self {
        if (self::$instance === null) {
            if (!$config) {
                throw new \RuntimeException("Database not initialized. Call getInstance(config) first.");
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function query(string $sql, array $params = []): \PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
}