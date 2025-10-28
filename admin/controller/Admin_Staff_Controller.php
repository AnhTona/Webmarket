<?php
// admin/controller/Admin_Staff_Controller.php
require_once __DIR__ . '/../../model/database.php';

class AdminStaffController
{
    private const PER_PAGE = 10; // 10 admin/staff mỗi trang

    public static function handle(): array
    {
        $conn = self::connect();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = $_GET['action'] ?? ($_POST['action'] ?? 'index');

        if ($method === 'POST' && $action === 'save') {
            return self::save($conn);
        }
        if ($action === 'delete') {
            return self::delete($conn);
        }
        if ($action === 'toggle') {
            return self::toggleStatus($conn);
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
        $per_page = self::PER_PAGE;
        $page     = max(1, (int)($_GET['page'] ?? 1));

        $search   = trim($_GET['q'] ?? ($_GET['search'] ?? ''));
        $status   = $_GET['status'] ?? 'All';
        $role     = $_GET['role']   ?? 'All';

        $w = ["nd.VaiTro IN ('ADMIN', 'STAFF')"]; // Chỉ lấy ADMIN và STAFF
        $p = [];

        if ($search !== '') {
            $like = '%'.$search.'%';
            $w[] = '(nd.HoTen LIKE ? OR nd.Email LIKE ? OR nd.SoDienThoai LIKE ? OR nd.Username LIKE ? OR CAST(nd.MaNguoiDung AS CHAR) LIKE ?)';
            array_push($p, $like, $like, $like, $like, $like);
        }
        if ($status !== 'All') {
            $w[] = ($status === 'Hoạt động') ? 'nd.TrangThai = 1' : 'nd.TrangThai = 0';
        }
        if ($role !== 'All') {
            $w[] = 'nd.VaiTro = ?';
            $p[] = strtoupper($role);
        }

        $where = 'WHERE ' . implode(' AND ', $w);

        // Count
        $sqlCount = "SELECT COUNT(*) AS total FROM nguoidung nd $where";
        $total_admins = (int) self::fetchValue($conn, $sqlCount, $p);

        $total_pages = max(1, (int)ceil($total_admins / $per_page));
        $page = min($page, $total_pages);
        $offset = ($page - 1) * $per_page;

        // List - BỎ NgayCapNhat
        $sqlList = "
            SELECT
                nd.MaNguoiDung       AS id,
                nd.Username          AS username,
                nd.HoTen             AS name,
                nd.Email             AS email,
                nd.SoDienThoai       AS phone,
                nd.VaiTro            AS role,
                CASE WHEN nd.TrangThai=1 THEN 'Hoạt động' ELSE 'Ngừng' END AS status,
                DATE_FORMAT(nd.NgayTao, '%d/%m/%Y %H:%i') AS created_at
            FROM nguoidung nd
            $where
            ORDER BY nd.NgayTao DESC, nd.MaNguoiDung DESC
            LIMIT ?, ?
        ";
        $listParams = array_merge($p, [$offset, $per_page]);
        $admin_list = self::fetchAll($conn, $sqlList, $listParams) ?? [];

        return compact('admin_list','per_page','page','total_pages','total_admins');
    }

    /* ===== Create/Update Admin/Staff ===== */
    private static function save(mysqli $conn): array
    {
        $id       = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $username = trim($_POST['username'] ?? '');
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $role     = strtoupper(trim($_POST['role'] ?? 'STAFF'));
        $password = trim($_POST['password'] ?? '');
        $status   = trim($_POST['status'] ?? 'Hoạt động');

        if ($username === '' || $name === '' || $email === '') {
            return self::respond(false, 'Vui lòng nhập đầy đủ Username, Họ tên, Email');
        }

        if (!in_array($role, ['ADMIN', 'STAFF'])) {
            return self::respond(false, 'Vai trò không hợp lệ');
        }

        $trangThai = ($status === 'Hoạt động') ? 1 : 0;

        if ($id) {
            // UPDATE - Kiểm tra username trùng với tài khoản khác
            $check = self::fetchOne($conn,
                "SELECT MaNguoiDung FROM nguoidung WHERE Username=? AND MaNguoiDung != ?",
                [$username, $id]
            );
            if ($check) {
                return self::respond(false, 'Username đã tồn tại cho tài khoản khác');
            }

            // Kiểm tra email trùng với tài khoản khác
            $checkEmail = self::fetchOne($conn,
                "SELECT MaNguoiDung FROM nguoidung WHERE Email=? AND MaNguoiDung != ?",
                [$email, $id]
            );
            if ($checkEmail) {
                return self::respond(false, 'Email đã tồn tại cho tài khoản khác');
            }

            // Update
            if ($password !== '') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE nguoidung
                    SET Username=?, HoTen=?, Email=?, SoDienThoai=?, VaiTro=?, MatKhau=?, TrangThai=?
                    WHERE MaNguoiDung=?";
                $ok  = self::exec($conn, $sql, [$username, $name, $email, $phone, $role, $hashedPassword, $trangThai, $id]);
            } else {
                $sql = "UPDATE nguoidung
                    SET Username=?, HoTen=?, Email=?, SoDienThoai=?, VaiTro=?, TrangThai=?
                    WHERE MaNguoiDung=?";
                $ok  = self::exec($conn, $sql, [$username, $name, $email, $phone, $role, $trangThai, $id]);
            }

            if (!$ok) {
                return self::respond(false, 'Cập nhật thất bại. Vui lòng thử lại!');
            }

            return self::respond(true, 'Cập nhật thành công!', ['id'=>$id]);

        } else {
            // CREATE - Bắt buộc có mật khẩu
            if ($password === '') {
                return self::respond(false, 'Vui lòng nhập mật khẩu khi tạo tài khoản mới');
            }

            // Kiểm tra username đã tồn tại chưa
            $checkUsername = self::fetchOne($conn, "SELECT MaNguoiDung FROM nguoidung WHERE Username=?", [$username]);
            if ($checkUsername) {
                return self::respond(false, 'Username "' . $username . '" đã tồn tại. Vui lòng chọn username khác!');
            }

            // Kiểm tra email đã tồn tại chưa
            $checkEmail = self::fetchOne($conn, "SELECT MaNguoiDung FROM nguoidung WHERE Email=?", [$email]);
            if ($checkEmail) {
                return self::respond(false, 'Email "' . $email . '" đã được đăng ký. Vui lòng sử dụng email khác!');
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO nguoidung (Username, MatKhau, HoTen, Email, SoDienThoai, VaiTro, TrangThai, NgayTao)
                VALUES (?,?,?,?,?,?,?, NOW())";
            $ok  = self::exec($conn, $sql, [$username, $hashedPassword, $name, $email, $phone, $role, $trangThai]);

            if (!$ok) {
                // Log lỗi để debug
                error_log("Failed to insert user: " . $conn->error);
                return self::respond(false, 'Thêm tài khoản thất bại. Lỗi: ' . $conn->error);
            }

            $id = (int)$conn->insert_id;
            return self::respond(true, 'Thêm tài khoản thành công!', ['id'=>$id]);
        }
    }

    /* ===== Toggle Status ===== */
    private static function toggleStatus(mysqli $conn): array
    {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) return self::respond(false, 'Thiếu mã tài khoản');

        $row = self::fetchOne($conn, "SELECT TrangThai, VaiTro FROM nguoidung WHERE MaNguoiDung=?", [$id]);
        if (!$row) return self::respond(false, 'Không tìm thấy tài khoản');

        // Không cho tắt tài khoản ADMIN đang đăng nhập
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            return self::respond(false, 'Không thể tắt tài khoản đang đăng nhập');
        }

        $new = ((int)$row['TrangThai'] === 1) ? 0 : 1;
        $ok  = self::exec($conn, "UPDATE nguoidung SET TrangThai=? WHERE MaNguoiDung=?", [$new, $id]);
        if (!$ok) return self::respond(false, 'Cập nhật trạng thái thất bại');
        return self::respond(true, 'Đã cập nhật trạng thái');
    }

    /* ===== Delete ===== */
    private static function delete(mysqli $conn): array
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) return self::respond(false, 'Thiếu mã tài khoản');

        // Không cho xóa chính mình
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            return self::respond(false, 'Không thể xóa tài khoản đang đăng nhập');
        }

        // Kiểm tra xem có phải ADMIN/STAFF không
        $user = self::fetchOne($conn, "SELECT VaiTro FROM nguoidung WHERE MaNguoiDung=?", [$id]);
        if (!$user) return self::respond(false, 'Không tìm thấy tài khoản');
        if (!in_array(strtoupper($user['VaiTro']), ['ADMIN', 'STAFF'])) {
            return self::respond(false, 'Chỉ có thể xóa tài khoản Admin/Staff');
        }

        $ok = self::exec($conn, "DELETE FROM nguoidung WHERE MaNguoiDung=?", [$id]);
        if (!$ok) return self::respond(false, 'Xóa thất bại');
        return self::respond(true, 'Đã xóa tài khoản');
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
        header('Location: admin_staff.php?'. ($ok?'success=':'error=').urlencode($message));
        exit;
    }
}