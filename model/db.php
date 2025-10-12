<?php
// db.php - Kết nối MySQL
$servername = "localhost";
$username   = "root"; // XAMPP mặc định
$password   = "";     // XAMPP mặc định để trống
$dbname     = "webmarket"; // Database của bạn

$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
