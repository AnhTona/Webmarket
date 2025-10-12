<?php
// admin/controller/Dashboard_Controller.php
// PHP 8+, MySQLi

class DashboardController
{
    public static function handle(): array
    {
        $conn = self::connect();

        // Chỉ trả đúng dữ liệu dashboard cần
        $kpis   = self::kpis($conn);               // gồm cả % đã format sẵn
        $series = self::revenueSeries30d($conn);   // [labels[], series[]]
        $bars7  = self::bars7d($series);           // đã tính pct/title/dow

        $recent = self::recentOrders($conn);       // 10 đơn gần nhất

        $conn->close();

        return [
            'kpis'              => $kpis,
            'revenue_series'    => $series,
            'bars_7d'           => $bars7,               // <—— thêm trường này cho UI
            'recent_orders'     => $recent,

            // nếu header có chuông thông báo
            'notifications'      => $GLOBALS['notifications'] ?? [],
            'notification_count' => is_array($GLOBALS['notifications'] ?? null) ? count($GLOBALS['notifications']) : 0,
        ];
    }

    /* ===================== KPIs ===================== */

    private static function kpis(mysqli $conn): array
    {
        // Doanh thu tháng này & tháng trước (theo lịch)
        $rev_month = (float) self::value($conn, "
            SELECT COALESCE(SUM(SoTien),0)
            FROM thanhtoan
            WHERE YEAR(NgayThanhToan)=YEAR(CURDATE())
              AND MONTH(NgayThanhToan)=MONTH(CURDATE())
        ");

        $rev_prev_month = (float) self::value($conn, "
            SELECT COALESCE(SUM(SoTien),0)
            FROM thanhtoan
            WHERE YEAR(NgayThanhToan)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
              AND MONTH(NgayThanhToan)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
        ");

        $rev_month_change_pct = self::pctChange($rev_prev_month, $rev_month);
        $rev_month_pct_text   = self::formatPct($rev_month_change_pct);

        // Đơn 7 ngày gần nhất & 7 ngày trước đó
        $orders_7d_current = (int) self::value($conn, "
            SELECT COUNT(*) FROM donhang
            WHERE NgayDat >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $orders_7d_prev = (int) self::value($conn, "
            SELECT COUNT(*) FROM donhang
            WHERE NgayDat >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
              AND NgayDat <  DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $orders_7d_change_pct = self::pctChange($orders_7d_prev, $orders_7d_current);
        $orders_7d_pct_text   = self::formatPct($orders_7d_change_pct);

        // Khách hàng mới 7 ngày
        $new_customers_7d = (int) self::value($conn, "
            SELECT COUNT(*) FROM nguoidung
            WHERE VaiTro='USER' AND NgayTao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");

        // Tồn kho (ưu tiên SoLuongTon -> SoLuong -> 0)
        $stock_total = (int) self::value($conn, "
            SELECT COALESCE(SUM(SoLuongTon), SUM(SoLuongTon), 0)
            FROM sanpham
        ");

        // Tổng đơn (nếu cần dùng nơi khác)
        $total_orders = (int) self::value($conn, "SELECT COUNT(*) FROM donhang");

        return [
            'revenue_month'             => $rev_month,
            'revenue_month_change_pct'  => $rev_month_change_pct,
            'revenue_month_pct_text'    => $rev_month_pct_text,      // <—— text sẵn: "+12%"
            'orders_7d_current'         => $orders_7d_current,
            'orders_7d_change_pct'      => $orders_7d_change_pct,
            'orders_7d_pct_text'        => $orders_7d_pct_text,      // <—— text sẵn: "-3%"
            'new_customers_7d'          => $new_customers_7d,
            'stock_total'               => $stock_total,
            'total_orders'              => $total_orders,
        ];
    }

    /* ================== Time series ================== */

    // Doanh thu 30 ngày (để có đủ dữ liệu dựng 7 ngày gần nhất)
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

    // Tạo dữ liệu “cột 7 ngày” cho UI (dow, pct, title, value)
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
            $w   = (int)date('N', strtotime($day)); // 1..7
            $dow = ['','T2','T3','T4','T5','T6','T7','CN'][$w];
            $bars[] = ['day'=>$day, 'dow'=>$dow, 'value'=>$val];
            if ($val > $max) $max = $val;
        }

        // nếu rỗng → mock 7 ngày giá trị 0 để UI không vỡ
        if (!$bars) {
            for ($j=6; $j>=0; $j--) {
                $d = date('Y-m-d', strtotime("-$j day"));
                $w = (int)date('N', strtotime($d));
                $dow = ['','T2','T3','T4','T5','T6','T7','CN'][$w];
                $bars[] = ['day'=>$d, 'dow'=>$dow, 'value'=>0];
            }
        }

        foreach ($bars as &$b) {
            $pct = $max > 0 ? round($b['value'] * 100 / $max, 2) : 5; // min 5%
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
            SELECT dh.MaDonHang, dh.NgayDat, dh.TongTien, dh.TrangThai,
                   nd.HoTen AS KhachHang
            FROM donhang dh
            LEFT JOIN nguoidung nd ON nd.MaNguoiDung = dh.MaNguoiDung
            ORDER BY dh.NgayDat DESC
            LIMIT 10
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

    private static function connect(): mysqli
    {
        // ưu tiên db.php nếu đã mở sẵn kết nối
        $candidates = [
            dirname(__DIR__, 2) . '/../../model/db.php',
            dirname(__DIR__)    . '/../../model/db.php',
            __DIR__ . '/../../model/db.php',
        ];
        foreach ($candidates as $f) { if (is_file($f)) include_once $f; }

        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            $GLOBALS['conn']->set_charset('utf8mb4');
            return $GLOBALS['conn'];
        }
        $conn = @new mysqli('127.0.0.1', 'root', '', 'webmarket');
        if ($conn->connect_error) { http_response_code(500); die('DB lỗi: '.$conn->connect_error); }
        $conn->set_charset('utf8mb4');
        return $conn;
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
            if ($res) { $row = $res->fetch_row(); if ($row && isset($row[0])) $val = $row[0]; }
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
