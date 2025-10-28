<?php
/**
 * get_notifications.php
 * API endpoint để lấy thông báo mới qua AJAX
 */

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Load database connection OOP
require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/../controller/Notification_Controller.php';

header('Content-Type: application/json');

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    $notifications = NotificationsController::getUnreadNotifications($limit);
    $notification_count = NotificationsController::getUnreadCount();

    // Format lại dữ liệu để trả về JSON
    $formatted_notifications = array_map(function($notif) {
        return [
            'id' => $notif['id'],
            'type' => $notif['type'],
            'message' => $notif['message'],
            'time' => $notif['time'],
            'order_id' => $notif['order_id'] ?? null,
            'product_id' => $notif['product_id'] ?? null,
            'priority' => $notif['priority'],
            'icon' => NotificationsController::getNotificationIcon($notif['type']),
            'color' => NotificationsController::getNotificationColor($notif['type'])
        ];
    }, $notifications);

    echo json_encode([
        'success' => true,
        'notifications' => $formatted_notifications,
        'count' => $notification_count,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);

}