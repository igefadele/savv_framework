<?php
namespace Savv\Utils\Db;

use PDO;

/**
 * SavvDb: The Connection Manager
 * Wraps PDO to ensure we use prepared statements and handle errors.
 */
class SavvDb {
    protected static $instance = null;
    protected $pdo;

    public function __construct($config) {
        try {
            if ($config['is_active'] ?? false) {
                if (!isset($config['driver'], $config['host'], $config['database'], $config['username'], $config['password'], $config['charset'])) {
                    throw new \InvalidArgumentException("Database configuration is incomplete.");
                }

                $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
                
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
    public static function getInstance($config = null) {
        if (self::$instance === null && $config) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}