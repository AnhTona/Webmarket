<?php

require_once __DIR__ . '/../model/database.php';
require_once __DIR__ . '/../admin/html/Invoice_Generator.php';
require_once __DIR__ . '/EmailService.php';
session_start();

class MembershipService {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getDiscountRate(string $rank): float {
        $rates = [
            'Mới' => 0.00,
            'Bronze' => 0.02,
            'Silver' => 0.05,
            'Gold' => 0.10
        ];
        return $rates[$rank] ?? 0.00;
    }

    public function getRankBySpent(float $spent): string {
        $conn = $this->db->getConnection();
        $stmt = $conn->query("
            SELECT TenHang 
            FROM cauhinh_hang 
            WHERE {$spent} >= MinChiTieu 
            ORDER BY MinChiTieu DESC 
            LIMIT 1
        ");

        $result = $stmt->fetch_assoc();
        return $result ? $result['TenHang'] : 'Mới';
    }
}

class CalculationService {
    private const VAT_RATE = 0.08;

    public function calculateTotals(array $cart, float $discountRate): array {
        $subtotal = 0;

        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $vat = $subtotal * self::VAT_RATE;
        $discountAmount = $subtotal * $discountRate;
        $grandTotal = ($subtotal + $vat) - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'vat' => $vat,
            'discount' => $discountAmount,
            'grand_total' => round($grandTotal)
        ];
    }
}

class UserManager {
    private $db;
    private $membershipService;

    public function __construct($database) {
        $this->db = $database;
        $this->membershipService = new MembershipService($database);
    }

    public function findOrCreateByEmail(string $email, string $name = ''): array {
        $conn = $this->db->getConnection();

        // Tìm user theo email
        $stmt = $conn->prepare("
            SELECT MaNguoiDung, HoTen, Hang, TongChiTieu 
            FROM nguoidung 
            WHERE Email = ? 
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User đã tồn tại
            $row = $result->fetch_assoc();
            $rank = $row['Hang'] ?: 'Mới';
            $spent = (float)$row['TongChiTieu'];

            return [
                'id' => (int)$row['MaNguoiDung'],
                'name' => $row['HoTen'],
                'rank' => $rank,
                'spent' => $spent,
                'discount_rate' => $this->membershipService->getDiscountRate($rank)
            ];
        } else {
            // Tạo user mới
            return $this->createNewUser($conn, $email, $name);
        }
    }

    private function createNewUser($conn, string $email, string $name): array {
        $finalName = !empty($name) ? $name : 'Khách vãng lai';
        $username = explode('@', $email)[0] . '_' . time();
        $defaultPassword = password_hash('123456', PASSWORD_BCRYPT);

        $stmt = $conn->prepare("
            INSERT INTO nguoidung (Username, HoTen, Email, MatKhau, Hang, TongChiTieu, NgayTao) 
            VALUES (?, ?, ?, ?, 'Mới', 0, NOW())
        ");
        $stmt->bind_param("ssss", $username, $finalName, $email, $defaultPassword);
        $stmt->execute();

        return [
            'id' => $conn->insert_id,
            'name' => $finalName,
            'rank' => 'Mới',
            'spent' => 0.0,
            'discount_rate' => 0.0
        ];
    }
}

class OrderManager {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getOrCreateCart(int $userId): int {
        $conn = $this->db->getConnection();

        // Tìm giỏ hàng hiện tại
        $stmt = $conn->prepare("
            SELECT MaGioHang 
            FROM giohang 
            WHERE MaNguoiDung = ? 
            ORDER BY NgayTao DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return (int)$result->fetch_assoc()['MaGioHang'];
        }

        // Tạo giỏ hàng mới
        $stmt = $conn->prepare("INSERT INTO giohang (MaNguoiDung, NgayTao) VALUES (?, NOW())");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        return $conn->insert_id;
    }

    public function createOrder(int $userId, int $cartId, $tableNumber, float $totalAmount, string $note): int {
        $conn = $this->db->getConnection();

        // Lấy MaBan từ table_number
        $tableId = $this->getTableIdByNumber($tableNumber);

        $stmt = $conn->prepare("
            INSERT INTO donhang (MaNguoiDung, MaGioHang, MaBan, NgayDat, TongTien, TrangThai) 
            VALUES (?, ?, ?, NOW(), ?, 'PLACED')
        ");
        $stmt->bind_param("iiid", $userId, $cartId, $tableId, $totalAmount);
        $stmt->execute();

        return $conn->insert_id;
    }

    public function saveOrderItems(int $orderId, array $cart): void {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("
            INSERT INTO chitietdonhang (MaDonHang, MaSanPham, SoLuong, DonGia) 
            VALUES (?, ?, ?, ?)
        ");

        foreach ($cart as $item) {
            $productId = $this->getProductIdByName($item['name']);
            if ($productId) {
                $stmt->bind_param("iiid", $orderId, $productId, $item['quantity'], $item['price']);
                $stmt->execute();
            }
        }
    }

    private function getTableIdByNumber($tableNumber): ?int {
        // Trích xuất số từ chuỗi (vd: "Bàn #5" -> 5)
        preg_match('/\d+/', $tableNumber, $matches);
        return isset($matches[0]) ? (int)$matches[0] : null;
    }

    private function getProductIdByName(string $productName): ?int {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("SELECT MaSanPham FROM sanpham WHERE TenSanPham = ? LIMIT 1");
        $stmt->bind_param("s", $productName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return (int)$result->fetch_assoc()['MaSanPham'];
        }
        return null;
    }
}

class PaymentManager {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function recordPayment(int $orderId, string $method, float $amount): int {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("
            INSERT INTO thanhtoan (MaDonHang, PhuongThuc, SoTien, NgayThanhToan) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("isd", $orderId, $method, $amount);
        $stmt->execute();

        return $conn->insert_id;
    }
}

class OrderProcessor {
    private $db;
    private $userManager;
    private $orderManager;
    private $paymentManager;
    private $calculationService;
    private $emailService;
    public function __construct($database) {
        $this->db = $database;
        $this->userManager = new UserManager($database);
        $this->orderManager = new OrderManager($database);
        $this->paymentManager = new PaymentManager($database);
        $this->calculationService = new CalculationService();
        $this->emailService = new EmailService($database);
    }
    public function processOrder(array $postData): array {
        $orderData = $this->validateAndPrepareData($postData);

        if (!$orderData['is_valid']) {
            return ['success' => false, 'message' => $orderData['message']];
        }

        $conn = $this->db->getConnection();

        try {
            $conn->begin_transaction();

            // 1. Tìm hoặc tạo user theo Email
            $user = $this->userManager->findOrCreateByEmail(
                $orderData['email'],
                $orderData['customer_name']
            );

            // 2. Tạo hoặc lấy giỏ hàng
            $cartId = $this->orderManager->getOrCreateCart($user['id']);

            // 3. Tính toán tổng tiền
            $totals = $this->calculationService->calculateTotals(
                $orderData['cart'],
                $user['discount_rate']
            );

            // 4. Tạo đơn hàng
            $orderId = $this->orderManager->createOrder(
                $user['id'],
                $cartId,
                $orderData['table_number'],
                $totals['grand_total'],
                $orderData['customer_note']
            );

            // 5. Lưu chi tiết đơn hàng (sản phẩm)
            $this->orderManager->saveOrderItems($orderId, $orderData['cart']);

            // 6. Ghi nhận thanh toán (chỉ CASH cho demo)
            $this->paymentManager->recordPayment(
                $orderId,
                'CASH',
                $totals['grand_total']
            );

            // ✅ 7. TỰ ĐỘNG TẠO HÓA ĐƠN SAU KHI ĐẶT HÀNG THÀNH CÔNG
            try {
                InvoiceGenerator::generateInvoice($orderId);
                error_log("✅ Invoice auto-generated for order #{$orderId}");
            } catch (Exception $e) {
                error_log("⚠️ Failed to generate invoice for order #{$orderId}: " . $e->getMessage());
                // Không throw lỗi vì đơn hàng đã thành công
            }

            // ✅ 8. GỬI EMAIL XÁC NHẬN ĐƠN HÀNG
            try {
                $emailSent = $this->emailService->sendOrderConfirmation($orderId);
                if ($emailSent) {
                    error_log("✅ Email xác nhận đã gửi thành công cho đơn hàng #{$orderId}");
                } else {
                    error_log("⚠️ Không thể gửi email xác nhận cho đơn hàng #{$orderId}");
                }
            } catch (Exception $e) {
                error_log("❌ Lỗi khi gửi email xác nhận: " . $e->getMessage());
                // Không throw lỗi vì đơn hàng đã thành công, chỉ ghi log
            }

            $conn->commit();

            return [
                'success' => true,
                'message' => 'Đặt hàng thành công! Email xác nhận đã được gửi đến hộp thư của bạn.',
                'order_id' => $orderId,
                'redirect' => "/Webmarket/view/html/order_success.php?order_id=" . $orderId
            ];

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    private function validateAndPrepareData(array $postData): array {
        $email = strtolower(trim($postData['email'] ?? ''));
        $tableNumber = $postData['table_number'] ?? '';
        $customerName = $postData['customer_name'] ?? '';
        $customerNote = $postData['customer_note'] ?? '';
        $cartData = $postData['cart_data'] ?? '[]';

        $cart = json_decode($cartData, true);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'is_valid' => false,
                'message' => 'Email không hợp lệ.'
            ];
        }

        if (empty($tableNumber) || !is_array($cart) || count($cart) === 0) {
            return [
                'is_valid' => false,
                'message' => 'Vui lòng điền đủ thông tin và đảm bảo giỏ hàng không trống.'
            ];
        }

        return [
            'is_valid' => true,
            'email' => $email,
            'table_number' => $tableNumber,
            'customer_name' => $customerName,
            'customer_note' => $customerNote,
            'cart' => $cart
        ];
    }
}

// ============================================
// MAIN EXECUTION
// ============================================
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

try {
    $db = Database::getInstance();
    $orderProcessor = new OrderProcessor($db);
    $result = $orderProcessor->processOrder($_POST);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Order processing error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống khi tạo đơn hàng. Vui lòng thử lại.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}