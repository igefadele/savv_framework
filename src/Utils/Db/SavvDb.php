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
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
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