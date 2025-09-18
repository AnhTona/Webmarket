<?php
class Database {
    private string $host = "127.0.0.1";
    private int    $port = 3306;
    private string $user = "root";
    private string $pass = "";
    private string $db   = "webmarket";

    protected ?PDO $conn = null;

    public function connect(): PDO {
        if ($this->conn instanceof PDO) return $this->conn;
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            die("DB connect failed: ".$e->getMessage());
        }
        return $this->conn;
    }
}
