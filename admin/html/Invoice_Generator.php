<?php
// admin/html/Invoice_Generator.php
require_once __DIR__ . '/../../model/database.php';

class InvoiceGenerator
{
    /**
     * Tạo và lưu hóa đơn HTML vào database
     */
    public static function generateInvoice(int $orderId): bool
    {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            // Lấy dữ liệu đơn hàng
            $orderData = self::getOrderData($orderId);
            if (!$orderData) {
                error_log("Order #{$orderId} not found");
                return false;
            }

            // Tạo HTML hóa đơn
            $html = self::generateInvoiceHTML($orderData);

            // Kiểm tra xem đã có hóa đơn chưa
            $stmt = $conn->prepare("SELECT MaHoaDon FROM hoadon WHERE MaDonHang = ?");
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Cập nhật hóa đơn cũ
                $stmt = $conn->prepare("UPDATE hoadon SET NoiDungHTML = ?, NgayTao = NOW() WHERE MaDonHang = ?");
                $stmt->bind_param('si', $html, $orderId);
            } else {
                // Tạo hóa đơn mới
                $stmt = $conn->prepare("INSERT INTO hoadon (MaDonHang, NoiDungHTML, NgayTao) VALUES (?, ?, NOW())");
                $stmt->bind_param('is', $orderId, $html);
            }

            $success = $stmt->execute();

            if ($success) {
                error_log("✅ Invoice generated successfully for order #{$orderId}");
            } else {
                error_log("❌ Failed to save invoice for order #{$orderId}");
            }

            return $success;

        } catch (Exception $e) {
            error_log("Error generating invoice: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem hóa đơn đã tồn tại chưa
     */
    public static function invoiceExists(int $orderId): bool
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT MaHoaDon FROM hoadon WHERE MaDonHang = ? LIMIT 1");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    /**
     * Lấy HTML hóa đơn từ database
     */
    public static function getInvoiceHTML(int $orderId): ?string
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT NoiDungHTML FROM hoadon WHERE MaDonHang = ? LIMIT 1");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['NoiDungHTML'];
        }

        return null;
    }

    /**
     * Lấy dữ liệu đơn hàng từ database
     */
    private static function getOrderData(int $orderId): ?array
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Lấy thông tin đơn hàng
        $stmt = $conn->prepare("
            SELECT 
                dh.MaDonHang,
                dh.NgayDat,
                dh.TongTien,
                dh.TrangThai,
                dh.MaBan,
                nd.HoTen AS KhachHang,
                nd.Email,
                nd.SoDienThoai,
                nd.Hang AS HangTV,
                tt.PhuongThuc
            FROM donhang dh
            JOIN nguoidung nd ON nd.MaNguoiDung = dh.MaNguoiDung
            LEFT JOIN thanhtoan tt ON tt.MaDonHang = dh.MaDonHang
            WHERE dh.MaDonHang = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        $order = $result->fetch_assoc();

        // Lấy chi tiết sản phẩm
        $stmt = $conn->prepare("
            SELECT 
                sp.TenSanPham,
                ct.SoLuong,
                ct.DonGia,
                (ct.SoLuong * ct.DonGia) AS ThanhTien
            FROM chitietdonhang ct
            JOIN sanpham sp ON sp.MaSanPham = ct.MaSanPham
            WHERE ct.MaDonHang = ?
        ");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $itemsResult = $stmt->get_result();

        $items = [];
        while ($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }

        $order['items'] = $items;

        // Tính toán các khoản phí
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['ThanhTien'];
        }

        // Lấy tỷ lệ giảm giá theo hạng
        $discountRate = self::getDiscountRate($order['HangTV'] ?? 'Mới');
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
     * Lấy tỷ lệ giảm giá theo hạng thành viên
     */
    private static function getDiscountRate(string $rank): float
    {
        $rates = [
                'Mới' => 0.00,
                'Bronze' => 0.02,
                'Silver' => 0.05,
                'Gold' => 0.10
        ];
        return $rates[$rank] ?? 0.00;
    }

    /**
     * Tên phương thức thanh toán
     */
    private static function getPaymentMethodName(?string $method): string
    {
        $methods = [
                'CASH' => 'Tiền mặt',
                'CARD' => 'Thẻ ngân hàng',
                'BANKING' => 'Chuyển khoản ngân hàng',
                'EWALLET' => 'Ví điện tử'
        ];
        return $methods[$method ?? 'CASH'] ?? 'Tiền mặt';
    }

    /**
     * Tạo HTML cho hóa đơn (in được, responsive)
     */
    private static function generateInvoiceHTML(array $data): string
    {
        $orderId = $data['MaDonHang'];
        $ngayDat = date('d/m/Y H:i', strtotime($data['NgayDat']));
        $khachHang = htmlspecialchars($data['KhachHang']);
        $email = htmlspecialchars($data['Email'] ?? '');
        $sdt = htmlspecialchars($data['SoDienThoai'] ?? 'N/A');
        $hangTV = htmlspecialchars($data['HangTV'] ?? 'Mới');
        $ban = $data['MaBan'] ? 'Bàn ' . $data['MaBan'] : '-';
        $phuongThuc = self::getPaymentMethodName($data['PhuongThuc'] ?? null);

        $calc = $data['calculations'];
        $items = $data['items'];

        // Format số tiền
        $fmt = function($num) {
            return number_format($num, 0, ',', '.');
        };

        // Tạo danh sách sản phẩm
        $itemsHTML = '';
        $stt = 1;
        foreach ($items as $item) {
            $itemsHTML .= '<tr>
                <td style="text-align: center;">' . $stt++ . '</td>
                <td>' . htmlspecialchars($item['TenSanPham']) . '</td>
                <td style="text-align: center;">' . $item['SoLuong'] . '</td>
                <td style="text-align: right;">' . $fmt($item['DonGia']) . ' đ</td>
                <td style="text-align: right; font-weight: bold;">' . $fmt($item['ThanhTien']) . ' đ</td>
            </tr>';
        }

        // Dòng giảm giá (nếu có)
        // Dòng giảm giá (nếu có) – dùng đúng 2 cột như Tạm tính/VAT để căn thẳng hàng
        $discountHTML = '';
        if ($calc['discount_amount'] > 0) {
            $discountPercent = ($calc['discount_rate'] * 100);
            $discountHTML = '<tr class="summary-row discount-row">
        <td style="text-align: right;">Giảm giá (Hạng ' . $hangTV . ' - ' . $discountPercent . '%):</td>
        <td style="text-align: right;">- ' . $fmt($calc['discount_amount']) . ' đ</td>
    </tr>';
        }


        $html = '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa Đơn #' . $orderId . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #8f2c24;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #8f2c24;
            font-size: 28px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .header .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #4d0702;
            margin-bottom: 5px;
        }
        .header .company-info {
            font-size: 12px;
            color: #666;
            line-height: 1.8;
        }
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-box {
            background: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #8f2c24;
        }
        .info-box h3 {
            color: #8f2c24;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .info-row {
            display: flex;
            padding: 5px 0;
            font-size: 13px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #8f2c24;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        tr:hover td {
            background: #f9f9f9;
        }
        .summary-table {
            margin-top: 30px;
            border: none;
        }
        .summary-table td {
            border: none;
            padding: 8px;
        }
        .summary-row {
            font-size: 14px;
        }
        .total-row {
            background: #8f2c24;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        .total-row td {
            padding: 15px 8px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .footer .signature {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
        }
        .signature div {
            text-align: center;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin: 50px auto 10px;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #8f2c24;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .print-button:hover {
            background: #6d1f18;
        }
        @media print {
            body { 
                background: white; 
                padding: 0; 
            }
            .invoice-container {
                box-shadow: none;
                padding: 0;
            }
            .print-button {
                display: none;
            }
        }
        @media (max-width: 600px) {
            .info-section {
                grid-template-columns: 1fr;
            }
            .invoice-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ In Hóa Đơn</button>
    
    <div class="invoice-container">
        <div class="header">
            <h1>Hóa Đơn Bán Hàng</h1>
            <div class="company-name">HƯƠNG TRÀ RESTAURANT</div>
            <div class="company-info">
                Địa chỉ: 88 Phan Xích Long, P.7, Q.Phú Nhuận, TPHCM<br>
                Điện thoại: 1800 8287 | Email: contact@huongtra.com
            </div>
        </div>

        <div class="info-section">
            <div class="info-box">
                <h3>Thông Tin Đơn Hàng</h3>
                <div class="info-row">
                    <span class="info-label">Mã đơn hàng:</span>
                    <span class="info-value">#' . $orderId . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ngày đặt:</span>
                    <span class="info-value">' . $ngayDat . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bàn:</span>
                    <span class="info-value">' . $ban . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Thanh toán:</span>
                    <span class="info-value">' . $phuongThuc . '</span>
                </div>
            </div>

            <div class="info-box">
                <h3>Thông Tin Khách Hàng</h3>
                <div class="info-row">
                    <span class="info-label">Họ tên:</span>
                    <span class="info-value">' . $khachHang . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">' . $email . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số điện thoại:</span>
                    <span class="info-value">' . $sdt . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Hạng thành viên:</span>
                    <span class="info-value">' . $hangTV . '</span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">STT</th>
                    <th>Sản Phẩm</th>
                    <th style="width: 80px; text-align: center;">SL</th>
                    <th style="width: 120px; text-align: right;">Đơn Giá</th>
                    <th style="width: 130px; text-align: right;">Thành Tiền</th>
                </tr>
            </thead>
            <tbody>
                ' . $itemsHTML . '
            </tbody>
        </table>

        <table class="summary-table">
            <tr class="summary-row">
                <td style="text-align: right; width: 70%;">Tạm tính:</td>
                <td style="text-align: right; font-weight: bold;">' . $fmt($calc['subtotal']) . ' đ</td>
            </tr>
            ' . $discountHTML . '
            <tr class="summary-row">
                <td style="text-align: right;">VAT (8%):</td>
                <td style="text-align: right; font-weight: bold;">' . $fmt($calc['vat']) . ' đ</td>
            </tr>
            <tr class="total-row">
                <td style="text-align: right;">TỔNG THANH TOÁN:</td>
                <td style="text-align: right;">' . $fmt($calc['grand_total']) . ' đ</td>
            </tr>
        </table>

        <div class="footer">
            <p><strong>Cảm ơn quý khách đã sử dụng dịch vụ!</strong></p>
            <p>Hóa đơn này được tạo tự động bởi hệ thống.</p>
            
            <div class="signature">
                <div>
                    <div class="signature-line"></div>
                    <strong>Khách hàng</strong>
                </div>
                <div>
                    <div class="signature-line"></div>
                    <strong>Thu ngân</strong>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';

        return $html;
    }
}