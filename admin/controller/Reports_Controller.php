<?php
// admin/controller/Reports_Controller.php

require_once __DIR__ . '/../../model/database.php';

class ReportsController
{
    /**
     * Main handler
     */
    public static function handle(): array
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Lấy tham số từ URL
        $reportType = $_GET['type'] ?? 'overview';
        $fromDate = $_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $toDate = $_GET['to_date'] ?? date('Y-m-d');

        // Xử lý export
        if (isset($_GET['export'])) {
            self::handleExport($conn, $_GET['export'], $fromDate, $toDate);
            exit;
        }

        // Lấy dữ liệu báo cáo
        $data = [
            'report_type' => $reportType,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'daily_revenue' => self::getDailyRevenue($conn, $fromDate, $toDate),
            'inventory_stock' => self::getInventoryStock($conn),
            'top_products' => self::getTopProducts($conn, $fromDate, $toDate),
            'revenue_by_category' => self::getRevenueByCategory($conn, $fromDate, $toDate),
            'summary_stats' => self::getSummaryStats($conn, $fromDate, $toDate),
        ];

        return $data;
    }

    /**
     * Lấy doanh thu theo ngày
     */
    private static function getDailyRevenue($conn, $fromDate, $toDate): array
    {
        $sql = "SELECT 
                    DATE(dh.NgayDat) as date,
                    COUNT(dh.MaDonHang) as total_orders,
                    SUM(tt.SoTien) as total_revenue
                FROM donhang dh
                LEFT JOIN thanhtoan tt ON dh.MaDonHang = tt.MaDonHang
                WHERE dh.TrangThai = 'DONE'
                  AND DATE(dh.NgayDat) BETWEEN ? AND ?
                GROUP BY DATE(dh.NgayDat)
                ORDER BY date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $fromDate, $toDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'date' => $row['date'],
                'total_orders' => (int)$row['total_orders'],
                'total_revenue' => (float)($row['total_revenue'] ?? 0)
            ];
        }

        $stmt->close();
        return $data;
    }

    /**
     * Lấy tồn kho sản phẩm
     */
    private static function getInventoryStock($conn): array
    {
        $sql = "SELECT 
                    sp.MaSanPham,
                    sp.TenSanPham,
                    GROUP_CONCAT(DISTINCT dm.TenDanhMuc SEPARATOR ', ') as TenDanhMuc,
                    sp.SoLuongTon,
                    sp.Gia,
                    (sp.SoLuongTon * sp.Gia) as stock_value,
                    CASE 
                        WHEN sp.SoLuongTon = 0 THEN 'Hết hàng'
                        WHEN sp.SoLuongTon < 10 THEN 'Sắp hết'
                        WHEN sp.SoLuongTon < 50 THEN 'Tồn ít'
                        ELSE 'Tồn nhiều'
                    END as stock_status
                FROM sanpham sp
                LEFT JOIN sanpham_danhmuc spdm ON sp.MaSanPham = spdm.MaSanPham
                LEFT JOIN danhmucsanpham dm ON spdm.MaDanhMuc = dm.MaDanhMuc
                GROUP BY sp.MaSanPham, sp.TenSanPham, sp.SoLuongTon, sp.Gia
                ORDER BY sp.SoLuongTon ASC, sp.TenSanPham ASC";

        $result = $conn->query($sql);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'product_id' => $row['MaSanPham'],
                'product_name' => $row['TenSanPham'],
                'category' => $row['TenDanhMuc'] ?? 'N/A',
                'quantity' => (int)$row['SoLuongTon'],
                'price' => (float)$row['Gia'],
                'stock_value' => (float)$row['stock_value'],
                'status' => $row['stock_status']
            ];
        }

        return $data;
    }

    /**
     * Top sản phẩm bán chạy
     */
    private static function getTopProducts($conn, $fromDate, $toDate): array
    {
        $sql = "SELECT 
                    sp.MaSanPham,
                    sp.TenSanPham,
                    SUM(ct.SoLuong) as total_sold,
                    SUM(ct.SoLuong * ct.DonGia) as total_revenue
                FROM chitietdonhang ct
                JOIN sanpham sp ON ct.MaSanPham = sp.MaSanPham
                JOIN donhang dh ON ct.MaDonHang = dh.MaDonHang
                WHERE dh.TrangThai = 'DONE'
                  AND DATE(dh.NgayDat) BETWEEN ? AND ?
                GROUP BY sp.MaSanPham, sp.TenSanPham
                ORDER BY total_sold DESC
                LIMIT 10";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $fromDate, $toDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'product_id' => $row['MaSanPham'],
                'product_name' => $row['TenSanPham'],
                'total_sold' => (int)$row['total_sold'],
                'total_revenue' => (float)$row['total_revenue']
            ];
        }

        $stmt->close();
        return $data;
    }

    /**
     * Doanh thu theo danh mục
     */
    private static function getRevenueByCategory($conn, $fromDate, $toDate): array
    {
        $sql = "SELECT 
                    dm.TenDanhMuc,
                    COUNT(DISTINCT dh.MaDonHang) as total_orders,
                    SUM(ct.SoLuong) as total_quantity,
                    SUM(ct.SoLuong * ct.DonGia) as total_revenue
                FROM chitietdonhang ct
                JOIN sanpham sp ON ct.MaSanPham = sp.MaSanPham
                JOIN sanpham_danhmuc spdm ON sp.MaSanPham = spdm.MaSanPham
                JOIN danhmucsanpham dm ON spdm.MaDanhMuc = dm.MaDanhMuc
                JOIN donhang dh ON ct.MaDonHang = dh.MaDonHang
                WHERE dh.TrangThai = 'DONE'
                  AND DATE(dh.NgayDat) BETWEEN ? AND ?
                GROUP BY dm.MaDanhMuc, dm.TenDanhMuc
                ORDER BY total_revenue DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $fromDate, $toDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'category' => $row['TenDanhMuc'],
                'total_orders' => (int)$row['total_orders'],
                'total_quantity' => (int)$row['total_quantity'],
                'total_revenue' => (float)$row['total_revenue']
            ];
        }

        $stmt->close();
        return $data;
    }

    /**
     * Thống kê tổng quan
     */
    private static function getSummaryStats($conn, $fromDate, $toDate): array
    {
        // Tổng doanh thu
        $sql = "SELECT SUM(tt.SoTien) as total 
                FROM donhang dh
                LEFT JOIN thanhtoan tt ON dh.MaDonHang = tt.MaDonHang
                WHERE dh.TrangThai = 'DONE' 
                  AND DATE(dh.NgayDat) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $fromDate, $toDate);
        $stmt->execute();
        $totalRevenue = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // Tổng đơn hàng
        $sql = "SELECT COUNT(*) as total FROM donhang 
                WHERE TrangThai = 'DONE' AND DATE(NgayDat) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $fromDate, $toDate);
        $stmt->execute();
        $totalOrders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        // Tổng giá trị tồn kho
        $sql = "SELECT SUM(SoLuongTon * Gia) as total FROM sanpham";
        $stockValue = $conn->query($sql)->fetch_assoc()['total'] ?? 0;

        // Tổng số lượng tồn
        $sql = "SELECT SUM(SoLuongTon) as total FROM sanpham";
        $totalStock = $conn->query($sql)->fetch_assoc()['total'] ?? 0;

        return [
            'total_revenue' => (float)$totalRevenue,
            'total_orders' => (int)$totalOrders,
            'avg_order_value' => $totalOrders > 0 ? (float)$totalRevenue / $totalOrders : 0,
            'stock_value' => (float)$stockValue,
            'total_stock' => (int)$totalStock
        ];
    }

    /**
     * Xử lý export
     */
    private static function handleExport($conn, $type, $fromDate, $toDate)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bao-cao-' . $type . '-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        if ($type === 'daily_revenue') {
            fputcsv($output, ['Ngày', 'Số đơn hàng', 'Doanh thu (VNĐ)']);
            $data = self::getDailyRevenue($conn, $fromDate, $toDate);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['date'],
                    $row['total_orders'],
                    number_format($row['total_revenue'], 0, '', '')
                ]);
            }
        } elseif ($type === 'inventory') {
            fputcsv($output, ['Mã SP', 'Tên sản phẩm', 'Danh mục', 'Tồn kho', 'Giá (VNĐ)', 'Giá trị tồn (VNĐ)', 'Trạng thái']);
            $data = self::getInventoryStock($conn);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['product_id'],
                    $row['product_name'],
                    $row['category'],
                    $row['quantity'],
                    number_format($row['price'], 0, '', ''),
                    number_format($row['stock_value'], 0, '', ''),
                    $row['status']
                ]);
            }
        }

        fclose($output);
    }
}