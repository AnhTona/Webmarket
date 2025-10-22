<?php
// admin/controller/Orders_Controller.php
// Thêm chức năng xem hóa đơn đã lưu

require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/../html/Invoice_Generator.php';

class OrdersController
{
    // Map trạng thái DB ↔ UI
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
                    case 'view':     return self::json(self::viewInvoice());
                    case 'generate_invoice': return self::json(self::generateInvoice());
                    default:         return self::json(['ok'=>false,'message'=>'Thiếu hoặc sai action']);
                }
            } catch (Throwable $e) {
                return self::json(['ok'=>false,'message'=>'Lỗi: '.$e->getMessage()]);
            }
        }

        // Xử lý view invoice HTML trực tiếp
        if (isset($_GET['view_invoice'])) {
            self::displayInvoice();
            exit;
        }

        [$orders, $page, $totalPages] = self::fetchList();
        return [
            'order_list'  => $orders,
            'page'        => $page,
            'total_pages' => $totalPages,
        ];
    }

    /* ============ DB Connect - OOP Version ============ */
    private static function getConnection(): mysqli
    {
        $db = Database::getInstance();
        return $db->getConnection();
    }

    /* ============ INVOICE HANDLING ============ */

    /**
     * Xem hóa đơn (trả về thông tin cho AJAX)
     */
    private static function viewInvoice(): array
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) return ['ok'=>false, 'message'=>'Thiếu id'];

        // Kiểm tra xem hóa đơn đã tồn tại chưa
        if (!InvoiceGenerator::invoiceExists($id)) {
            // Tự động tạo hóa đơn nếu chưa có
            $generated = InvoiceGenerator::generateInvoice($id);
            if (!$generated) {
                return ['ok'=>false, 'message'=>'Không thể tạo hóa đơn'];
            }
        }

        // Trả về URL để mở hóa đơn
        return [
            'ok' => true,
            'invoice_url' => "orders.php?view_invoice=1&id={$id}"
        ];
    }

    /**
     * Hiển thị hóa đơn HTML
     */
    private static function displayInvoice(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo "Thiếu mã đơn hàng";
            return;
        }

        $invoicePath = InvoiceGenerator::getInvoicePath($id);
        if (!$invoicePath) {
            echo "Không tìm thấy hóa đơn";
            return;
        }

        // Hiển thị file hóa đơn
        readfile($invoicePath);
    }

    /**
     * Tạo hóa đơn thủ công (nếu cần)
     */
    private static function generateInvoice(): array
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) return ['ok'=>false, 'message'=>'Thiếu id'];

        $success = InvoiceGenerator::generateInvoice($id);
        return $success
            ? ['ok'=>true, 'message'=>'Đã tạo hóa đơn thành công']
            : ['ok'=>false, 'message'=>'Không thể tạo hóa đơn'];
    }

    /* ============ LIST ============ */

    /**
     * Lấy danh sách đơn cho UI hiện tại.
     * Trả về: [$rows, $page, $totalPages]
     */
    private static function fetchList(): array
    {
        $conn = self::getConnection();

        $search     = trim((string)($_GET['search'] ?? ''));
        $ym         = trim((string)($_GET['date'] ?? ''));
        $statusText = trim((string)($_GET['status'] ?? 'All'));
        $dateFrom   = trim((string)($_GET['date_from'] ?? ''));
        $dateTo     = trim((string)($_GET['date_to'] ?? ''));

        $where = [];
        $params = [];
        $types = '';

        if ($search !== '') {
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
              nd.Hang        AS customer_rank,
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
            $dbStatus = trim($r['TrangThai']);
            $uiStatus = self::DB2UI[$dbStatus] ?? $dbStatus;

            $rows[] = [
                'MaDon'      => (string)$r['MaDonHang'],
                'KhachHang'  => (string)($r['customer_name'] ?? 'Khách lẻ'),
                'HangTV'     => (string)($r['customer_rank'] ?? 'Mới'),
                'Ban'        => $r['ban_id'] ? ('Bàn '.$r['ban_id']) : '-',
                'NgayDat'    => date('Y-m-d H:i', strtotime($r['NgayDat'] ?? 'now')),
                'TongTien'   => (float)($r['TongTien'] ?? 0),
                'TrangThai'  => $uiStatus,
                'TrangThaiDB'=> $dbStatus,
            ];
        }
        $stmt->close();

        return [$rows, $page, $totalPages];
    }

    /* ============ STATUS ACTIONS ============ */

    // Trong method updateStatus - dòng 249
    private static function updateStatus(string $to): array
    {
        $conn = self::getConnection();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) return ['ok'=>false,'message'=>'Thiếu id'];

        $stmt = $conn->prepare("UPDATE donhang SET TrangThai=? WHERE MaDonHang=?");
        $stmt->bind_param('si', $to, $id);
        $ok = $stmt->execute();
        $stmt->close();

        // ✅ Tự động tạo hóa đơn khi hoàn thành
        if ($ok && $to === 'CONFIRMED') {
            InvoiceGenerator::generateInvoice($id);
        }

        return $ok ? ['ok'=>true,'message'=>'Đã cập nhật trạng thái'] : ['ok'=>false,'message'=>'Cập nhật thất bại'];
    }

    private static function changeStatusByText(): array
    {
        $conn = self::getConnection();
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
        $conn = self::getConnection();
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