<?php
declare(strict_types=1);

/**
 * get_Notifications.php
 * AJAX API endpoint for real-time notifications
 *
 * @package Admin\Controller
 * @author AnhTona
 * @version 2.0.0
 * @since PHP 8.4
 */

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => !empty($_SERVER['HTTPS']),
        'use_strict_mode' => true,
    ]);
}

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers (if needed for local development)
if ($_SERVER['HTTP_ORIGIN'] ?? '' === 'http://localhost:3000') {
    header('Access-Control-Allow-Origin: http://localhost:3000');
    header('Access-Control-Allow-Credentials: true');
}

/**
 * Send JSON response and exit
 */
function jsonResponse(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    exit;
}

/**
 * Check authentication
 */
function requireAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(
            data: [
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Vui lòng đăng nhập',
            ],
            statusCode: 401
        );
    }

    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > 1800) {
            session_unset();
            session_destroy();

            jsonResponse(
                data: [
                    'success' => false,
                    'error' => 'Session timeout',
                    'message' => 'Phiên làm việc đã hết hạn',
                ],
                statusCode: 401
            );
        }
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Main execution
 */
try {
    // Check authentication
    requireAuth();

    // Load dependencies
    require_once __DIR__ . '/../../model/database.php';
    require_once __DIR__ . '/Notification_Controller.php';

    // Get parameters
    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;
    $action = $_GET['action'] ?? 'get_unread';

    // Handle different actions
    $response = match($action) {
        'get_unread' => handleGetUnread($limit),
        'get_count' => handleGetCount(),
        'mark_read' => handleMarkRead(),
        'mark_all_read' => handleMarkAllRead(),
        'get_stats' => handleGetStats(),
        default => [
            'success' => false,
            'error' => 'Invalid action',
            'message' => 'Action không hợp lệ',
        ]
    };

    jsonResponse($response);

} catch (Throwable $e) {
    error_log("Notification API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    jsonResponse(
        data: [
            'success' => false,
            'error' => 'Internal server error',
            'message' => 'Đã xảy ra lỗi khi tải thông báo',
            'debug' => $_ENV['APP_DEBUG'] ?? false ? $e->getMessage() : null,
        ],
        statusCode: 500
    );
}

/**
 * Get unread notifications
 */
function handleGetUnread(int $limit): array
{
    $notifications = NotificationsController::getUnreadNotifications($limit);
    $count = NotificationsController::getUnreadCount();

    // Format notifications with icons and colors
    $formattedNotifications = array_map(
        fn(array $notif): array => [
            'id' => $notif['id'],
            'type' => $notif['type'],
            'message' => $notif['message'],
            'time' => $notif['time'],
            'timestamp' => $notif['timestamp'],
            'priority' => $notif['priority'],
            'icon' => NotificationsController::getNotificationIcon($notif['type']),
            'color' => NotificationsController::getNotificationColor($notif['type']),
            'order_id' => $notif['order_id'] ?? null,
            'product_id' => $notif['product_id'] ?? null,
            'customer_id' => $notif['customer_id'] ?? null,
            'amount' => $notif['amount'] ?? null,
        ],
        $notifications
    );

    return [
        'success' => true,
        'notifications' => $formattedNotifications,
        'count' => $count,
        'timestamp' => time(),
        'server_time' => date('Y-m-d H:i:s'),
    ];
}

/**
 * Get unread count only
 */
function handleGetCount(): array
{
    $count = NotificationsController::getUnreadCount();

    return [
        'success' => true,
        'count' => $count,
        'timestamp' => time(),
    ];
}

/**
 * Mark single notification as read
 */
function handleMarkRead(): array
{
    $notificationId = $_POST['notification_id'] ?? $_GET['notification_id'] ?? '';

    if ($notificationId === '') {
        return [
            'success' => false,
            'error' => 'Missing notification_id',
            'message' => 'Thiếu ID thông báo',
        ];
    }

    $success = NotificationsController::markAsRead($notificationId);

    return [
        'success' => $success,
        'message' => $success ? 'Đã đánh dấu đã đọc' : 'Lỗi khi đánh dấu',
    ];
}

/**
 * Mark all notifications as read
 */
function handleMarkAllRead(): array
{
    $success = NotificationsController::markAllAsRead();

    return [
        'success' => $success,
        'message' => $success ? 'Đã đánh dấu tất cả đã đọc' : 'Lỗi khi đánh dấu',
    ];
}

/**
 * Get notification statistics
 */
function handleGetStats(): array
{
    $stats = NotificationsController::getStatistics();

    return [
        'success' => true,
        'statistics' => $stats,
        'timestamp' => time(),
    ];
}