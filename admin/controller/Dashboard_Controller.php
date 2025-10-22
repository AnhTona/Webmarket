<?php
declare(strict_types=1);
require_once __DIR__ . '/../../model/database.php';

class DashboardController
{
    public static function handle(): array
    {
        $conn = self::connect();

        // Lấy tất cả dữ liệu dashboard
        $kpis   = self::kpis($conn);
        $series = self::revenueSeries30d($conn);
        $bars7  = self::bars7d($series);
        $recent = self::recentOrders($conn);

        return [
            'kpis'              => $kpis,
            'revenue_series'    => $series,
            'bars_7d'           => $bars7,
            'recent_orders'     => $recent,
            'notifications'      => [],
            'notification_count' => 0,
        ];
    }

    /* ===== DB Connect - OOP Version ===== */
    private static function connect(): mysqli
    {
        $db = Database::getInstance();
        return $db->getConnection();
    }

    /* ===================== KPIs ===================== */
    private static function kpis(mysqli $conn): array
    {
        // 1. DOANH THU THÁNG NÀY (từ bảng thanhtoan)
        $rev_month = (float) self::value($conn, "
            SELECT COALESCE(SUM(SoTien), 0)
            FROM thanhtoan
            WHERE YEAR(NgayThanhToan) = YEAR(CURDATE())
              AND MONTH(NgayThanhToan) = MONTH(CURDATE())
        ");

        // 2. DOANH THU THÁNG TRƯỚC
        $rev_prev_month = (float) self::value($conn, "
            SELECT COALESCE(SUM(SoTien), 0)
            FROM thanhtoan
            WHERE YEAR(NgayThanhToan) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
              AND MONTH(NgayThanhToan) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
        ");

        $rev_month_change_pct = self::pctChange($rev_prev_month, $rev_month);
        $rev_month_pct_text   = self::formatPct($rev_month_change_pct);

        // 3. ĐƠN HÀNG 7 NGÀY GẦN NHẤT (chỉ tính đơn đã xác nhận trở lên)
        $orders_7d_current = (int) self::value($conn, "
            SELECT COUNT(*) FROM donhang
            WHERE NgayDat >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              AND TrangThai IN ('PLACED', 'CONFIRMED', 'SHIPPING', 'DONE')
        ");

        // 4. ĐƠN HÀNG 7 NGÀY TRƯỚC ĐÓ
        $orders_7d_prev = (int) self::value($conn, "
            SELECT COUNT(*) FROM donhang
            WHERE NgayDat >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
              AND NgayDat <  DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              AND TrangThai IN ('PLACED', 'CONFIRMED', 'SHIPPING', 'DONE')
        ");

        $orders_7d_change_pct = self::pctChange($orders_7d_prev, $orders_7d_current);
        $orders_7d_pct_text   = self::formatPct($orders_7d_change_pct);

        // 5. KHÁCH HÀNG MỚI 7 NGÀY (VaiTro='USER')
        $new_customers_7d = (int) self::value($conn, "
            SELECT COUNT(*) FROM nguoidung
            WHERE VaiTro = 'USER' 
              AND NgayTao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");

        // 6. KHÁCH HÀNG MỚI 7 NGÀY TRƯỚC
        $new_customers_prev = (int) self::value($conn, "
            SELECT COUNT(*) FROM nguoidung
            WHERE VaiTro = 'USER'
              AND NgayTao >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
              AND NgayTao <  DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");

        $customers_7d_change_pct = self::pctChange($new_customers_prev, $new_customers_7d);
        $customers_7d_pct_text   = self::formatPct($customers_7d_change_pct);

        // 7. TỒN KHO (SoLuongTon)
        $stock_total = (int) self::value($conn, "
            SELECT COALESCE(SUM(SoLuongTon), 0)
            FROM sanpham
            WHERE TrangThai = 1
        ");

        return [
            'revenue_month'             => $rev_month,
            'revenue_month_change_pct'  => $rev_month_change_pct,
            'revenue_month_pct_text'    => $rev_month_pct_text,

            'orders_7d_current'         => $orders_7d_current,
            'orders_7d_change_pct'      => $orders_7d_change_pct,
            'orders_7d_pct_text'        => $orders_7d_pct_text,

            'new_customers_7d'          => $new_customers_7d,
            'customers_7d_change_pct'   => $customers_7d_change_pct,
            'customers_7d_pct_text'     => $customers_7d_pct_text,

            'stock_total'               => $stock_total,
        ];
    }

    /* ================== Time series ================== */
    private static function revenueSeries30d(mysqli $conn): array
    {
        $rows = self::rows($conn, "
            SELECT DATE(NgayThanhToan) AS d, SUM(SoTien) AS s
            FROM thanhtoan
            WHERE NgayThanhToan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(NgayThanhToan)
            ORDER BY d
        ");

        $labels = [];
        $series = [];
        foreach ($rows as $r) {
            $labels[] = $r['d'];
            $series[] = (float)$r['s'];
        }
        return ['labels' => $labels, 'series' => $series];
    }

    private static function bars7d(array $series): array
    {
        $labelsAll = $series['labels'] ?? [];
        $seriesAll = $series['series'] ?? [];
        $cnt   = count($labelsAll);
        $start = max(0, $cnt - 7);

        $bars = [];
        $max  = 0;
        for ($i = $start; $i < $cnt; $i++) {
            $day = $labelsAll[$i] ?? date('Y-m-d');
            $val = (int)($seriesAll[$i] ?? 0);
            $w   = (int)date('N', strtotime($day));
            $dow = ['','T2','T3','T4','T5','T6','T7','CN'][$w];
            $bars[] = ['day'=>$day, 'dow'=>$dow, 'value'=>$val];
            if ($val > $max) $max = $val;
        }

        if (!$bars) {
            for ($j=6; $j>=0; $j--) {
                $d = date('Y-m-d', strtotime("-$j day"));
                $w = (int)date('N', strtotime($d));
                $dow = ['','T2','T3','T4','T5','T6','T7','CN'][$w];
                $bars[] = ['day'=>$d, 'dow'=>$dow, 'value'=>0];
            }
        }

        foreach ($bars as &$b) {
            $pct = $max > 0 ? round($b['value'] * 100 / $max, 2) : 5;
            $b['pct'] = max($pct, $b['value'] > 0 ? 8 : 3);
            $million  = $b['value'] >= 1_000_000
                ? round($b['value'] / 1_000_000) . 'M'
                : number_format($b['value'], 0, ',', '.');
            $b['title'] = "{$b['dow']}: {$million}";
        }
        unset($b);

        return $bars;
    }

    /* ================= Recent orders ================= */
    private static function recentOrders(mysqli $conn): array
    {
        return self::rows($conn, "
            SELECT 
                dh.MaDonHang, 
                dh.NgayDat, 
                dh.TongTien, 
                dh.TrangThai,
                COALESCE(nd.HoTen, 'Khách lẻ') AS KhachHang
            FROM donhang dh
            LEFT JOIN nguoidung nd ON nd.MaNguoiDung = dh.MaNguoiDung
            WHERE dh.TrangThai != 'DRAFT'
            ORDER BY dh.NgayDat DESC
            LIMIT 5
        ");
    }

    /* ====================== Utils ==================== */
    private static function pctChange(float $prev, float $curr): float
    {
        if (abs($prev) < 0.00001) return $curr > 0 ? 100.0 : 0.0;
        return round((($curr - $prev) / $prev) * 100, 2);
    }

    private static function formatPct(float $pct): string
    {
        $txt = number_format($pct, 2, '.', '');
        $txt = rtrim(rtrim($txt, '0'), '.');
        return ($pct >= 0 ? '+' : '') . $txt . '%';
    }

    private static function value(mysqli $conn, string $sql, array $params = [])
    {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return 0;
        if ($params) self::bind($stmt, $params);
        if (!$stmt->execute()) { $stmt->close(); return 0; }

        $val = 0;
        if (method_exists($stmt, 'get_result')) {
            $res = $stmt->get_result();
            if ($res) {
                $row = $res->fetch_row();
                if ($row && isset($row[0])) $val = $row[0];
            }
        } else {
            $stmt->bind_result($val);
            $stmt->fetch();
        }
        $stmt->close();
        return is_numeric($val) ? $val + 0 : 0;
    }

    private static function rows(mysqli $conn, string $sql, array $params = []): array
    {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        if ($params) self::bind($stmt, $params);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $out ?: [];
    }

    private static function bind(mysqli_stmt $stmt, array $params): void
    {
        $types = '';
        $vals  = [];
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            $vals[] = $p;
        }
        if ($types) $stmt->bind_param($types, ...$vals);
    }
}