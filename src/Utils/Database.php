<?php

namespace App\Utils;

use mysqli;
use Exception;

/**
 * Database Connection Manager
 * Singleton pattern for managing database connections
 */
class Database
{
    private static ?mysqli $connection = null;
    private static ?array $config = null;

    /**
     * Get database connection instance
     */
    public static function getConnection(): mysqli
    {
        if (self::$connection === null) {
            self::connect();
        }
        return self::$connection;
    }

    /**
     * Establish database connection
     */
    private static function connect(): void
    {
        if (self::$config === null) {
            $dbConfig = require CONFIG_PATH . '/database.php';
            self::$config = $dbConfig['connections'][$dbConfig['default']];
        }

        self::$connection = new mysqli(
            self::$config['host'],
            self::$config['username'],
            self::$config['password'],
            self::$config['database'],
            (int)self::$config['port']
        );

        if (self::$connection->connect_error) {
            throw new Exception('Database connection failed: ' . self::$connection->connect_error);
        }

        self::$connection->set_charset(self::$config['charset']);
        self::$connection->query("SET time_zone = '+00:00'");
    }

    /**
     * Close database connection
     */
    public static function close(): void
    {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }

    /**
     * Execute a query and return result
     */
    public static function query(string $sql): \mysqli_result|bool
    {
        $conn = self::getConnection();
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception('Query failed: ' . $conn->error);
        }
        
        return $result;
    }

    /**
     * Prepare a statement
     */
    public static function prepare(string $sql): \mysqli_stmt|false
    {
        return self::getConnection()->prepare($sql);
    }

    /**
     * Escape string for safe queries
     */
    public static function escape(string $value): string
    {
        return self::getConnection()->real_escape_string($value);
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId(): int
    {
        return self::getConnection()->insert_id;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollback();
    }
}
