<?php
declare(strict_types=1);
session_start();

/* Load database.php */
$paths = [
    __DIR__ . '/../model/database.php',
    __DIR__ . '/../database.php',
];
$loaded = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $loaded = true; break; }
}
if (!$loaded) { http_response_code(500); exit('database.php not found'); }

$db = (new Database())->connect();

/* Helpers */
function flash(string $type, string $msg): void { $_SESSION[$type] = $msg; }
function redirect(string $url): never { header("Location: $url"); exit; }

/* Router: xử lý POST login */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_login') {
    handleAdminLogin($db);
}

/**
 * Đăng nhập admin: Username + Password
 */
function handleAdminLogin(PDO $db): void {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        flash('error', 'Vui lòng nhập username và mật khẩu.');
        redirect('/Webmarket/view/html/admin_login.php');
    }

    $stmt = $db->prepare(
        "SELECT MaNguoiDung, Username, HoTen, Email, MatKhau, VaiTro, TrangThai
         FROM NguoiDung
         WHERE Username = :u
         LIMIT 1"
    );
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        flash('error', 'Tài khoản không tồn tại.');
        redirect('/Webmarket/view/html/admin_login.php');
    }

    if (!password_verify($password, (string)$user['MatKhau'])) {
        flash('error', 'Mật khẩu không đúng.');
        redirect('/Webmarket/view/html/admin_login.php');
    }

    if ((int)$user['TrangThai'] === 0) {
        flash('error', 'Tài khoản đã bị khóa.');
        redirect('/Webmarket/view/html/admin_login.php');
    }

    if (strtoupper($user['VaiTro'] ?? '') !== 'ADMIN') {
        flash('error', 'Bạn không có quyền truy cập trang quản trị.');
        redirect('/Webmarket/view/html/admin_login.php');
    }

    // Đăng nhập thành công
    $_SESSION['admin_id']    = (int)$user['MaNguoiDung'];
    $_SESSION['admin_name']  = $user['HoTen'] ?? $user['Username'];
    $_SESSION['admin_email'] = $user['Email'] ?? null;

    flash('success', 'Đăng nhập quản trị thành công!');
    redirect('/Webmarket/view/html/admin_dashboard.php');
}
