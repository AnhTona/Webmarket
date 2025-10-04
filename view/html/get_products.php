<?php
header("Content-Type: application/json; charset=UTF-8");
include __DIR__ . '/../../db.php';

// Đảm bảo charset
$conn->set_charset("utf8mb4");
$products = [];

$sql = "SELECT s.*, GROUP_CONCAT(DISTINCT dm.TenDanhMuc SEPARATOR ', ') AS categories
        FROM sanpham s
        LEFT JOIN sanpham_khuyenmai sd ON s.MaSanPham = sd.MaSanPham
        LEFT JOIN danhmuc dm ON sd.MaDanhMuc = dm.MaDanhMuc
        GROUP BY s.MaSanPham";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "error" => "SQL lỗi: " . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

while ($row = $result->fetch_assoc()) {
    $isPromo = (int)$row["isPromo"]; // Lấy giá trị isPromo từ database
    $oldPrice = $isPromo && $row["GiaCu"] ? (int)$row["GiaCu"] : null;
    if (!$oldPrice && $isPromo) {
        $oldPrice = (int)($row["Gia"] * (1 + (rand(10, 30) / 100)));
    }
    $products[] = [
        "id" => (int)$row["MaSanPham"],
        "name" => $row["TenSanPham"],
        "price" => (int)$row["Gia"],
        "oldPrice" => $oldPrice,
        "image" => $row["HinhAnh"] ?: "image/sp1.jpg",
        "category" => $row["categories"] ?: $row["danh_muc"],  // Ưu tiên categories từ join
        "subCategory" => $row["loai"] ?: "",
        "popularity" => rand(50, 500), // Tạo ngẫu nhiên, có thể lưu vào database nếu cần cố định
        "newProduct" => (bool)rand(0, 1), // Tạo ngẫu nhiên, có thể lưu vào database nếu cần cố định
        "isPromo" => $isPromo
    ];
}

echo json_encode($products, JSON_UNESCAPED_UNICODE);
$conn->close();
?>