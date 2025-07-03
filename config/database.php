<?php
/**
 * Database Configuration
 */

class Database 
{
    private static $instance = null;
    private $connection;
    private $config;

    private function __construct() 
    {
        $this->config = require_once __DIR__ . '/app.php';
        $this->connect();
    }

    public static function getInstance(): Database 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void 
    {
        try {
            $dbConfig = $this->config['database']['connections']['mysql'];
            
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }

    public function getConnection(): PDO 
    {
        return $this->connection;
    }

    public function prepare(string $sql): PDOStatement 
    {
        return $this->connection->prepare($sql);
    }

    public function lastInsertId(): string 
    {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): bool 
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool 
    {
        return $this->connection->commit();
    }

    public function rollback(): bool 
    {
        return $this->connection->rollback();
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}