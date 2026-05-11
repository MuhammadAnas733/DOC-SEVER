<?php
// src/Database.php

namespace Hospital;

use PDO;
use PDOException;

require_once __DIR__ . '/Config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // Parse host and port from Config
        $host = Config::DB_HOST;
        $port = Config::DB_PORT;

        $dsn = "mysql:host=$host;port=$port;dbname=" . Config::DB_NAME . ";charset=utf8mb4";
        
        try {
            $this->connection = new PDO($dsn, Config::DB_USER, Config::DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $this->connection->exec("SET time_zone = '+05:00'");
            return;
        } catch (PDOException $e) {
            // Log or handle error
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => "Database connection failed: " . $e->getMessage()]);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->getConnection();
    }

    public function getConnection() {
        return $this->connection;
    }
}
