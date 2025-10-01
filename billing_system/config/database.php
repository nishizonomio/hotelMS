<?php

class Database
{
    private $host = "localhost:3307";
    private $username = "root";
    private $password = "";
    private $dbname = "samplebilling";
    private $conn;

    public function __construct()
    {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function prepare($query)
    {
        return $this->conn->prepare($query);
    }
}

$db = new Database();
$conn = $db->getConnection();
