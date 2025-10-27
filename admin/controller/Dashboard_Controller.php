<?php
declare(strict_types=1);

/**
 * Dashboard_Controller.php
 * Dashboard analytics controller with PHP 8.4 features
 *
 * @package Admin\Controller
 * @author AnhTona
 * @version 2.0.0
 * @since PHP 8.4
 */

require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/BaseController.php';

final class DashboardController extends BaseController
{
    /**
     * Handle dashboard requests
     *
     * @return array<string, mixed>
     */
    public static function handle(): array
    {
        self::requireAuth();

        try {
            return [
                'kpis' => self::getKPIs(),
                'revenue_series' => self::getRevenueSeries(),
                'bars_7d' => self::getRevenueBarChart(),
                'recent_orders' => self::getRecentOrders(),
                'notifications' => [],
                'notification_count' => 0,
            ];
        } catch (Throwable $e) {
            error_log("Dashboard Error: " . $e->getMessage());

            return [
                'kpis' => self::getEmptyKPIs(),
                'revenue_series' => [],
                'bars_7d' => [],
                'recent_orders' => [],
                'error' => 'Không thể tải dữ liệu dashboard',
            ];
        }
    }

    /**
     * Get Key Performance Indicators
     *
     * @return array<string, mixed>
     */
    private static function getKPIs(): array
    {
        // Revenue this month (from thanhtoan table)
        $revenueMonth = (float)self::fetchOne(
            "SELECT COALESCE(SUM(SoTien), 0)
             FROM thanhtoan
             WHERE YEAR(NgayThanhToan) = YEAR(CURDATE())
               AND MONTH(NgayThanhToan) = MONTH(CURDATE())"
        );

        // Revenue last month
        $revenuePrevMonth = (float)self::fetchOne(
            "SELECT COALESCE(SUM(SoTien), 0)
             FROM thanhtoan
             WHERE YEAR(NgayThanhToan) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
               AND MONTH(NgayThanhToan) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
        );

        // Orders last 7 days
        $orders7d = (int)self::fetchOne(
            "SELECT COUNT(*) FROM donhang
             WHERE NgayDat >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND TrangThai IN ('PLACED', 'CONFIRMED', 'SHIPPING', 'DONE')"
        );

        // Orders previous 7 days
        $ordersPrev7d = (int)self::fetchOne(
            "SELECT COUNT(*) FROM donhang
             WHERE NgayDat >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
               AND NgayDat < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND TrangThai IN ('PLACED', 'CONFIRMED', 'SHIPPING', 'DONE')"
        );

        // New customers last 7 days
        $newCustomers7d = (int)self::fetchOne(
            "SELECT COUNT(*) FROM nguoidung
             WHERE NgayTao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND VaiTro = 'CUSTOMER'"
        );

        // New customers previous 7 days
        $customersPrev7d = (int)self::fetchOne(
            "SELECT COUNT(*) FROM nguoidung
             WHERE NgayTao >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
               AND NgayTao < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND VaiTro = 'CUSTOMER'"
        );

        // Total stock
        $stockTotal = (int)self::fetchOne(
            "SELECT COALESCE(SUM(SoLuongTon), 0) FROM sanpham"
        );

        // Calculate percentage changes
        $revenueChangePct = self::percentChange($revenuePrevMonth, $revenueMonth);
        $ordersChangePct = self::percentChange($ordersPrev7d, $orders7d);
        $customersChangePct = self::percentChange($customersPrev7d, $newCustomers7d);

        return [
            'revenue_month' => $revenueMonth,
            'revenue_month_change_pct' => $revenueChangePct,
            'revenue_month_pct_text' => self::formatPercent($revenueChangePct),

            'orders_7d_current' => $orders7d,
            'orders_7d_change_pct' => $ordersChangePct,
            'orders_7d_pct_text' => self::formatPercent($ordersChangePct),

            'new_customers_7d' => $newCustomers7d,
            'customers_7d_change_pct' => $customersChangePct,
            'customers_7d_pct_text' => self::formatPercent($customersChangePct),

            'stock_total' => $stockTotal,
        ];
    }

    /**
     * Get revenue series for last 30 days
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getRevenueSeries(): array
    {
        $data = self::fetchAll(
            "SELECT 
            DATE(tt.NgayThanhToan) as date,
            COALESCE(SUM(tt.SoTien), 0) as revenue
         FROM thanhtoan tt
         WHERE tt.NgayThanhToan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         GROUP BY DATE(tt.NgayThanhToan)
         ORDER BY date ASC"
        );

        return array_map(
            fn(array $row): array => [
                'date' => $row['date'],
                'revenue' => (float)$row['revenue'],
                'day_of_week' => self::getDayOfWeekVN($row['date']), // ← SỬA ĐÂY
            ],
            $data
        );
    }

    /**
     * Get revenue bar chart data (last 7 days)
     *
     * @return array<int, array<string, mixed>>
     */
    /**
     * Get revenue bar chart with full 7 days (including days with no revenue)
     */
    private static function getRevenueBarChart(): array
    {
        // Get all revenue data from last 7 days
        $data = self::fetchAll(
            "SELECT 
            DATE(tt.NgayThanhToan) as date,
            COALESCE(SUM(tt.SoTien), 0) as revenue
         FROM thanhtoan tt
         WHERE tt.NgayThanhToan >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(tt.NgayThanhToan)
         ORDER BY date ASC"
        );

        // Create array indexed by date
        $revenueByDate = [];
        foreach ($data as $row) {
            $revenueByDate[$row['date']] = (float)$row['revenue'];
        }

        // Generate full 7 days
        $bars = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $revenue = $revenueByDate[$date] ?? 0;

            $bars[] = [
                'date' => $date,
                'dow' => self::getDayOfWeekVN($date),
                'revenue' => $revenue,
            ];
        }

        // Calculate percentages
        $revenues = array_column($bars, 'revenue');
        $maxRevenue = max($revenues);
        $maxRevenue = $maxRevenue > 0 ? $maxRevenue : 1;

        return array_map(
            fn(array $day): array => [
                'date' => $day['date'],
                'dow' => $day['dow'],
                'revenue' => $day['revenue'],
                'pct' => round(($day['revenue'] / $maxRevenue) * 100, 2),
                'title' => number_format($day['revenue'], 0, ',', '.') . ' VNĐ',
            ],
            $bars
        );
    }

    /**
     * Get 5 most recent orders
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getRecentOrders(): array
    {
        return self::fetchAll(
            "SELECT 
                dh.MaDonHang,
                nd.HoTen as KhachHang,
                dh.NgayDat,
                dh.TongTien,
                dh.TrangThai
             FROM donhang dh
             LEFT JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
             ORDER BY dh.NgayDat DESC
             LIMIT 5"
        );
    }

    /**
     * Get empty KPIs structure for error state
     *
     * @return array<string, mixed>
     */
    private static function getEmptyKPIs(): array
    {
        return [
            'revenue_month' => 0,
            'revenue_month_change_pct' => 0,
            'revenue_month_pct_text' => '0%',
            'orders_7d_current' => 0,
            'orders_7d_change_pct' => 0,
            'orders_7d_pct_text' => '0%',
            'new_customers_7d' => 0,
            'customers_7d_change_pct' => 0,
            'customers_7d_pct_text' => '0%',
            'stock_total' => 0,
        ];
    }
    /**
     * Convert English day of week to Vietnamese
     */
    private static function getDayOfWeekVN(string $date): string
    {
        $dayMap = [
            'Mon' => 'T2',
            'Tue' => 'T3',
            'Wed' => 'T4',
            'Thu' => 'T5',
            'Fri' => 'T6',
            'Sat' => 'T7',
            'Sun' => 'CN',
        ];

        $day = date('D', strtotime($date));
        return $dayMap[$day] ?? $day;
    }
}