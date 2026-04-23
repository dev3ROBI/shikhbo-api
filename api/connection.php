<?php
require_once 'config.php';

class Database {
    private $conn;

    public function connect() {
        try {
            $this->conn = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                DB_PORT
            );

            if ($this->conn->connect_error) {
                throw new Exception($this->conn->connect_error);
            }

            $this->conn->set_charset("utf8mb4");

            return $this->conn;

        } catch (Exception $e) {
            http_response_code(500);

            die(json_encode([
                "status" => "error",
                "message" => "Database connection failed",
                "error" => $e->getMessage()
            ]));
        }
    }
}

// GLOBAL connection
$database = new Database();
$conn = $database->connect();
?>
