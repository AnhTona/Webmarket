<?php
/**
 * OAuthController.php - Xử lý đăng nhập OAuth (Google, Facebook)
 * OOP Version với Database Singleton Pattern
 */

declare(strict_types=1);

require_once __DIR__ . '/../model/Database.php';

class OAuthController {
    private $db;
    private $conn;

    // Configuration
    private $homeUrl = '/Webmarket/view/html/home.php';
    private $loginUrl = '/Webmarket/view/html/login.php';

    private const SUPPORTED_PROVIDERS = ['google', 'facebook', 'github', 'oauth'];
    private const DEFAULT_ROLE = 'USER';
    private const RANDOM_PASSWORD_LENGTH = 24;

    public function __construct() {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    // --------------- Helper Methods ---------------

    private function flash(string $type, string $message): void {
        $_SESSION[$type] = $message;
    }

    private function redirect(string $url): never {
        header("Location: $url");
        exit;
    }

    private function generateRandomPassword(int $length = self::RANDOM_PASSWORD_LENGTH): string {
        return bin2hex(random_bytes((int)($length / 2)));
    }

    // --------------- Validation Methods ---------------

    private function validateEmail(?string $email): ?string {
        if (!$email) {
            return null;
        }

        $email = trim($email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function validateProvider(string $provider): string {
        $provider = strtolower(trim($provider));
        return in_array($provider, self::SUPPORTED_PROVIDERS) ? $provider : 'oauth';
    }

    private function validateOAuthProfile(array $profile): array {
        $errors = [];

        if (empty($profile['id']) && empty($profile['email'])) {
            $errors[] = 'OAuth profile thiếu email hoặc ID';
        }

        if (isset($profile['email']) && !$this->validateEmail($profile['email'])) {
            $errors[] = 'Email không hợp lệ';
        }

        return $errors;
    }

    // --------------- Username Generation ---------------

    private function sanitizeUsername(string $base): string {
        // Remove special characters, keep only alphanumeric, dot, underscore, dash
        $base = preg_replace('/[^a-z0-9._-]+/i', '', $base);
        $base = strtolower(trim($base));

        return $base !== '' ? $base : 'user';
    }

    private function usernameExists(string $username): bool {
        $sql = "SELECT 1 FROM NguoiDung WHERE Username = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Database error: ' . $this->conn->error);
        }

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    private function generateUniqueUsername(string $base): string {
        $base = $this->sanitizeUsername($base);
        $username = $base;
        $counter = 1;

        while ($this->usernameExists($username)) {
            $username = $base . $counter;
            $counter++;

            // Prevent infinite loop
            if ($counter > 9999) {
                $username = $base . '_' . time();
                break;
            }
        }

        return $username;
    }

    // --------------- Database Methods ---------------

    private function findUserByEmail(string $email): ?array {
        $sql = "SELECT MaNguoiDung, Username, HoTen, Email, VaiTro, TrangThai
                FROM NguoiDung
                WHERE Email = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Database error: ' . $this->conn->error);
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    private function updateUserName(int $userId, string $name): bool {
        $sql = "UPDATE NguoiDung SET HoTen = ? WHERE MaNguoiDung = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('si', $name, $userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    private function createOAuthUser(string $username, string $name, ?string $email): int {
        $passwordHash = password_hash($this->generateRandomPassword(), PASSWORD_DEFAULT);

        $sql = "INSERT INTO NguoiDung (Username, HoTen, Email, MatKhau, VaiTro, TrangThai, NgayTao)
                VALUES (?, ?, ?, ?, ?, 1, NOW())";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Failed to create user: ' . $this->conn->error);
        }

        $role = self::DEFAULT_ROLE;
        $stmt->bind_param('sssss', $username, $name, $email, $passwordHash, $role);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('Failed to insert user: ' . $stmt->error);
        }

        $userId = $this->conn->insert_id;
        $stmt->close();

        return $userId;
    }

    private function logOAuthLogin(int $userId, string $provider): void {
        // Optional: Log OAuth login for analytics
        $sql = "INSERT INTO oauth_logins (user_id, provider, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);

        if ($stmt) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            $stmt->bind_param('isss', $userId, $provider, $ip, $userAgent);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --------------- Session Management ---------------

    private function createUserSession(array $user): void {
        // Regenerate session ID for security
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['MaNguoiDung'];
        $_SESSION['username'] = (string)$user['Username'];
        $_SESSION['email'] = $user['Email'] ?? null;
        $_SESSION['name'] = $user['HoTen'] ?? $user['Username'];
        $_SESSION['role'] = $user['VaiTro'] ?? self::DEFAULT_ROLE;
        $_SESSION['login_time'] = time();
        $_SESSION['login_method'] = 'oauth';
    }

    // --------------- Main OAuth Handler ---------------

    public function handleOAuthLogin(array $profile): never {
        try {
            // Validate profile
            $validationErrors = $this->validateOAuthProfile($profile);
            if (!empty($validationErrors)) {
                $this->flash('error', implode('. ', $validationErrors));
                $this->redirect($this->loginUrl);
            }

            // Extract and validate data
            $provider = $this->validateProvider($profile['provider'] ?? 'oauth');
            $externalId = trim((string)($profile['id'] ?? ''));
            $email = $this->validateEmail($profile['email'] ?? null);
            $name = trim((string)($profile['name'] ?? ''));

            // Try to find existing user by email
            $user = null;
            if ($email) {
                $user = $this->findUserByEmail($email);
            }

            if ($user) {
                // Existing user found

                // Check if account is active
                if (isset($user['TrangThai']) && (int)$user['TrangThai'] === 0) {
                    $this->flash('error', 'Tài khoản đã bị khóa.');
                    $this->redirect($this->loginUrl);
                }

                // Update name if empty
                if ($name && empty($user['HoTen'])) {
                    $this->updateUserName((int)$user['MaNguoiDung'], $name);
                    $user['HoTen'] = $name;
                }

                // Create session and log login
                $this->createUserSession($user);
                $this->logOAuthLogin((int)$user['MaNguoiDung'], $provider);

                $this->flash('success', 'Đăng nhập thành công!');
                $this->redirect($this->homeUrl);
            }

            // New user - Auto register

            // Generate username
            $baseUsername = $email ? explode('@', $email)[0] : ($provider . '_' . $externalId);
            $username = $this->generateUniqueUsername($baseUsername);

            // Use name or fallback to username
            $fullName = $name ?: $username;

            // Create new user
            $userId = $this->createOAuthUser($username, $fullName, $email);

            // Prepare user data for session
            $newUser = [
                'MaNguoiDung' => $userId,
                'Username' => $username,
                'HoTen' => $fullName,
                'Email' => $email,
                'VaiTro' => self::DEFAULT_ROLE,
                'TrangThai' => 1,
            ];

            // Create session and log login
            $this->createUserSession($newUser);
            $this->logOAuthLogin($userId, $provider);

            $this->flash('success', 'Tạo tài khoản và đăng nhập thành công!');
            $this->redirect($this->homeUrl);

        } catch (Exception $e) {
            error_log('OAuth login error: ' . $e->getMessage());
            $this->flash('error', 'Đã xảy ra lỗi khi đăng nhập. Vui lòng thử lại.');
            $this->redirect($this->loginUrl);
        }
    }

    // --------------- Static Helper for External Use ---------------

    public static function requireLogin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Vui lòng đăng nhập để tiếp tục.';
            header('Location: /Webmarket/view/html/login.php');
            exit;
        }
    }

    public static function getCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? 'User',
            'email' => $_SESSION['email'] ?? null,
            'name' => $_SESSION['name'] ?? 'User',
            'role' => $_SESSION['role'] ?? 'USER',
        ];
    }

    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear user session
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['email']);
        unset($_SESSION['name']);
        unset($_SESSION['role']);
        unset($_SESSION['login_time']);
        unset($_SESSION['login_method']);

        // Destroy session if empty
        if (empty($_SESSION)) {
            session_destroy();
        }
    }
}