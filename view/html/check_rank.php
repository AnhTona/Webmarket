<?php
// check_rank.php (API kiểm tra hạng khách hàng)
header('Content-Type: application/json; charset=UTF-8');
// Đảm bảo đường dẫn đến file kết nối CSDL (db.php) là đúng
include __DIR__ . '/../../db.php'; 

$response = [
    'success' => true,
    'rank' => 'Mới',
    'rank_class' => 'rank-moi',
    'total_orders' => 0,
    'total_spent' => 0,
    'discount_rate' => 0.00,
    'name' => ''
];

if (!isset($_GET['phone']) || empty($_GET['phone'])) {
    // Trả về hạng mặc định nếu SĐT trống
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$phone = trim($_GET['phone']);

try {
    // 1. Tìm MaNguoiDung và HoTen dựa trên SoDienThoai
    $sql_user = "SELECT MaNguoiDung, HoTen FROM NguoiDung WHERE SoDienThoai = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $phone);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    $ma_nguoi_dung = null;
    $ho_ten = '';
    
    if ($row_user = $result_user->fetch_assoc()) {
        $ma_nguoi_dung = $row_user['MaNguoiDung'];
        $ho_ten = $row_user['HoTen'];
        $response['name'] = $ho_ten;
    } else {
        // Nếu SĐT chưa tồn tại trong NguoiDung, đây là khách hàng mới.
        // Dừng và trả về mặc định "Mới".
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Truy vấn CSDL để tính TỔNG TIÊU VÀ SỐ ĐƠN HÀNG THÀNH CÔNG (TrangThai = 'COMPLETED')
    $sql_orders = "SELECT 
                        COUNT(MaDonHang) AS total_orders, 
                        IFNULL(SUM(TongTien), 0) AS total_spent 
                    FROM DonHang 
                    WHERE MaNguoiDung = ? AND TrangThai = 'COMPLETED'";
            
    $stmt_orders = $conn->prepare($sql_orders);
    // Sử dụng MaNguoiDung đã tìm được để truy vấn
    $stmt_orders->bind_param("i", $ma_nguoi_dung); 
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    $data = $result_orders->fetch_assoc();

    $total_orders = (int)$data['total_orders'];
    $total_spent = (float)$data['total_spent'];

    // 3. Xử lý Logic Hạng khách hàng
    
    // Mặc định
    $rank_name = 'Mới';
    $rank_class = 'rank-moi';
    $discount_rate = 0.00; // 0%

    // Hạng Kim cương: ≥ 15 đơn HOẶC chi tiêu ≥ 10 triệu
    if ($total_orders >= 15 || $total_spent >= 10000000) {
        $rank_name = 'Kim cương';
        $rank_class = 'rank-kim-cuong';
        $discount_rate = 0.10; // 10%
    } 
    // Hạng Vàng: ≥ 7 đơn HOẶC chi tiêu ≥ 5 triệu
    else if ($total_orders >= 7 || $total_spent >= 5000000) {
        $rank_name = 'Vàng';
        $rank_class = 'rank-vang';
        $discount_rate = 0.05; // 5%
    } 
    // Hạng Bạc: ≥ 3 đơn HOẶC chi tiêu ≥ 2 triệu
    else if ($total_orders >= 3 || $total_spent >= 2000000) {
        $rank_name = 'Bạc';
        $rank_class = 'rank-bac';
        $discount_rate = 0.02; // 2%
    }

    // 4. Cập nhật hạng vào bảng NguoiDung
    // Cập nhật lại 2 cột mới thêm (HangKhachHang, TongChiTieu)
    $sql_update_user = "UPDATE NguoiDung 
                        SET HangKhachHang = ?, TongChiTieu = ?
                        WHERE MaNguoiDung = ?";
    $stmt_update = $conn->prepare($sql_update_user);
    $stmt_update->bind_param("sdi", $rank_name, $total_spent, $ma_nguoi_dung);
    $stmt_update->execute();

    // 5. Trả kết quả về Frontend
    $response['rank'] = $rank_name;
    $response['rank_class'] = $rank_class;
    $response['total_orders'] = $total_orders;
    $response['total_spent'] = $total_spent;
    $response['discount_rate'] = $discount_rate;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Lỗi truy vấn: " . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
$conn->close();
?>