<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../model/database.php';        // Database::getInstance()->getConnection() (mysqli)
require_once __DIR__ . '/../auth/google_auth.php';// lớp GoogleOAuth ở trên

final class GoogleLoginController
{
    private mysqli $db;
    private GoogleOAuth $google;

    public function __construct(mysqli $db, GoogleOAuth $google)
    {
        $this->db     = $db;
        $this->google = $google;
    }

    public function handle(): void
    {
        $action = $_GET['action'] ?? 'start';
        if ($action === 'start') {
            $this->start();
        } elseif ($action === 'callback') {
            $this->callback();
        } else {
            $this->start();
        }
    }

    private function start(): void
    {
        $url = $this->google->buildAuthUrl();
        header('Location: ' . $url);
        exit;
    }

    private function callback(): void
    {
        // Kiểm tra CSRF state
        if (!isset($_GET['state'], $_SESSION['g_state']) || $_GET['state'] !== $_SESSION['g_state']) {
            $_SESSION['error'] = 'Yêu cầu không hợp lệ (state mismatch).';
            header('Location: /Webmarket/view/html/login.php'); exit;
        }
        unset($_SESSION['g_state']);

        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $_SESSION['error'] = 'Thiếu mã xác thực Google.';
            header('Location: /Webmarket/view/html/login.php'); exit;
        }

        $tok = $this->google->exchangeCode($code);
        if (!$tok || empty($tok['access_token'])) {
            $_SESSION['error'] = 'Không đổi được access token từ Google.';
            header('Location: /Webmarket/view/html/login.php'); exit;
        }

        $u = $this->google->fetchUserInfo($tok['access_token']);
        if (!$u || empty($u['email'])) {
            $_SESSION['error'] = 'Không lấy được thông tin người dùng Google.';
            header('Location: /Webmarket/view/html/login.php'); exit;
        }

        // Lưu/đăng nhập người dùng theo Email (Email unique trong bảng nguoidung)
        $userId = $this->findOrCreateUserByEmail(
            email: (string)$u['email'],
            fullname: (string)($u['name'] ?? ''),
            suggestedUsername: (string)($u['email'] ?? '')
        );

        // Tạo session giống phần admin đang dùng
        $_SESSION['user_id']  = $userId;
        $_SESSION['user_name'] = $this->getFullnameById($userId) ?? ($u['name'] ?? 'User');

        // Chuyển hướng sau đăng nhập
        header('Location: /Webmarket/view/html/home.php');
        exit;
    }

    private function getFullnameById(int $id): ?string
    {
        $stmt = $this->db->prepare("SELECT HoTen FROM nguoidung WHERE MaNguoiDung=? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row['HoTen'] ?? null;
    }

    private function findOrCreateUserByEmail(string $email, string $fullname, string $suggestedUsername): int
    {
        // 1) Tìm theo email
        $stmt = $this->db->prepare("SELECT MaNguoiDung FROM nguoidung WHERE Email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $stmt->close();
            return (int)$row['MaNguoiDung'];
        }
        $stmt->close();

        // 2) Tạo mới
        $username = $this->makeUniqueUsername($suggestedUsername);
        $hoten    = trim($fullname) !== '' ? $fullname : $username;

        // Tạo password ngẫu nhiên (để đủ dữ liệu, không dùng để đăng nhập pass thường)
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO nguoidung (Username, HoTen, Email, MatKhau, VaiTro, TrangThai, Hang, TongChiTieu)
            VALUES (?, ?, ?, ?, 'USER', 1, 'Mới', 0.00)
        ");
        $stmt->bind_param('ssss', $username, $hoten, $email, $randomPassword);
        $stmt->execute();
        $newId = $this->db->insert_id;
        $stmt->close();

        return (int)$newId;
    }

    private function makeUniqueUsername(string $fromEmail): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9_]+/i', '', explode('@', $fromEmail)[0] ?? 'user'));
        if ($base === '') $base = 'user';
        $u = $base;
        $i = 1;
        while ($this->usernameExists($u)) {
            $u = $base . $i;
            $i++;
        }
        return $u;
    }

    private function usernameExists(string $u): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM nguoidung WHERE Username=? LIMIT 1");
        $stmt->bind_param('s', $u);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }
}

// --- Bootstrap
$dbConn  = Database::getInstance()->getConnection(); // mysqli
$google  = new GoogleOAuth();
$ctrl    = new GoogleLoginController($dbConn, $google);
$ctrl->handle();
