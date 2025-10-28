<?php
// admin/controller/CustomersController.php
require_once __DIR__ . '/../../model/database.php';

class CustomersController
{
    // Ngưỡng hạng & thời gian tính
    private const RANK_WINDOW_DAYS = 365;      // 12 tháng gần nhất
    private const RANK_SILVER_MIN  = 2_000_000;
    private const RANK_GOLD_MIN    = 5_000_000;
    private const PER_PAGE         = 10;       // 10 khách hàng mỗi trang

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

        if ($action === 'delete') {
            return self::delete($conn);
        }
        if ($action === 'recompute_all') {
            return self::recomputeAll($conn);
        }

        return self::index($conn);
    }

    /* ===== DB Connect - OOP Version ===== */
    private static function connect(): mysqli
    {
        $db = Database::getInstance();
        return $db->getConnection();
    }

    /* ===== Danh sách + Filter ===== */
    private static function index(mysqli $conn): array
    {
        // Cố định 10 user/trang
        $per_page = self::PER_PAGE;
        $page     = max(1, (int)($_GET['page'] ?? 1));

        $search   = trim($_GET['q'] ?? ($_GET['search'] ?? ''));
        $status   = $_GET['status'] ?? 'All';
        $rank     = $_GET['rank']   ?? 'All';

        $w = ["nd.VaiTro = 'USER'"]; // Chỉ lấy USER
        $p = [];

        if ($search !== '') {
            $like = '%'.$search.'%';
            $w[] = '(nd.HoTen LIKE ? OR nd.Email LIKE ? OR nd.SoDienThoai LIKE ? OR nd.Username LIKE ? OR CAST(nd.MaNguoiDung AS CHAR) LIKE ?)';
            array_push($p, $like, $like, $like, $like, $like);
        }
        if ($status !== 'All') {
            $w[] = ($status === 'Hoạt động') ? 'nd.TrangThai = 1' : 'nd.TrangThai = 0';
        }
        if ($rank !== 'All') {
            $w[] = 'nd.Hang = ?';
            $p[] = $rank;
        }

        $where = 'WHERE ' . implode(' AND ', $w);

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
                COALESCE(nd.VaiTro,'USER') AS role,
                COALESCE(nd.Hang,'Mới')   AS rank,
                CASE WHEN nd.TrangThai=1 THEN 'Hoạt động' ELSE 'Ngừng' END AS status,
                DATE_FORMAT(nd.NgayTao, '%d/%m/%Y') AS created_at
            FROM nguoidung nd
            $where
            ORDER BY nd.NgayTao DESC, nd.MaNguoiDung DESC
            LIMIT ?, ?
        ";
        $listParams = array_merge($p, [$offset, $per_page]);
        $customer_list = self::fetchAll($conn, $sqlList, $listParams) ?? [];

        return compact('customer_list','per_page','page','total_pages','total_customers');
    }

    /* ===== Delete ===== */
    private static function delete(mysqli $conn): array
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return self::respond(false, 'Thiếu mã KH');

        // Kiểm tra xem có phải USER không
        $user = self::fetchOne($conn, "SELECT VaiTro FROM nguoidung WHERE MaNguoiDung=?", [$id]);
        if (!$user) return self::respond(false, 'Không tìm thấy khách hàng');
        if (strtoupper($user['VaiTro']) !== 'USER') {
            return self::respond(false, 'Không thể xóa tài khoản Admin/Staff');
        }

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
        $stmt->bind_param($types, ...$bind);
    }

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

    // Recompute cho toàn bộ user
    private static function recomputeAll(mysqli $conn): array
    {
        $ids = self::fetchAll($conn, "SELECT MaNguoiDung AS id FROM nguoidung WHERE VaiTro='USER'");
        foreach ($ids as $r) {
            self::recomputeRank($conn, (int)$r['id']);
        }
        return self::respond(true, 'Đã tính lại hạng cho tất cả khách hàng');
    }
}