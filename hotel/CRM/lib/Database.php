<?php
namespace CRM\Lib;

class Database {
    private $host = "localhost";
    private $db_name = "hotel";
    private $username = "root";
    private $password = "";
    private $conn;

    public function __construct(array $config = []) {
        if (!empty($config)) {
            $this->host = $config['host'] ?? $this->host;
            $this->db_name = $config['db_name'] ?? $this->db_name;
            $this->username = $config['username'] ?? $this->username;
            $this->password = $config['password'] ?? $this->password;
        }
    }

    public function getConnection(): \PDO {
        if ($this->conn instanceof \PDO) return $this->conn;
        try {
            $this->conn = new \PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            throw $e;
        }
        return $this->conn;
    }
}
