<?php
// admin/controller/CustomersController.php
class CustomersController
{
    // Ngưỡng hạng & thời gian tính
    private const RANK_WINDOW_DAYS = 365;      // 12 tháng gần nhất
    private const RANK_SILVER_MIN  = 2_000_000;
    private const RANK_GOLD_MIN    = 5_000_000;

// Trạng thái đơn được tính doanh số
    private static function paidStatuses(): array {
        return ['CONFIRMED','SHIPPING','DONE'];
    }

// Map tổng chi tiêu -> hạng
    private static function rankFromAmount(int|float $amount): string {
        if ($amount >= self::RANK_GOLD_MIN)   return 'Gold';
        if ($amount >= self::RANK_SILVER_MIN) return 'Silver';
        if ($amount > 0)                      return 'Bronze';
        return 'Mới';
    }
    public static function handle(): array
    {
        $conn = self::connect();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = $_GET['action'] ?? ($_POST['action'] ?? 'index');

        if ($method === 'POST' && in_array($action, ['save','create','update'], true)) {
            return self::save($conn);     // JSON hoặc redirect rồi exit
        }
        if ($method === 'GET' && $action === 'delete') {
            return self::delete($conn);   // JSON hoặc redirect rồi exit
        }
        if ($action === 'toggle') {       // cho phép GET/POST
            return self::toggle($conn);
        }
        if ($action === 'recompute_all') {
            return self::recomputeAll($conn);
        }

        return self::index($conn);
    }

    /* ===== DB Connect ===== */
    private static function connect(): mysqli
    {
        $candidates = [
            dirname(__DIR__, 2) . '/model/db.php',
            dirname(__DIR__)    . '/model/db.php',
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

    /* ===== Danh sách + Filter ===== */
    private static function index(mysqli $conn): array
    {
        $per_page = max(1, (int)($_GET['per_page'] ?? 10));
        $page     = max(1, (int)($_GET['page'] ?? 1));

        $search   = trim($_GET['q'] ?? ($_GET['search'] ?? ''));
        $status   = $_GET['status'] ?? 'All';          // Hoạt động | Ngưng | All
        $rank     = $_GET['rank']   ?? 'All';          // Gold | Silver | Bronze | Mới | All
        $city     = trim($_GET['city'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to'] ?? '');

        $w = []; $p = [];

        if ($search !== '') {
            $like = '%'.$search.'%';
            $w[] = '(nd.HoTen LIKE ? OR nd.Email LIKE ? OR nd.SoDienThoai LIKE ? OR nd.Username LIKE ? OR CAST(nd.MaNguoiDung AS CHAR) LIKE ?)';
            array_push($p, $like, $like, $like, $like, $like);
        }
        if ($status !== 'All') {
            $w[] = ($status === 'Hoạt động') ? 'nd.TrangThai = 1' : 'nd.TrangThai = 0';
        }
        if ($rank !== 'All') {
            $w[] = 'nd.Hang = ?'; $p[] = $rank; // 'Gold' | 'Silver' | 'Bronze' | 'Mới'
        }
        if ($city !== '') {
            $w[] = 'nd.DiaChi LIKE ?'; $p[] = '%'.$city.'%';
        }
        if ($dateFrom !== '') {
            $w[] = 'DATE(nd.NgayTao) >= ?'; $p[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $w[] = 'DATE(nd.NgayTao) <= ?'; $p[] = $dateTo;
        }

        $where = $w ? ('WHERE '.implode(' AND ', $w)) : '';

        // Count
        $sqlCount = "SELECT COUNT(*) AS total FROM nguoidung nd $where";
        $total_customers = (int) self::fetchValue($conn, $sqlCount, $p);

        $total_pages = max(1, (int)ceil($total_customers / $per_page));
        $page = min($page, $total_pages);
        $offset = ($page - 1) * $per_page;

        // List
        $sqlList = "
            SELECT
                nd.MaNguoiDung       AS id,
                nd.HoTen             AS name,
                nd.Email             AS email,
                nd.SoDienThoai       AS phone,
                COALESCE(nd.DiaChi,'') AS address,
                COALESCE(nd.VaiTro,'USER') AS role,
                COALESCE(nd.Hang,'Mới')   AS rank,
                CASE WHEN nd.TrangThai=1 THEN 'Hoạt động' ELSE 'Ngưng' END AS status,
                DATE(nd.NgayTao)     AS created_at
            FROM nguoidung nd
            $where
            ORDER BY nd.NgayTao DESC, nd.MaNguoiDung DESC
            LIMIT ?, ?
        ";
        $listParams = array_merge($p, [$offset, $per_page]);
        $customer_list = self::fetchAll($conn, $sqlList, $listParams) ?? [];

        return compact('customer_list','per_page','page','total_pages','total_customers');
    }

    /* ===== Create/Update ===== */
    private static function save(mysqli $conn): array
    {
        $id      = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $phone   = trim($_POST['phone']   ?? '');
        $address = trim($_POST['address'] ?? '');
        $status  = trim($_POST['status']  ?? 'Hoạt động');   // Hoạt động | Ngưng
        $rank    = trim($_POST['rank']    ?? 'Mới');          // Gold | Silver | Bronze | Mới

        if ($name === '' || $email === '' || $phone === '') {
            return self::respond(false, 'Vui lòng nhập đầy đủ Họ tên, Email, SĐT');
        }
        $trangThai = ($status === 'Hoạt động') ? 1 : 0;

        if ($id) {
            $sql = "UPDATE nguoidung
                    SET HoTen=?, Email=?, SoDienThoai=?, DiaChi=?, TrangThai=?, Hang=?
                    WHERE MaNguoiDung=?";
            $ok  = self::exec($conn, $sql, [$name,$email,$phone,$address,$trangThai,$rank,$id]);
            if (!$ok) return self::respond(false, 'Cập nhật thất bại');
        } else {
            $sql = "INSERT INTO nguoidung (HoTen, Email, SoDienThoai, DiaChi, TrangThai, Hang, NgayTao)
                    VALUES (?,?,?,?,?,?, NOW())";
            $ok  = self::exec($conn, $sql, [$name,$email,$phone,$address,$trangThai,$rank]);
            if (!$ok) return self::respond(false, 'Thêm khách hàng thất bại');
            $id = (int)$conn->insert_id;
        }

        self::recomputeRank($conn, $id);
        return self::respond(true, 'Lưu thành công', ['id'=>$id]);
    }

    /* ===== Toggle Status ===== */
    private static function toggle(mysqli $conn): array
    {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) return self::respond(false, 'Thiếu mã KH');

        $row = self::fetchOne($conn, "SELECT TrangThai FROM nguoidung WHERE MaNguoiDung=?", [$id]);
        if (!$row) return self::respond(false, 'Không tìm thấy KH');

        $new = ((int)$row['TrangThai'] === 1) ? 0 : 1;
        $ok  = self::exec($conn, "UPDATE nguoidung SET TrangThai=? WHERE MaNguoiDung=?", [$new, $id]);
        if (!$ok) return self::respond(false, 'Cập nhật trạng thái thất bại');
        self::recomputeRank($conn, $id);
        return self::respond(true, 'Đã cập nhật trạng thái');
    }

    /* ===== Delete ===== */
    private static function delete(mysqli $conn): array
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return self::respond(false, 'Thiếu mã KH');
        $ok = self::exec($conn, "DELETE FROM nguoidung WHERE MaNguoiDung=?", [$id]);
        if (!$ok) return self::respond(false, 'Xóa thất bại');
        return self::respond(true, 'Đã xóa khách hàng');
    }

    /* ===== Helpers ===== */
    private static function fetchValue(mysqli $conn, string $sql, array $params=[]){
        $r=self::fetchOne($conn,$sql,$params); return $r?array_values($r)[0]:null;
    }
    private static function fetchOne(mysqli $conn, string $sql, array $params=[]): ?array {
        $rows=self::fetchAll($conn,$sql,$params); return $rows[0]??null;
    }
    private static function fetchAll(mysqli $conn, string $sql, array $params=[]): array {
        $stmt=$conn->prepare($sql); if(!$stmt) return [];
        if ($params) self::bind($stmt,$params);
        $stmt->execute(); $res=$stmt->get_result(); $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[];
        $stmt->close(); return $rows;
    }
    private static function exec(mysqli $conn, string $sql, array $params=[]): bool {
        $stmt=$conn->prepare($sql); if(!$stmt) return false;
        if ($params) self::bind($stmt,$params);
        $ok=$stmt->execute(); $stmt->close(); return (bool)$ok;
    }
    private static function bind(mysqli_stmt $stmt, array $params): void {
        $types=''; $bind=[];
        foreach($params as $p){ $types .= is_int($p)?'i':(is_float($p)?'d':'s'); $bind[]=$p; }
        $stmt->bind_param($types, ...self::byref($bind));
    }
    private static function byref(array $a): array { foreach($a as $k=>$v){ $a[$k]=&$a[$k]; } return $a; }

    private static function isAjax(): bool {
        return (($_GET['ajax'] ?? '') === '1') ||
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest');
    }
    private static function respond(bool $ok, string $message, array $extra=[]): array
    {
        $payload = array_merge(['ok'=>$ok,'message'=>$message], $extra);
        if (self::isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Location: customers.php?'. ($ok?'success=':'error=').urlencode($message));
        exit;
    }
    // Tính tổng chi tiêu 12 tháng gần nhất và cập nhật cột Hang cho 1 user
    private static function recomputeRank(mysqli $conn, int $userId): void
    {
        // Tổng tiền các đơn đã hoàn tất trong 12 tháng gần nhất
        $statuses = self::paidStatuses();
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql = "
        SELECT COALESCE(SUM(TongTien),0) AS amt
        FROM donhang
        WHERE MaNguoiDung = ?
          AND TrangThai IN ($placeholders)
          AND NgayDat >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    ";
        $params = array_merge([$userId], $statuses, [self::RANK_WINDOW_DAYS]);
        $row = self::fetchOne($conn, $sql, $params);
        $amt = (int)($row['amt'] ?? 0);

        $rank = self::rankFromAmount($amt);
        self::exec($conn, "UPDATE nguoidung SET Hang=? WHERE MaNguoiDung=?", [$rank, $userId]);
    }

    // Recompute cho toàn bộ user (dùng khi bạn đổi ngưỡng/hệ thống tính)
    private static function recomputeAll(mysqli $conn): array
    {
        $ids = self::fetchAll($conn, "SELECT MaNguoiDung AS id FROM nguoidung");
        foreach ($ids as $r) {
            self::recomputeRank($conn, (int)$r['id']);
        }
        return self::respond(true, 'Đã tính lại hạng cho tất cả khách hàng');
    }

}
