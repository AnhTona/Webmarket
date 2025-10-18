<?php
// process_order.php
include __DIR__ . '/../../database.php'; // Kết nối cơ sở dữ liệu (Giả sử $conn là đối tượng kết nối)
session_start();

// Đặt header để trả về JSON
header('Content-Type: application/json');

// --- HÀM HỖ TRỢ (Server-side Validation và Calculation) ---

/**
 * Hàm mô phỏng logic tính toán giảm giá thực tế (Server-side validation)
 */
function getDiscountRateByPhone($conn, $phoneNumber) {
    // Thực tế: Lấy hạng thành viên từ DB dựa trên SĐT
    // Hiện tại: Dùng dữ liệu giả định như trong checkout.js để đồng bộ cho bài tập
    if (empty($phoneNumber) || strlen($phoneNumber) < 9) {
        return ['rank' => 'Mới', 'rate' => 0.00];
    }

    if (str_starts_with($phoneNumber, '090')) { 
        return ['rank' => 'Vàng', 'rate' => 0.05]; 
    } else if (str_starts_with($phoneNumber, '098')) { 
        return ['rank' => 'Kim cương', 'rate' => 0.10]; 
    }
    return ['rank' => 'Mới', 'rate' => 0.00];
}

/**
 * Hàm tính tổng tiền thực tế
 */
function calculateActualTotal($cart, $discountRate) {
    $subtotal = 0;
    foreach ($cart as $item) {
        // Cần đảm bảo item là object và có các thuộc tính cần thiết
        if (isset($item->price) && isset($item->quantity)) {
            $subtotal += $item->price * $item->quantity;
        }
    }
    
    $VAT_RATE = 0.08;
    $vat = $subtotal * $VAT_RATE;
    $totalBeforeDiscount = $subtotal + $vat;
    
    $discountAmount = $subtotal * $discountRate;
    $grandTotal = $totalBeforeDiscount - $discountAmount;
    
    return [
        'subtotal' => $subtotal,
        'vat' => $vat,
        'discount' => $discountAmount,
        'grand_total' => round($grandTotal) // Làm tròn
    ];
}

// ----------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

// 1. Lấy và kiểm tra dữ liệu
$table_number = $_POST['table_number'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$customer_name = $_POST['customer_name'] ?? '';
$customer_note = $_POST['customer_note'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cash';
$cart_data = $_POST['cart_data'] ?? '[]';

$cart = json_decode($cart_data);

if (empty($table_number) || empty($phone_number) || count($cart) === 0) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đủ thông tin bắt buộc và đảm bảo giỏ hàng không trống.']);
    exit;
}

try {
    // Bắt đầu Transaction
    $conn->begin_transaction();
    
    // 2. Xử lý thông tin Khách hàng (UPSERT: Tìm kiếm & Cập nhật/Chèn)
    $membership = getDiscountRateByPhone($conn, $phone_number);
    $rank = $membership['rank'];

    $stmt_cust = $conn->prepare("SELECT customer_id, customer_name FROM customers WHERE phone = ?");
    $stmt_cust->bind_param("s", $phone_number);
    $stmt_cust->execute();
    $result_cust = $stmt_cust->get_result();
    $customer_id = null;
    
    if ($result_cust->num_rows > 0) {
        // Khách hàng đã tồn tại (UPDATE)
        $customer_row = $result_cust->fetch_assoc();
        $customer_id = $customer_row['customer_id'];
        
        // Giữ tên đã lưu nếu SĐT là thành viên, hoặc dùng tên mới nếu tên khách hàng gửi lên không trống
        $final_name = !empty($customer_row['customer_name']) ? $customer_row['customer_name'] : (!empty($customer_name) ? $customer_name : 'Khách vãng lai');

        $stmt_update = $conn->prepare("UPDATE customers SET rank = ?, customer_name = ? WHERE customer_id = ?");
        $stmt_update->bind_param("ssi", $rank, $final_name, $customer_id);
        $stmt_update->execute();
    } else {
        // Khách hàng mới (INSERT)
        $final_name = !empty($customer_name) ? $customer_name : 'Khách vãng lai';
        // Giả sử bảng customers có cột 'rank' và 'status' mặc định là 'Mới'/'Hoạt động'
        $stmt_insert = $conn->prepare("INSERT INTO customers (customer_name, phone, rank, created_at) VALUES (?, ?, ?, NOW())");
        $stmt_insert->bind_param("sss", $final_name, $phone_number, $rank);
        $stmt_insert->execute();
        $customer_id = $conn->insert_id;
    }
    
    // 3. Tính toán lại tổng tiền (Server-side validation)
    $totals = calculateActualTotal($cart, $membership['rate']);
    
    // 4. Xử lý Upload Biên lai (Nếu có)
    $receipt_path = null;
    if ($payment_method === 'transfer' && isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../uploads/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION);
        $file_name = 'receipt_' . time() . '_' . $customer_id . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['receipt_file']['tmp_name'], $target_file)) {
            $receipt_path = 'uploads/receipts/' . $file_name; // Đường dẫn tương đối để lưu DB
        }
    }

    // 5. Lưu Order vào bảng `orders`
    $order_status = ($payment_method === 'cash' || $payment_method === 'momo') ? 'Waiting' : 'Pending Payment'; // Waiting cho thanh toán tại chỗ, Pending Payment cho chuyển khoản
    $order_code = 'HD' . date('YmdHis') . $customer_id; // Mã đơn hàng

    // Giả sử bảng orders có các cột: order_code, customer_id, table_number, total_amount, discount_amount, vat_amount, payment_method, customer_note, receipt_path, status, created_at
    $stmt_order = $conn->prepare("INSERT INTO orders (order_code, customer_id, table_number, total_amount, discount_amount, vat_amount, payment_method, customer_note, receipt_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt_order->bind_param("sisidddsss", 
        $order_code, 
        $customer_id, 
        $table_number, 
        $totals['grand_total'], 
        $totals['discount'], 
        $totals['vat'], 
        $payment_method, 
        $customer_note, 
        $receipt_path, 
        $order_status
    );
    $stmt_order->execute();
    $order_id = $conn->insert_id;

    // 6. Lưu chi tiết Order vào bảng `order_items`
    // Giả sử bảng order_items có các cột: order_id, product_name, price, quantity, note
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_name, price, quantity, note) VALUES (?, ?, ?, ?, ?)");
    foreach ($cart as $item) {
        $stmt_item->bind_param("isdis", 
            $order_id, 
            $item->name, 
            $item->price, 
            $item->quantity, 
            $item->note
        );
        $stmt_item->execute();
    }

    // 7. Hoàn tất Transaction
    $conn->commit();

    // 8. Trả về kết quả thành công
    echo json_encode(['success' => true, 'message' => 'Đặt hàng thành công!', 'order_code' => $order_code]);

} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    error_log("Order processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống khi tạo đơn hàng. Vui lòng thử lại.', 'error' => $e->getMessage()]);
}

$conn->close();
?>