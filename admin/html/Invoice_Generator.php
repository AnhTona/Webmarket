<?php
// admin/controller/Invoice_Generator.php
// Tự động tạo và lưu hóa đơn HTML

require_once __DIR__ . '/../../model/database.php';

class InvoiceGenerator
{
    private const INVOICE_DIR = __DIR__ . '/../invoices/';

    /**
     * Tạo hóa đơn HTML cho đơn hàng
     */
    public static function generateInvoice(int $orderId): bool
    {
        try {
            // Tạo thư mục nếu chưa có
            if (!file_exists(self::INVOICE_DIR)) {
                mkdir(self::INVOICE_DIR, 0755, true);
            }

            // Lấy thông tin đơn hàng
            $orderData = self::getOrderData($orderId);
            if (!$orderData) {
                return false;
            }

            // Tạo HTML hóa đơn
            $html = self::createInvoiceHTML($orderData);

            // Lưu file
            $filename = self::INVOICE_DIR . "invoice_{$orderId}.html";
            file_put_contents($filename, $html);

            return true;
        } catch (Exception $e) {
            error_log("Error generating invoice: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy đường dẫn file hóa đơn
     */
    public static function getInvoicePath(int $orderId): ?string
    {
        $filename = self::INVOICE_DIR . "invoice_{$orderId}.html";
        return file_exists($filename) ? $filename : null;
    }

    /**
     * Kiểm tra hóa đơn có tồn tại không
     */
    public static function invoiceExists(int $orderId): bool
    {
        $filename = self::INVOICE_DIR . "invoice_{$orderId}.html";
        return file_exists($filename);
    }

    /**
     * Lấy dữ liệu đơn hàng từ database
     */
    private static function getOrderData(int $orderId): ?array
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Lấy thông tin đơn hàng
        $sqlOrder = "
            SELECT 
                dh.MaDonHang,
                dh.NgayDat,
                dh.TongTien,
                dh.TrangThai,
                nd.HoTen,
                nd.Email,
                nd.SoDienThoai,
                nd.Hang,
                dh.MaBan,
                tt.PhuongThuc
            FROM donhang dh
            JOIN nguoidung nd ON nd.MaNguoiDung = dh.MaNguoiDung
            LEFT JOIN thanhtoan tt ON tt.MaDonHang = dh.MaDonHang
            WHERE dh.MaDonHang = ?
        ";
        $stmt = $conn->prepare($sqlOrder);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $orderInfo = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$orderInfo) {
            return null;
        }

        // Lấy chi tiết sản phẩm
        $sqlItems = "
            SELECT sp.TenSanPham, ct.SoLuong,
                   COALESCE(ct.DonGia, sp.Gia, 0) AS Gia,
                   (ct.SoLuong * COALESCE(ct.DonGia, sp.Gia, 0)) AS Tong
            FROM chitietdonhang ct
            JOIN sanpham sp ON sp.MaSanPham = ct.MaSanPham
            WHERE ct.MaDonHang = ?
        ";
        $stmt = $conn->prepare($sqlItems);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $rs = $stmt->get_result();

        $items = [];
        $subtotal = 0;
        while ($r = $rs->fetch_assoc()) {
            $itemTotal = (float)$r['Tong'];
            $subtotal += $itemTotal;
            $items[] = $r;
        }
        $stmt->close();

        // Tính toán
        $rank = $orderInfo['Hang'] ?? 'Mới';
        $totalAmount = (float)$orderInfo['TongTien'];

        $discountRates = [
            'Mới' => 0.00,
            'Bronze' => 0.02,
            'Silver' => 0.05,
            'Gold' => 0.10
        ];
        $discountRate = $discountRates[$rank] ?? 0.00;

        $calculatedSubtotal = $totalAmount / ((1 - $discountRate) * 1.08);
        $discountAmount = $calculatedSubtotal * $discountRate;
        $subtotalAfterDiscount = $calculatedSubtotal - $discountAmount;
        $vat = $subtotalAfterDiscount * 0.08;

        return [
            'order' => $orderInfo,
            'items' => $items,
            'calculations' => [
                'subtotal' => $calculatedSubtotal,
                'discount_rate' => $discountRate,
                'discount_amount' => $discountAmount,
                'vat' => $vat,
                'grand_total' => $totalAmount,
            ]
        ];
    }

    /**
     * Tạo HTML cho hóa đơn
     */
    private static function createInvoiceHTML(array $data): string
    {
        $order = $data['order'];
        $items = $data['items'];
        $calc = $data['calculations'];

        $fmt = new NumberFormatter('vi_VN', NumberFormatter::DECIMAL);

        $paymentMethods = [
            'CASH' => 'Tiền mặt',
            'TRANSFER' => 'Chuyển khoản',
            'CARD' => 'Thẻ ngân hàng',
            'BANKING' => 'Chuyển khoản ngân hàng',
            'EWALLET' => 'Ví điện tử',
            'MOMO' => 'Ví MoMo',
            'ZALOPAY' => 'ZaloPay'
        ];

        $statusMap = [
            'DRAFT' => 'Nháp',
            'PLACED' => 'Chờ xác nhận',
            'CONFIRMED' => 'Đang chuẩn bị',
            'SHIPPING' => 'Đang giao',
            'DONE' => 'Hoàn thành',
            'CANCELLED' => 'Đã hủy',
        ];

        $paymentMethod = $paymentMethods[$order['PhuongThuc'] ?? 'CASH'] ?? 'Tiền mặt';
        $status = $statusMap[$order['TrangThai']] ?? $order['TrangThai'];

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Hóa Đơn #<?= $order['MaDonHang'] ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Arial', sans-serif; background: #f5f5f5; padding: 20px; }
                .invoice-container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 3px solid #8f2c24; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #8f2c24; font-size: 32px; margin-bottom: 10px; }
                .header p { color: #666; font-size: 14px; }
                .invoice-info { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
                .info-section h3 { color: #8f2c24; font-size: 16px; margin-bottom: 15px; border-bottom: 2px solid #8f2c24; padding-bottom: 8px; }
                .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #eee; }
                .info-label { font-weight: 600; color: #333; min-width: 120px; }
                .info-value { color: #666; flex: 1; }
                .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                .items-table thead { background: #8f2c24; color: white; }
                .items-table th { padding: 12px; text-align: left; font-weight: 600; }
                .items-table td { padding: 12px; border-bottom: 1px solid #eee; }
                .items-table tbody tr:hover { background: #f9f9f9; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .totals { margin-left: auto; width: 400px; }
                .total-row { display: flex; justify-content: space-between; padding: 10px 15px; border-bottom: 1px solid #eee; }
                .total-row.discount { background: #fff3e0; color: #e65100; }
                .total-row.grand { background: #8f2c24; color: white; font-size: 18px; font-weight: bold; border-radius: 6px; margin-top: 10px; }
                .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; color: #666; font-size: 13px; }
                .status-badge { display: inline-block; padding: 6px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; }
                .status-placed { background: #fff3cd; color: #856404; }
                .status-confirmed { background: #cce5ff; color: #004085; }
                .status-done { background: #d4edda; color: #155724; }
                .status-cancelled { background: #f8d7da; color: #721c24; }
                @media print {
                    body { padding: 0; }
                    .invoice-container { box-shadow: none; }
                }
            </style>
        </head>
        <body>
        <div class="invoice-container">
            <!-- Header -->
            <div class="header">
                <h1>🍽️ HƯỚNG TRÀ ADMIN</h1>
                <p>Địa chỉ: 123 Đường ABC, Quận XYZ, TP.HCM</p>
                <p>Điện thoại: 0123-456-789 | Email: admin@huongtra.com</p>
            </div>

            <!-- Invoice Info -->
            <div class="invoice-info">
                <div class="info-section">
                    <h3>Thông Tin Đơn Hàng</h3>
                    <div class="info-row">
                        <span class="info-label">Mã đơn:</span>
                        <span class="info-value">#<?= htmlspecialchars($order['MaDonHang']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày đặt:</span>
                        <span class="info-value"><?= date('d/m/Y H:i', strtotime($order['NgayDat'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Bàn:</span>
                        <span class="info-value"><?= $order['MaBan'] ? 'Bàn ' . $order['MaBan'] : 'Mang về' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Trạng thái:</span>
                        <span class="info-value"><span class="status-badge status-<?= strtolower($order['TrangThai']) ?>"><?= $status ?></span></span>
                    </div>
                </div>

                <div class="info-section">
                    <h3>Thông Tin Khách Hàng</h3>
                    <div class="info-row">
                        <span class="info-label">Họ tên:</span>
                        <span class="info-value"><?= htmlspecialchars($order['HoTen']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?= htmlspecialchars($order['Email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Điện thoại:</span>
                        <span class="info-value"><?= htmlspecialchars($order['SoDienThoai'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Hạng TV:</span>
                        <span class="info-value"><?= htmlspecialchars($order['Hang']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Thanh toán:</span>
                        <span class="info-value"><?= $paymentMethod ?></span>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th class="text-center">Số lượng</th>
                    <th class="text-right">Đơn giá</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['TenSanPham']) ?></td>
                        <td class="text-center"><?= $item['SoLuong'] ?></td>
                        <td class="text-right"><?= $fmt->format($item['Gia']) ?> đ</td>
                        <td class="text-right"><?= $fmt->format($item['Tong']) ?> đ</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="totals">
                <div class="total-row">
                    <span>Tạm tính:</span>
                    <span><?= $fmt->format($calc['subtotal']) ?> đ</span>
                </div>
                <?php if ($calc['discount_amount'] > 0): ?>
                    <div class="total-row discount">
                        <span>Giảm giá (<?= ($calc['discount_rate'] * 100) ?>%):</span>
                        <span>- <?= $fmt->format($calc['discount_amount']) ?> đ</span>
                    </div>
                <?php endif; ?>
                <div class="total-row">
                    <span>VAT (8%):</span>
                    <span><?= $fmt->format($calc['vat']) ?> đ</span>
                </div>
                <div class="total-row grand">
                    <span>TỔNG THANH TOÁN:</span>
                    <span><?= $fmt->format($calc['grand_total']) ?> đ</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p><strong>Cảm ơn quý khách đã sử dụng dịch vụ!</strong></p>
                <p>Hóa đơn được tạo tự động bởi hệ thống - <?= date('d/m/Y H:i:s') ?></p>
            </div>
        </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}