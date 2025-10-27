<?php
/**
 * config.php
 * File cấu hình chung cho admin panel
 */

// Khởi động session an toàn
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true
    ]);
}

// Định nghĩa constants
define('ADMIN_PATH', __DIR__);
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * Kiểm tra authentication
 */
function requireAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: login.php");
        exit();
    }

    // Kiểm tra timeout session (30 phút)
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check file upload
 */
function validateImageUpload($file) {
    $errors = [];

    // Kiểm tra có file không
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => 'Chưa chọn file'];
    }

    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Lỗi upload file'];
    }

    // Kiểm tra kích thước
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'File quá lớn (max 5MB)'];
    }

    // Kiểm tra MIME type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Chỉ chấp nhận file ảnh'];
    }

    return ['success' => true];
}

/**
 * Error handler
 */
function handleError($message, $redirect = null) {
    error_log("Admin Error: " . $message);
    $_SESSION['error_message'] = $message;

    if ($redirect) {
        header("Location: " . $redirect);
        exit();
    }
}

/**
 * Success message
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Get and clear flash message
 */
function getFlashMessage($type = 'error') {
    $key = $type . '_message';
    $message = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $message;
}