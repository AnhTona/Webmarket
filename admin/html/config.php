<?php
// admin/html/config.php

// Chỉ start session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra authentication
 */
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Nếu chưa đăng nhập
    if (!isset($_SESSION['user_id'])) {
        header("Location: /Webmarket/admin/html/login.php");
        exit();
    }

    // ✅ ĐỔI: Kiểm tra user_role thay vì role
    $role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? ''); // Hỗ trợ cả 2
    if ($role !== 'ADMIN' && $role !== 'STAFF') {
        session_destroy();
        header("Location: /Webmarket/admin/html/login.php");
        exit();
    }
}

/**
 * Xử lý lỗi
 */
function handleError($message, $page = '') {
    error_log("Error in {$page}: {$message}");
    echo "<div style='color: red; padding: 20px;'>Lỗi: " . htmlspecialchars($message) . "</div>";
    exit();
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get flash message
 */
function getFlashMessage(string $type): ?string {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

/**
 * Redirect helper
 */
function redirect(string $path): void {
    header("Location: {$path}");
    exit();
}

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency (VND)
 */
function formatCurrency(float $amount): string {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

/**
 * Format date
 */
function formatDate(string $date, string $format = 'd/m/Y H:i'): string {
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Check if user has permission
 */
function hasPermission(string $permission): bool {
    // ✅ ĐỔI: Dùng user_role
    $role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? '');

    if ($role === 'ADMIN') {
        return true;
    }

    $staffPermissions = [
        'view_orders',
        'update_orders',
        'view_products',
        'view_customers',
    ];

    return $role === 'STAFF' && in_array($permission, $staffPermissions);
}

/**
 * Get current user info
 */
function getCurrentUser(): array {
    // ✅ ĐỔI: Dùng user_role
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? 'Guest',
        'name' => $_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'Guest'),
        'role' => $_SESSION['user_role'] ?? ($_SESSION['role'] ?? 'USER'),
    ];
}
