<?php

require_once __DIR__ . '/../model/database.php';
session_start();
class MembershipService {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getDiscountRate(string $rank): float {
        $rates = [
            'Má»›i' => 0.00,
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
        return $result ? $result['TenHang'] : 'Má»›i';
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

        // TÃ¬m user theo email
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
            // User Ä‘Ã£ tá»“n táº¡i
            $row = $result->fetch_assoc();
            $rank = $row['Hang'] ?: 'Má»›i';
            $spent = (float)$row['TongChiTieu'];

            return [
                'id' => (int)$row['MaNguoiDung'],
                'name' => $row['HoTen'],
                'rank' => $rank,
                'spent' => $spent,
                'discount_rate' => $this->membershipService->getDiscountRate($rank)
            ];
        } else {
            // Táº¡o user má»›i
            return $this->createNewUser($conn, $email, $name);
        }
    }

    private function createNewUser($conn, string $email, string $name): array {
        $finalName = !empty($name) ? $name : 'KhÃ¡ch vÃ£ng lai';
        $username = explode('@', $email)[0] . '_' . time();
        $defaultPassword = password_hash('123456', PASSWORD_BCRYPT);

        $stmt = $conn->prepare("
            INSERT INTO nguoidung (Username, HoTen, Email, MatKhau, Hang, TongChiTieu, NgayTao) 
            VALUES (?, ?, ?, ?, 'Má»›i', 0, NOW())
        ");
        $stmt->bind_param("ssss", $username, $finalName, $email, $defaultPassword);
        $stmt->execute();

        return [
            'id' => $conn->insert_id,
            'name' => $finalName,
            'rank' => 'Má»›i',
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

        // TÃ¬m giá» hÃ ng hiá»‡n táº¡i
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

        // Táº¡o giá» hÃ ng má»›i
        $stmt = $conn->prepare("INSERT INTO giohang (MaNguoiDung, NgayTao) VALUES (?, NOW())");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        return $conn->insert_id;
    }

    public function createOrder(int $userId, int $cartId, $tableNumber, float $totalAmount, string $note): int {
        $conn = $this->db->getConnection();

        // Láº¥y MaBan tá»« table_number
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
        // TrÃ­ch xuáº¥t sá»‘ tá»« chuá»—i (vd: "BÃ n #5" -> 5)
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

    public function __construct($database) {
        $this->db = $database;
        $this->userManager = new UserManager($database);
        $this->orderManager = new OrderManager($database);
        $this->paymentManager = new PaymentManager($database);
        $this->calculationService = new CalculationService();
    }

    public function processOrder(array $postData): array {
        $orderData = $this->validateAndPrepareData($postData);

        if (!$orderData['is_valid']) {
            return ['success' => false, 'message' => $orderData['message']];
        }

        $conn = $this->db->getConnection();

        try {
            $conn->begin_transaction();

            // 1. TÃ¬m hoáº·c táº¡o user theo Email
            $user = $this->userManager->findOrCreateByEmail(
                $orderData['email'],
                $orderData['customer_name']
            );

            // 2. Táº¡o hoáº·c láº¥y giá» hÃ ng
            $cartId = $this->orderManager->getOrCreateCart($user['id']);

            // 3. TÃ­nh toÃ¡n tá»•ng tiá»n
            $totals = $this->calculationService->calculateTotals(
                $orderData['cart'],
                $user['discount_rate']
            );

            // 4. Táº¡o Ä‘Æ¡n hÃ ng
            $orderId = $this->orderManager->createOrder(
                $user['id'],
                $cartId,
                $orderData['table_number'],
                $totals['grand_total'],
                $orderData['customer_note']
            );

            // 5. LÆ°u chi tiáº¿t Ä‘Æ¡n hÃ ng (sáº£n pháº©m)
            $this->orderManager->saveOrderItems($orderId, $orderData['cart']);

            // 6. Ghi nháº­n thanh toÃ¡n (chá»‰ CASH cho demo)
            $this->paymentManager->recordPayment(
                $orderId,
                'CASH',
                $totals['grand_total']
            );

            $conn->commit();

            return [
                'success' => true,
                'message' => 'Äáº·t hÃ ng thÃ nh cÃ´ng!',
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
                'message' => 'Email khÃ´ng há»£p lá»‡.'
            ];
        }

        if (empty($tableNumber) || !is_array($cart) || count($cart) === 0) {
            return [
                'is_valid' => false,
                'message' => 'Vui lÃ²ng Ä‘iá»n Ä‘á»§ thÃ´ng tin vÃ  Ä‘áº£m báº£o giá» hÃ ng khÃ´ng trá»‘ng.'
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
    echo json_encode(['success' => false, 'message' => 'PhÆ°Æ¡ng thá»©c khÃ´ng há»£p lá»‡.']);
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
        'message' => 'Lá»—i há»‡ thá»‘ng khi táº¡o Ä‘Æ¡n hÃ ng. Vui lÃ²ng thá»­ láº¡i.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>