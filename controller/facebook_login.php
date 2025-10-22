<?php
declare(strict_types=1);

// controller/facebook_login.php
session_start();

require_once __DIR__ . '/../model/database.php';         // Database::getInstance()->getConnection() -> mysqli
require_once __DIR__ . '/../auth/facebook_auth.php';

final class FacebookLoginController
{
    private mysqli $db;
    private FacebookOAuth $fb;

    public function __construct(mysqli $db, FacebookOAuth $fb)
    {
        $this->db = $db;
        $this->fb = $fb;
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
        $url = $this->fb->buildAuthUrl();
        header('Location: ' . $url);
        exit;
    }

    private function callback(): void
    {
        // CSRF check
        if (!isset($_GET['state'], $_SESSION['fb_state']) || $_GET['state'] !== $_SESSION['fb_state']) {
            $_SESSION['error'] = 'Yêu cầu không hợp lệ (state).';
            header('Location: /Webmarket/view/html/login.php'); exit;
        }
        unset($_SESSION['fb_state']);

        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $_SESSION['error'] = 'Thiếu mã code từ Facebook.';
            header('Location: /Webmarket/view/html/login.php'); exit;
        }

        $token = $this->fb->exchangeCode($code);
        if (!$token || empty($token['access_token'])) {
            $_SESSION['error'] = 'Không đổi được access token từ Facebook.';
            header('Location: /Webmarket/view/html/login.php'); exit;
        }

        $user = $this->fb->fetchUser($token['access_token']);
        if (!$user || (empty($user['email']) && empty($user['id']))) {
            $_SESSION['error'] = 'Không lấy được thông tin người dùng Facebook.';
            header('Location: /Webmarket/view/html/login.php'); exit;
        }

        // Email có thể bị ẩn; tạo fallback dạng fb_{id}@facebook.local để thỏa unique index Email
        $email = (string)($user['email'] ?? '');
        if ($email === '' && !empty($user['id'])) {
            $email = 'fb_' . $user['id'] . '@facebook.local';
        }

        $fullname  = (string)($user['name'] ?? '');
        $suggested = $email !== '' ? $email : ('fbuser' . ($user['id'] ?? rand(1000,9999)));

        $userId = $this->findOrCreateUserByEmail($email, $fullname, $suggested);

        // Set session giống hệ thống sẵn có
        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $this->getFullnameById($userId) ?? ($fullname !== '' ? $fullname : 'User');

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
        // 1) Tìm theo Email
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

        // Tạo password ngẫu nhiên (không dùng để đăng nhập password thường)
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

    private function makeUniqueUsername(string $fromEmailOrName): string
    {
        // Ưu tiên phần trước @ nếu là email; nếu không thì sanitize toàn chuỗi
        $raw = explode('@', $fromEmailOrName)[0] ?? $fromEmailOrName;
        $base = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $raw));
        if ($base === '') $base = 'fbuser';

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
$dbConn = Database::getInstance()->getConnection(); // mysqli
$fb     = new FacebookOAuth();
$ctrl   = new FacebookLoginController($dbConn, $fb);
$ctrl->handle();
