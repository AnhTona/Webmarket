<?php
// controller/OrderSuccessController.php

require_once __DIR__ . '/../model/database.php';

class OrderSuccessController {
    private $db;
    private const VAT_RATE = 0.08;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Lấy thông tin đơn hàng chi tiết
     */
    public function getOrderDetails(int $orderId): ?array {
        if ($orderId <= 0) {
            return null;
        }

        $conn = $this->db->getConnection();

        // Lấy thông tin đơn hàng
        $stmt = $conn->prepare("
            SELECT
                d.MaDonHang,
                d.NgayDat,
                d.TongTien,
                d.TrangThai,
                u.HoTen,
                u.Email,
                u.Hang,
                u.TongChiTieu,
                b.MaBan,
                t.SoTien,
                t.PhuongThuc,
                t.NgayThanhToan
            FROM donhang d
            JOIN nguoidung u ON d.MaNguoiDung = u.MaNguoiDung
            LEFT JOIN bantrongquan b ON d.MaBan = b.MaBan
            LEFT JOIN thanhtoan t ON d.MaDonHang = t.MaDonHang
            WHERE d.MaDonHang = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            return null;
        }

        // Lấy chi tiết sản phẩm
        $stmt = $conn->prepare("
            SELECT
                ct.SoLuong,
                ct.DonGia,
                sp.TenSanPham
            FROM chitietdonhang ct
            JOIN sanpham sp ON ct.MaSanPham = sp.MaSanPham
            WHERE ct.MaDonHang = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Tính toán chi tiết từ TongTien (tính ngược)
        $calculations = $this->calculateFromTotal($order['TongTien'], $order['Hang']);

        return [
            'order' => $order,
            'items' => $items,
            'calculations' => $calculations
        ];
    }

    /**
     * Tính ngược từ TongTien để có subtotal, discount, VAT
     */
    private function calculateFromTotal(float $totalAmount, string $rank): array {
        $discountRate = $this->getDiscountRate($rank);

        // Công thức ngược: TongTien = (Subtotal * (1 - discount_rate) * 1.08)
        // => Subtotal = TongTien / ((1 - discount_rate) * 1.08)
        $subtotal = $totalAmount / ((1 - $discountRate) * 1.08);

        $discountAmount = $subtotal * $discountRate;
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        $vat = $subtotalAfterDiscount * self::VAT_RATE;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_rate' => $discountRate,
            'discount_amount' => round($discountAmount, 2),
            'vat' => round($vat, 2),
            'grand_total' => round($totalAmount, 2)
        ];
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
     * Format số tiền VND
     */
    public function formatMoney(float $amount): string {
        return number_format($amount, 0, ',', '.') . ' đ';
    }

    /**
     * Format ngày giờ
     */
    public function formatDateTime(string $datetime): string {
        return date('d/m/Y H:i', strtotime($datetime));
    }

    /**
     * Lấy tên phương thức thanh toán
     */
    public function getPaymentMethodName(string $method): string {
        $methods = [
            'CASH' => 'Tiền mặt',
            'TRANSFER' => 'Chuyển khoản',
            'MOMO' => 'Ví MoMo',
            'ZALOPAY' => 'ZaloPay',
            'CARD' => 'Thẻ ngân hàng',
            'BANKING' => 'Chuyển khoản ngân hàng',
            'EWALLET' => 'Ví điện tử'
        ];
        return $methods[$method] ?? 'Tiền mặt';
    }

    /**
     * Lấy class CSS cho hạng thành viên
     */
    public function getRankClass(string $rank): string {
        return 'rank-' . strtolower($rank);
    }
}