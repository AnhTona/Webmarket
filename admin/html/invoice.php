<?php
// admin/html/invoice.php

require_once __DIR__ . '/../../model/database.php'; // đúng đường dẫn của bạn
header('Content-Type: text/html; charset=UTF-8');

// Lấy kết nối từ Singleton Database
if (class_exists('Database')) {
    $db   = Database::getInstance();
    $conn = $db->getConnection();        // <-- lấy mysqli
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo '<h3>Không thể kết nối cơ sở dữ liệu.</h3>';
    exit;
}
$conn->set_charset('utf8mb4');

$oid = isset($_GET['id']) ? (int)$_GET['id'] : 0; // MaDon
if ($oid <= 0) {
    http_response_code(400);
    echo '<h3>Thiếu mã đơn hàng.</h3>';
    exit;
}

// hoadon: MaHoaDon (PK), MaDonHang (FK), NoiDungHTML
$sql = "SELECT NoiDungHTML
        FROM hoadon
        WHERE MaDonHang = ?
        ORDER BY MaHoaDon DESC
        LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo '<h3>Lỗi chuẩn bị truy vấn.</h3>';
    exit;
}
$stmt->bind_param('i', $oid);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo '<h3>Không tìm thấy hóa đơn cho đơn #'.htmlspecialchars((string)$oid).'</h3>';
    exit;
}

$html = $row['NoiDungHTML'] ?? '';

// Nếu lúc lưu có addslashes => DB chứa \n, \" ...
if (strpos($html, '\\n') !== false || strpos($html, '\\"') !== false || strpos($html, '\\t') !== false) {
    $html = stripcslashes($html);
}

echo $html;
