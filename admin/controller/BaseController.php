<?php
declare(strict_types=1);

/**
 * AuthController.php
 * Authentication controller with PHP 8.4 features
 *
 * @package Admin\Controller
 */

require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/BaseController.php';

final class AuthController extends BaseController
{
    /**
     * Handle authentication requests
     *
     * @return array<string, mixed>
     */
    public static function handle(): array
    {
        // Logout action
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            self::logout();
        }

        // Already logged in
        if (isset($_SESSION['user_id'])) {
            self::redirect('/Webmarket/admin/html/dashboard.php');
        }

        // Handle login POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$success, $message] = self::login(
                username: $_POST['username'] ?? '',
                password: $_POST['password'] ?? ''
            );

            if ($success) {
                self::redirect('/Webmarket/admin/html/dashboard.php');
            }

            return ['error' => $message ?? 'Đăng nhập thất bại'];
        }

        return ['error' => null];
    }

    /**
     * Login user
     *
     * @return array{bool, ?string}
     */
    private static function login(
        string $username,
        string $password
    ): array {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return [false, 'Vui lòng nhập đủ tên đăng nhập và mật khẩu'];
        }

        try {
            $user = self::fetchRow(
                sql: "SELECT MaNguoiDung, Username, MatKhau, VaiTro, TrangThai
                      FROM nguoidung
                      WHERE Username = ?
                      LIMIT 1",
                params: [$username]
            );

            if (!$user) {
                return [false, 'Sai tên đăng nhập hoặc mật khẩu'];
            }

            // Check account status
            if ((int)$user['TrangThai'] !== 1) {
                return [false, 'Tài khoản đã bị khóa'];
            }

            // Check role
            $role = strtoupper($user['VaiTro']);
            if (!in_array($role, ['ADMIN', 'STAFF'], true)) {
                return [false, 'Tài khoản không có quyền truy cập'];
            }

            // Verify password
            $hash = $user['MatKhau'];
            $validPassword = password_verify($password, $hash) ||
                hash_equals($hash, $password); // Backward compat

            if (!$validPassword) {
                self::log('failed_login', ['username' => $username]);
                return [false, 'Sai tên đăng nhập hoặc mật khẩu'];
            }

            // Create session
            session_regenerate_id(deleteOldSession: true);

            $_SESSION['user_id'] = (int)$user['MaNguoiDung'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $role;
            $_SESSION['last_activity'] = time();

            self::log('successful_login', ['username' => $username]);

            return [true, null];

        } catch (Throwable $e) {
            error_log("Login error: " . $e->getMessage());
            return [false, 'Lỗi hệ thống. Vui lòng thử lại.'];
        }
    }

    /**
     * Logout user
     */
    private static function logout(): never
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $username = $_SESSION['username'] ?? 'unknown';

        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(
                name: session_name(),
                value: '',
                expires_or_options: time() - 3600,
                path: '/'
            );
        }

        session_destroy();

        self::log('logout', ['username' => $username]);
        self::redirect('/Webmarket/admin/html/login.php');
    }

    /**
     * Redirect helper
     */
    private static function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }
}