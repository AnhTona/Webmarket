<?php
// admin/controller/Auth_Controller.php
session_start();
require_once __DIR__ . '/../../model/db.php'; // tạo $conn (MySQLi)

class AuthController {
    public static function handle(): array {
        // Logout
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            self::logout();
        }

        // Đã đăng nhập thì vào dashboard
        if (isset($_SESSION['user_id'])) {
            self::redirect('/Webmarket/admin/html/dashboard.php');
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$ok, $msg] = self::login($_POST['username'] ?? '', $_POST['password'] ?? '');
            if ($ok) {
                self::redirect('/Webmarket/admin/html/dashboard.php');
            } else {
                $error = $msg ?: 'Đăng nhập thất bại';
            }
        }
        return ['error' => $error];
    }

    private static function login(string $username, string $password): array {
        global $conn;
        if (!$conn) return [false, 'Không kết nối được DB'];

        $u = trim($username);
        $p = (string)$password;
        if ($u === '' || $p === '') return [false, 'Vui lòng nhập đủ tên đăng nhập và mật khẩu'];

        $sql = "SELECT MaNguoiDung, Username, MatKhau, VaiTro, TrangThai
                FROM nguoidung
                WHERE Username = ?
                LIMIT 1";

        if (!($stmt = $conn->prepare($sql))) {
            return [false, 'Lỗi chuẩn bị truy vấn'];
        }
        $stmt->bind_param('s', $u);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) return [false, 'Sai tên đăng nhập hoặc mật khẩu'];

        // Khóa tài khoản?
        if ((int)$row['TrangThai'] !== 1) {
            return [false, 'Tài khoản đã bị khóa'];
        }

        // Chỉ cho ADMIN/STAFF vào admin
        $role = strtoupper((string)$row['VaiTro']);
        if ($role !== 'ADMIN' && $role !== 'STAFF') {
            return [false, 'Tài khoản không có quyền truy cập trang quản trị'];
        }

        // Hỗ trợ cả hash và (tạm thời) plain text để bạn chuyển dần
        $hash = (string)$row['MatKhau'];
        $valid = password_verify($p, $hash) || hash_equals($hash, $p);
        if (!$valid) return [false, 'Sai tên đăng nhập hoặc mật khẩu'];

        // OK -> tạo session
        session_regenerate_id(true);
        $_SESSION['user_id']  = (int)$row['MaNguoiDung'];
        $_SESSION['username'] = $row['Username'];
        $_SESSION['role']     = $role;

        return [true, null];
    }

    private static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::redirect('/Webmarket/admin/html/login.php');
    }

    private static function redirect(string $path): void {
        header("Location: http://localhost{$path}");
        exit();
    }
}
