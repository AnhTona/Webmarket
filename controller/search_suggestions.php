<?php
// view/html/search_suggestions.php
header("Content-Type: application/json; charset=UTF-8");
include __DIR__ . '/../model/database.php'; // Đảm bảo đường dẫn này đúng

// Đảm bảo charset
$conn->set_charset("utf8mb4");

$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$products = [];

if (empty($keyword)) {
    echo json_encode($products, JSON_UNESCAPED_UNICODE);
    exit;
}

// Sử dụng Prepared Statement để bảo mật
$sql = "SELECT MaSanPham AS id, TenSanPham AS name FROM sanpham WHERE TenSanPham LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $like_keyword = '%' . trim($keyword) . '%';
    $stmt->bind_param("s", $like_keyword);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
}

echo json_encode($products, JSON_UNESCAPED_UNICODE);
// KHÔNG đóng $conn ở đây nếu db.php có thể được include ở nơi khác
// $conn->close(); 
?>