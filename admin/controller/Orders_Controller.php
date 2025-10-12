<?php
// admin/controller/Orders_Controller.php
// PHP 8+, MySQLi. KHÔNG sửa layout/JS. Chỉ cung cấp data và các API AJAX.

require_once __DIR__ . '/../../model/db.php';

class OrdersController
{
    // Map trạng thái DB ↔ UI
    // DB: DRAFT, PLACED, CONFIRMED, SHIPPING, DONE, CANCELLED (đoán theo dump của bạn)
    private const DB2UI = [
        'DRAFT'     => 'Nháp',
        'PLACED'    => 'Chờ xác nhận',
        'CONFIRMED' => 'Đang chuẩn bị',
        'SHIPPING'  => 'Đang giao',
        'DONE'      => 'Hoàn thành',
        'CANCELLED' => 'Đã hủy',
    ];
    private const UI2DB = [
        'Nháp'           => 'DRAFT',
        'Chờ xác nhận'   => 'PLACED',
        'Đang chuẩn bị'  => 'CONFIRMED',
        'Đang giao'      => 'SHIPPING',
        'Hoàn thành'     => 'DONE',
        'Đã hủy'         => 'CANCELLED',
    ];

    public static function handle(): array
    {
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            $action = $_GET['action'] ?? ($_POST['action'] ?? '');
            try {
                switch ($action) {
                    case 'confirm':  return self::json(self::updateStatus('CONFIRMED'));
                    case 'cancel':   return self::json(self::updateStatus('CANCELLED'));
                    case 'complete': return self::json(self::updateStatus('DONE'));
                    case 'change_status': return self::json(self::changeStatusByText());
                    case 'view':     return self::json(self::fetchDetails());
                    default:         return self::json(['ok'=>false,'message'=>'Thiếu hoặc sai action']);
                }
            } catch (Throwable $e) {
                return self::json(['ok'=>false,'message'=>'Lỗi: '.$e->getMessage()]);
            }
        }

        [$orders, $page, $totalPages] = self::fetchList();
        return [
            'order_list'  => $orders,
            'page'        => $page,
            'total_pages' => $totalPages,
        ];
    }

    /* ============ LIST ============ */

    /**
     * Lấy danh sách đơn cho UI hiện tại.
     * Trả về: [$rows, $page, $totalPages]
     */
    private static function fetchList(): array
    {
        global $conn;

        $search     = trim((string)($_GET['search'] ?? ''));     // tên KH / mã đơn
        $ym         = trim((string)($_GET['date'] ?? ''));       // yyyy-mm
        $statusText = trim((string)($_GET['status'] ?? 'All'));  // theo UI
        $dateFrom   = trim((string)($_GET['date_from'] ?? ''));  // yyyy-mm-dd
        $dateTo     = trim((string)($_GET['date_to'] ?? ''));    // yyyy-mm-dd

        $where = [];
        $params = [];
        $types = '';

        if ($search !== '') {
            // Tìm theo mã đơn hoặc tên KH / email / phone
            $where[]  = '(dh.MaDonHang LIKE ? OR nd.HoTen LIKE ? OR nd.Email LIKE ? OR nd.SoDienThoai LIKE ?)';
            $kw = "%$search%";
            array_push($params, $kw, $kw, $kw, $kw);
            $types .= 'ssss';
        }
        if ($ym !== '') {
            $where[] = 'DATE_FORMAT(dh.NgayDat, "%Y-%m") = ?';
            $params[] = $ym;
            $types   .= 's';
        }
        if ($statusText !== '' && $statusText !== 'All') {
            $dbStatus = self::UI2DB[$statusText] ?? null;
            if ($dbStatus) {
                $where[] = 'dh.TrangThai = ?';
                $params[] = $dbStatus;
                $types   .= 's';
            }
        }
        if ($dateFrom !== '') {
            $where[] = 'DATE(dh.NgayDat) >= ?';
            $params[] = $dateFrom;
            $types   .= 's';
        }
        if ($dateTo !== '') {
            $where[] = 'DATE(dh.NgayDat) <= ?';
            $params[] = $dateTo;
            $types   .= 's';
        }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        // Phân trang nhẹ (nếu bạn chưa dùng ở UI thì vẫn trả page=1)
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Đếm tổng
        $sqlCount = "SELECT COUNT(*) AS c FROM donhang dh 
                     JOIN nguoidung nd ON nd.MaNguoiDung = dh.MaNguoiDung
                     $whereSql";
        $total = self::scalar($sqlCount, $types, $params);
        $totalPages = (int)max(1, ceil($total / $limit));

        // Lấy list
        $sql = "
            SELECT 
              dh.MaDonHang,
              nd.HoTen       AS customer_name,
              dh.MaBan       AS ban_id,
              dh.NgayDat,
              dh.TongTien,
              dh.TrangThai
            FROM donhang dh
            JOIN nguoidung nd ON nd.MaNguoiDung = dh.MaNguoiDung
            $whereSql
            ORDER BY dh.NgayDat DESC
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $conn->prepare($sql);
        if ($types) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $rs = $stmt->get_result();

        $rows = [];
        while ($r = $rs->fetch_assoc()) {
            $rows[] = [
                // match đúng keys mà orders.js dùng
                'MaDon'    => (string)$r['MaDonHang'],
                'KhachHang'=> (string)($r['customer_name'] ?? 'Khách lẻ'),
                'Ban'      => $r['ban_id'] ? ('Bàn '.$r['ban_id']) : '-',
                'NgayDat'  => date('Y-m-d H:i', strtotime($r['NgayDat'] ?? 'now')),
                'TongTien' => (int)($r['TongTien'] ?? 0), // JS đã format lại
                'TrangThai'=> self::DB2UI[$r['TrangThai']] ?? $r['TrangThai'],
            ];
        }
        $stmt->close();

        return [$rows, $page, $totalPages];
    }

    /* ============ DETAILS ============ */

    private static function fetchDetails(): array
    {
        global $conn;
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) return ['ok'=>false, 'message'=>'Thiếu id'];

        // Lấy chi tiết: tên SP, SL, giá, tổng
        $sql = "
            SELECT sp.TenSanPham, ct.SoLuong,
                   COALESCE(ct.DonGia, sp.Gia, 0) AS Gia,
                   (ct.SoLuong * COALESCE(ct.DonGia, sp.Gia, 0)) AS Tong
            FROM chitietdonhang ct
            JOIN sanpham sp ON sp.MaSanPham = ct.MaSanPham
            WHERE ct.MaDonHang = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $rs = $stmt->get_result();

        $items = [];
        while ($r = $rs->fetch_assoc()) {
            $items[] = [
                'TenSanPham' => (string)$r['TenSanPham'],
                'SoLuong'    => (int)$r['SoLuong'],
                'Gia'        => (int)$r['Gia'],
                'Tong'       => (int)$r['Tong'],
            ];
        }
        $stmt->close();

        return ['ok'=>true,'items'=>$items];
    }

    /* ============ STATUS ACTIONS ============ */

    // confirm / cancel / complete
    private static function updateStatus(string $to): array
    {
        global $conn;
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) return ['ok'=>false,'message'=>'Thiếu id'];

        $stmt = $conn->prepare("UPDATE donhang SET TrangThai=? WHERE MaDonHang=?");
        $stmt->bind_param('si', $to, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? ['ok'=>true,'message'=>'Đã cập nhật trạng thái'] : ['ok'=>false,'message'=>'Cập nhật thất bại'];
    }

    // change_status: nhận status tiếng Việt từ UI
    private static function changeStatusByText(): array
    {
        global $conn;
        $id = (int)($_GET['id'] ?? 0);
        $statusText = trim((string)($_GET['status'] ?? ''));
        if (!$id || $statusText==='') return ['ok'=>false,'message'=>'Thiếu tham số'];

        $db = self::UI2DB[$statusText] ?? null;
        if (!$db) return ['ok'=>false,'message'=>'Trạng thái không hợp lệ'];

        $stmt = $conn->prepare("UPDATE donhang SET TrangThai=? WHERE MaDonHang=?");
        $stmt->bind_param('si', $db, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? ['ok'=>true,'message'=>'Đã cập nhật trạng thái'] : ['ok'=>false,'message'=>'Cập nhật thất bại'];
    }

    /* ============ Helpers ============ */

    private static function scalar(string $sql, string $types='', array $params=[]): int
    {
        global $conn;
        $stmt = $conn->prepare($sql);
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($res['c'] ?? 0);
    }

    private static function json(array $payload): array
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        return $payload;
    }
}
