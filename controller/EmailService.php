<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

class EmailService {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Gửi email xác nhận đơn hàng
     */
    public function sendOrderConfirmation(int $orderId): bool {
        try {
            // Lấy thông tin đơn hàng
            $orderDetails = $this->getOrderDetails($orderId);

            if (!$orderDetails) {
                error_log("Không tìm thấy đơn hàng #$orderId");
                return false;
            }

            // Khởi tạo PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'trananhhung12345@gmail.com';
            $mail->Password   = 'mihr fyxi kzoj bcgx';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Người gửi & người nhận
            $mail->setFrom('huynhnguyen56392@gmail.com', 'Hương Trà Restaurant');
            $mail->addAddress($orderDetails['customer_email'], $orderDetails['customer_name']);

            // Nội dung email
            $mail->isHTML(true);
            $mail->Subject = "Hóa đơn bán hàng #" . $orderId . " - Hương Trà Restaurant";

            // Tạo HTML body
            $mail->Body = $this->generateEmailHTML($orderDetails, $orderId);

            // Gửi email
            $mail->send();
            error_log("✅ Email đã gửi thành công cho đơn hàng #$orderId");
            return true;

        } catch (PHPMailerException $e) {
            error_log("❌ Lỗi gửi email đơn hàng #$orderId: " . $mail->ErrorInfo . " | " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("❌ Lỗi không xác định khi gửi email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy thông tin chi tiết đơn hàng
     */
    private function getOrderDetails(int $orderId): ?array {
        $conn = $this->db->getConnection();

        // Query lấy thông tin đơn hàng
        $stmt = $conn->prepare("
            SELECT 
                dh.MaDonHang,
                dh.NgayDat,
                dh.TongTien,
                dh.TrangThai,
                dh.MaBan,
                nd.Email as customer_email,
                nd.HoTen as customer_name,
                nd.SoDienThoai as customer_phone,
                nd.Hang as customer_rank,
                tt.PhuongThuc as payment_method
            FROM donhang dh
            JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
            LEFT JOIN thanhtoan tt ON tt.MaDonHang = dh.MaDonHang
            WHERE dh.MaDonHang = ?
        ");

        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        $order = $result->fetch_assoc();

        // Lấy danh sách sản phẩm trong đơn
        $order['items'] = $this->getOrderItems($orderId);

        // Tính toán các khoản
        $subtotal = 0;
        foreach ($order['items'] as $item) {
            $subtotal += $item['total_price'];
        }

        $discountRate = $this->getDiscountRate($order['customer_rank'] ?? 'Mới');
        $discountAmount = $subtotal * $discountRate;
        $vat = $subtotal * 0.08;
        $grandTotal = ($subtotal + $vat) - $discountAmount;

        $order['calculations'] = [
            'subtotal' => $subtotal,
            'discount_rate' => $discountRate,
            'discount_amount' => $discountAmount,
            'vat' => $vat,
            'grand_total' => $grandTotal
        ];

        return $order;
    }

    /**
     * Lấy danh sách sản phẩm trong đơn hàng
     */
    private function getOrderItems(int $orderId): array {
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("
            SELECT 
                sp.TenSanPham as product_name,
                ct.SoLuong as quantity,
                ct.DonGia as unit_price,
                (ct.SoLuong * ct.DonGia) as total_price
            FROM chitietdonhang ct
            JOIN sanpham sp ON ct.MaSanPham = sp.MaSanPham
            WHERE ct.MaDonHang = ?
        ");

        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        return $items;
    }

    /**
     * Lấy tỷ lệ giảm giá theo hạng thành viên
     */
    private function getDiscountRate(string $rank): float {
        $rates = [
            'Mới' => 0.00,
            'Bronze' => 0.02,
            'Silver' => 0.05,
            'Gold' => 0.10
        ];
        return $rates[$rank] ?? 0.00;
    }

    /**
     * Lấy tên phương thức thanh toán
     */
    private function getPaymentMethodName(?string $method): string {
        $methods = [
            'CASH' => 'Tiền mặt',
            'CARD' => 'Thẻ ngân hàng',
            'BANKING' => 'Chuyển khoản ngân hàng',
            'EWALLET' => 'Ví điện tử'
        ];
        return $methods[$method ?? 'CASH'] ?? 'Tiền mặt';
    }

    /**
     * Tạo HTML template cho email - Layout giống hóa đơn
     */
    private function generateEmailHTML(array $orderDetails, int $orderId): string {
        $customerName = htmlspecialchars($orderDetails['customer_name']);
        $customerEmail = htmlspecialchars($orderDetails['customer_email']);
        $customerPhone = htmlspecialchars($orderDetails['customer_phone'] ?? 'N/A');
        $orderDate = date('d/m/Y H:i', strtotime($orderDetails['NgayDat']));
        $tableName = $orderDetails['MaBan'] ? 'Bàn ' . $orderDetails['MaBan'] : '-';
        $rank = htmlspecialchars($orderDetails['customer_rank'] ?? 'Mới');
        $paymentMethod = $this->getPaymentMethodName($orderDetails['payment_method'] ?? null);

        $calc = $orderDetails['calculations'];

        // Format số tiền
        $fmt = function($num) {
            return number_format($num, 0, ',', '.');
        };

        // Tạo HTML cho danh sách sản phẩm
        $itemsHTML = '';
        $stt = 1;
        foreach ($orderDetails['items'] as $item) {
            $itemName = htmlspecialchars($item['product_name']);
            $quantity = (int)$item['quantity'];
            $unitPrice = $fmt($item['unit_price']);
            $totalPrice = $fmt($item['total_price']);

            $itemsHTML .= "
            <tr>
                <td style='padding: 10px 8px; border-bottom: 1px solid #ddd; text-align: center;'>{$stt}</td>
                <td style='padding: 10px 8px; border-bottom: 1px solid #ddd;'>{$itemName}</td>
                <td style='padding: 10px 8px; border-bottom: 1px solid #ddd; text-align: center;'>{$quantity}</td>
                <td style='padding: 10px 8px; border-bottom: 1px solid #ddd; text-align: right;'>{$unitPrice} đ</td>
                <td style='padding: 10px 8px; border-bottom: 1px solid #ddd; text-align: right; font-weight: bold;'>{$totalPrice} đ</td>
            </tr>";
            $stt++;
        }

        // Dòng giảm giá (nếu có)
        $discountHTML = '';
        if ($calc['discount_amount'] > 0) {
            $discountPercent = ($calc['discount_rate'] * 100);
            $discountHTML = "
            <tr>
                <td colspan='4' style='padding: 8px; text-align: right; border-bottom: 1px solid #ddd;'>
                    Giảm giá (Hạng {$rank} - {$discountPercent}%):
                </td>
                <td style='padding: 8px; text-align: right; border-bottom: 1px solid #ddd;'>
                    - {$fmt($calc['discount_amount'])} đ
                </td>
            </tr>";
        }

        // Template HTML giống layout hóa đơn
        $html = "
<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Hóa Đơn #{$orderId}</title>
</head>
<body style='margin: 0; padding: 20px; font-family: Arial, sans-serif; background-color: #f5f5f5;'>
    <div style='max-width: 800px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>
        
        <!-- Header -->
        <div style='text-align: center; border-bottom: 3px solid #8f2c24; padding-bottom: 20px; margin-bottom: 30px;'>
            <h1 style='color: #8f2c24; font-size: 28px; margin: 0 0 10px 0; text-transform: uppercase;'>HÓA ĐƠN BÁN HÀNG</h1>
            <div style='font-size: 18px; font-weight: bold; color: #4d0702; margin-bottom: 5px;'>HƯƠNG TRÀ RESTAURANT</div>
            <div style='font-size: 12px; color: #666; line-height: 1.8;'>
                Địa chỉ: 88 Phan Xích Long, P.7, Q.Phú Nhuận, TPHCM<br>
                Điện thoại: 1800 8287 | Email: contact@huongtra.com
            </div>
        </div>

        <!-- Thông tin đơn hàng và khách hàng (2 cột) -->
        <table style='width: 100%; margin-bottom: 30px; border-collapse: collapse;'>
            <tr>
                <td style='width: 50%; vertical-align: top; padding-right: 15px;'>
                    <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #8f2c24;'>
                        <h3 style='color: #8f2c24; font-size: 14px; margin: 0 0 10px 0; text-transform: uppercase;'>THÔNG TIN ĐỚN HÀNG</h3>
                        <table style='width: 100%; font-size: 13px;'>
                            <tr>
                                <td style='padding: 5px 0; font-weight: bold; color: #555; width: 120px;'>Mã đơn hàng:</td>
                                <td style='padding: 5px 0; color: #333;'>#{$orderId}</td>
                            </tr>
                            <tr>
                                <td style='padding: 5px 0; font-weight: bold; color: #555;'>Ngày đặt:</td>
                                <td style='padding: 5px 0; color: #333;'>{$orderDate}</td>
                            </tr>
                            <tr>
                                <td style='padding: 5px 0; font-weight: bold; color: #555;'>Bàn:</td>
                                <td style='padding: 5px 0; color: #333;'>{$tableName}</td>
                            </tr>
                            <tr>
                                <td style='padding: 5px 0; font-weight: bold; color: #555;'>Thanh toán:</td>
                                <td style='padding: 5px 0; color: #333;'>{$paymentMethod}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style='width: 50%; vertical-align: top; padding-left: 15px;'>
                    <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #8f2c24;'>
                        <h3 style='color: #8f2c24; font-size: 14px; margin: 0 0 10px 0; text-transform: uppercase;'>THÔNG TIN KHÁCH HÀNG</h3>
                        <table style='width: 100%; font-size: 13px;'>
                            <tr>
                                <td style='padding: 5px 0; font-weight: bold; color: #555; width: 120px;'>Họ tên:</td>
                                <td style='padding: 5px 0; color: #333;'>{$customerName}</td>
                            </tr>
                            <tr>
                                <td style='padding: 5px 0; font-weight: bold; color: #555;'>Email:</td>
                                <td style='padding: 5px 0; color: #333;'>{$customerEmail}</td>
                            </tr>
                            <tr>
                                <td style='padding: 5px 0; font-weight: bold; color: #555;'>Số điện thoại:</td>
                                <td style='padding: 5px 0; color: #333;'>{$customerPhone}</td>
                            </tr>
                            <tr>
                                <td style='padding: 5px 0; font-weight: bold; color: #555;'>Hạng thành viên:</td>
                                <td style='padding: 5px 0; color: #333;'>{$rank}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Bảng sản phẩm -->
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <thead>
                <tr style='background: #8f2c24; color: white;'>
                    <th style='padding: 12px 8px; text-align: center; font-size: 13px; text-transform: uppercase; width: 50px;'>STT</th>
                    <th style='padding: 12px 8px; text-align: left; font-size: 13px; text-transform: uppercase;'>SẢN PHẨM</th>
                    <th style='padding: 12px 8px; text-align: center; font-size: 13px; text-transform: uppercase; width: 80px;'>SL</th>
                    <th style='padding: 12px 8px; text-align: right; font-size: 13px; text-transform: uppercase; width: 120px;'>ĐƠN GIÁ</th>
                    <th style='padding: 12px 8px; text-align: right; font-size: 13px; text-transform: uppercase; width: 130px;'>THÀNH TIỀN</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHTML}
            </tbody>
        </table>

        <!-- Bảng tính toán -->
        <table style='width: 100%; border-collapse: collapse; margin-top: 30px;'>
            <tr>
                <td colspan='4' style='padding: 8px; text-align: right; font-size: 14px; border-bottom: 1px solid #ddd;'>Tạm tính:</td>
                <td style='padding: 8px; text-align: right; font-weight: bold; font-size: 14px; border-bottom: 1px solid #ddd;'>{$fmt($calc['subtotal'])} đ</td>
            </tr>
            {$discountHTML}
            <tr>
                <td colspan='4' style='padding: 8px; text-align: right; font-size: 14px; border-bottom: 1px solid #ddd;'>VAT (8%):</td>
                <td style='padding: 8px; text-align: right; font-weight: bold; font-size: 14px; border-bottom: 1px solid #ddd;'>{$fmt($calc['vat'])} đ</td>
            </tr>
            <tr style='background: #8f2c24; color: white;'>
                <td colspan='4' style='padding: 15px 8px; text-align: right; font-size: 18px; font-weight: bold;'>TỔNG THANH TOÁN:</td>
                <td style='padding: 15px 8px; text-align: right; font-size: 18px; font-weight: bold;'>{$fmt($calc['grand_total'])} đ</td>
            </tr>
        </table>

        <!-- Footer -->
        <div style='margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd; text-align: center; font-size: 12px; color: #666;'>
            <p style='margin: 10px 0;'><strong>Cảm ơn quý khách đã sử dụng dịch vụ!</strong></p>
            <p style='margin: 10px 0;'>Hóa đơn này được tạo tự động bởi hệ thống.</p>
            
            <table style='width: 100%; margin-top: 40px;'>
                <tr>
                    <td style='width: 50%; text-align: center;'>
                        <div style='display: inline-block;'>
                            <div style='width: 200px; border-top: 1px solid #333; margin: 50px auto 10px;'></div>
                            <strong>Khách hàng</strong>
                        </div>
                    </td>
                    <td style='width: 50%; text-align: center;'>
                        <div style='display: inline-block;'>
                            <div style='width: 200px; border-top: 1px solid #333; margin: 50px auto 10px;'></div>
                            <strong>Thu ngân</strong>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>";

        return $html;
    }
}