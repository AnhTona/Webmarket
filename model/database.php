<?php
// Database.php - Kết nối MySQL OOP với Singleton Pattern

class Database {
    private static $instance = null;
    private $conn;

    private $servername = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "webmarket";

    // Private constructor để ngăn khởi tạo trực tiếp
    private function __construct() {
        try {
            $this->conn = new mysqli(
                $this->servername,
                $this->username,
                $this->password,
                $this->dbname
            );

            // Kiểm tra kết nối
            if ($this->conn->connect_error) {
                throw new Exception("Kết nối thất bại: " . $this->conn->connect_error);
            }

            // Set charset
            $this->conn->set_charset("utf8mb4");

        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    // Ngăn clone object
    private function __clone() {}

    // Ngăn unserialize
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    // Phương thức lấy instance duy nhất
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Lấy connection
    public function getConnection() {
        return $this->conn;
    }

    // Đóng kết nối
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }

    // Phương thức tiện ích: Thực thi query
    public function query($sql) {
        return $this->conn->query($sql);
    }

    // Phương thức tiện ích: Prepare statement
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    // Phương thức tiện ích: Escape string
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }

    // Lấy ID cuối cùng được insert
    public function getLastInsertId() {
        return $this->conn->insert_id;
    }
}

// Cách sử dụng:
// $db = Database::getInstance();
// $conn = $db->getConnection();
//
// Hoặc trực tiếp:
// $result = Database::getInstance()->query("SELECT * FROM products");
?>