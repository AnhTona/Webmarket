<?php
declare(strict_types=1);

/**
 * Notification_Controller.php
 * Real-time notification controller with PHP 8.4 features
 *
 * @package Admin\Controller
 * @author AnhTona
 * @version 2.0.0
 * @since PHP 8.4
 */

require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/BaseController.php';

final class NotificationsController extends BaseController
{
    /**
     * Notification types and their properties
     */
    private const NOTIFICATION_TYPES = [
        'new_order' => [
            'icon' => 'fa-shopping-cart',
            'color' => 'bg-blue-500',
            'priority' => 'high',
        ],
        'order_confirmed' => [
            'icon' => 'fa-check-circle',
            'color' => 'bg-green-500',
            'priority' => 'medium',
        ],
        'low_stock' => [
            'icon' => 'fa-exclamation-triangle',
            'color' => 'bg-yellow-500',
            'priority' => 'high',
        ],
        'new_customer' => [
            'icon' => 'fa-user-plus',
            'color' => 'bg-purple-500',
            'priority' => 'low',
        ],
        'system' => [
            'icon' => 'fa-info-circle',
            'color' => 'bg-gray-500',
            'priority' => 'medium',
        ],
    ];

    /**
     * Get notifications for template (called by template.php)
     *
     * @return array{notifications: array, notification_count: int}
     */
    public static function getNotificationsForTemplate(): array
    {
        try {
            $notifications = self::getUnreadNotifications(limit: 5);
            $count = self::getUnreadCount();

            return [
                'notifications' => $notifications,
                'notification_count' => $count,
            ];
        } catch (Throwable $e) {
            error_log("Get Notifications Error: " . $e->getMessage());
            return [
                'notifications' => [],
                'notification_count' => 0,
            ];
        }
    }

    /**
     * Get unread notifications
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getUnreadNotifications(int $limit = 10): array
    {
        $notifications = [];

        try {
            // 1. New orders (PLACED - waiting confirmation) in last 24h
            $newOrders = self::fetchAll(
                "SELECT 
                    dh.MaDonHang,
                    nd.HoTen as KhachHang,
                    dh.NgayDat,
                    dh.TongTien,
                    'new_order' as type
                 FROM donhang dh
                 INNER JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
                 WHERE dh.TrangThai = 'PLACED'
                   AND dh.NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY dh.NgayDat DESC
                 LIMIT ?",
                [$limit]
            );

            foreach ($newOrders as $order) {
                $notifications[] = [
                    'id' => 'order_' . $order['MaDonHang'],
                    'type' => 'new_order',
                    'message' => sprintf(
                        "Đơn hàng mới từ %s (#%05d)",
                        $order['KhachHang'],
                        $order['MaDonHang']
                    ),
                    'time' => self::timeAgo($order['NgayDat']),
                    'order_id' => $order['MaDonHang'],
                    'timestamp' => strtotime($order['NgayDat']),
                    'priority' => 'high',
                    'amount' => (float)$order['TongTien'],
                ];
            }

            // 2. Confirmed orders (CONFIRMED - being prepared) in last 24h
            $confirmedOrders = self::fetchAll(
                "SELECT 
                    dh.MaDonHang,
                    nd.HoTen as KhachHang,
                    dh.NgayDat,
                    'order_confirmed' as type
                 FROM donhang dh
                 INNER JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
                 WHERE dh.TrangThai = 'CONFIRMED'
                   AND dh.NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY dh.NgayDat DESC
                 LIMIT ?",
                [$limit]
            );

            foreach ($confirmedOrders as $order) {
                $notifications[] = [
                    'id' => 'order_confirmed_' . $order['MaDonHang'],
                    'type' => 'order_confirmed',
                    'message' => sprintf(
                        "Đơn hàng #%05d đang được chuẩn bị",
                        $order['MaDonHang']
                    ),
                    'time' => self::timeAgo($order['NgayDat']),
                    'order_id' => $order['MaDonHang'],
                    'timestamp' => strtotime($order['NgayDat']),
                    'priority' => 'medium',
                ];
            }

            // 3. Low stock products (< 10 items)
            $lowStock = self::fetchAll(
                "SELECT 
                    MaSanPham,
                    TenSanPham,
                    SoLuongTon
                 FROM sanpham
                 WHERE SoLuongTon < 10
                   AND SoLuongTon > 0
                 ORDER BY SoLuongTon ASC
                 LIMIT ?",
                [$limit]
            );

            foreach ($lowStock as $product) {
                $notifications[] = [
                    'id' => 'low_stock_' . $product['MaSanPham'],
                    'type' => 'low_stock',
                    'message' => sprintf(
                        "Sản phẩm '%s' sắp hết hàng (còn %d)",
                        $product['TenSanPham'],
                        $product['SoLuongTon']
                    ),
                    'time' => 'Vừa xong',
                    'product_id' => $product['MaSanPham'],
                    'timestamp' => time(),
                    'priority' => 'high',
                ];
            }

            // 4. New customers in last 7 days
            $newCustomers = self::fetchAll(
                "SELECT 
                    MaNguoiDung,
                    HoTen,
                    Email,
                    NgayTao
                 FROM nguoidung
                 WHERE VaiTro = 'CUSTOMER'
                   AND NgayTao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY NgayTao DESC
                 LIMIT ?",
                [$limit]
            );

            foreach ($newCustomers as $customer) {
                $notifications[] = [
                    'id' => 'new_customer_' . $customer['MaNguoiDung'],
                    'type' => 'new_customer',
                    'message' => sprintf(
                        "Khách hàng mới: %s",
                        $customer['HoTen']
                    ),
                    'time' => self::timeAgo($customer['NgayTao']),
                    'customer_id' => $customer['MaNguoiDung'],
                    'timestamp' => strtotime($customer['NgayTao']),
                    'priority' => 'low',
                ];
            }

            // Sort by timestamp (newest first) and limit
            usort($notifications, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
            $notifications = array_slice($notifications, 0, $limit);

        } catch (Throwable $e) {
            error_log("Get Unread Notifications Error: " . $e->getMessage());
        }

        return $notifications;
    }

    /**
     * Get unread notification count
     */
    public static function getUnreadCount(): int
    {
        try {
            $count = 0;

            // New orders (last 24h)
            $count += (int)self::fetchOne(
                "SELECT COUNT(*) FROM donhang 
                 WHERE TrangThai = 'PLACED' 
                   AND NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );

            // Low stock products
            $count += (int)self::fetchOne(
                "SELECT COUNT(*) FROM sanpham 
                 WHERE SoLuongTon < 10 AND SoLuongTon > 0"
            );

            return $count;

        } catch (Throwable $e) {
            error_log("Get Unread Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get notification icon by type
     */
    public static function getNotificationIcon(string $type): string
    {
        return self::NOTIFICATION_TYPES[$type]['icon'] ?? 'fa-bell';
    }

    /**
     * Get notification color by type
     */
    public static function getNotificationColor(string $type): string
    {
        return self::NOTIFICATION_TYPES[$type]['color'] ?? 'bg-gray-500';
    }

    /**
     * Get notification priority by type
     */
    public static function getNotificationPriority(string $type): string
    {
        return self::NOTIFICATION_TYPES[$type]['priority'] ?? 'medium';
    }

    /**
     * Convert timestamp to relative time (e.g., "5 phút trước")
     */
    private static function timeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        return match(true) {
            $diff < 60 => 'Vừa xong',
            $diff < 3600 => floor($diff / 60) . ' phút trước',
            $diff < 86400 => floor($diff / 3600) . ' giờ trước',
            $diff < 604800 => floor($diff / 86400) . ' ngày trước',
            default => date('d/m/Y H:i', $timestamp)
        };
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead(string $notificationId): bool
    {
        try {
            // TODO: Implement notification read status in database
            // For now, this is a placeholder

            self::log('mark_notification_read', ['notification_id' => $notificationId]);

            return true;

        } catch (Throwable $e) {
            error_log("Mark As Read Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read
     */
    public static function markAllAsRead(): bool
    {
        try {
            // TODO: Implement mark all as read in database

            self::log('mark_all_notifications_read', []);

            return true;

        } catch (Throwable $e) {
            error_log("Mark All As Read Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create system notification
     */
    public static function createSystemNotification(
        string $message,
        string $type = 'system',
        array $metadata = []
    ): bool {
        try {
            // TODO: Implement notification storage in database

            self::log('create_system_notification', [
                'type' => $type,
                'message' => $message,
                'metadata' => $metadata,
            ]);

            return true;

        } catch (Throwable $e) {
            error_log("Create System Notification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notification statistics
     *
     * @return array<string, mixed>
     */
    public static function getStatistics(): array
    {
        try {
            return [
                'total_unread' => self::getUnreadCount(),
                'new_orders' => (int)self::fetchOne(
                    "SELECT COUNT(*) FROM donhang 
                     WHERE TrangThai = 'PLACED' 
                       AND NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
                ),
                'low_stock_items' => (int)self::fetchOne(
                    "SELECT COUNT(*) FROM sanpham 
                     WHERE SoLuongTon < 10 AND SoLuongTon > 0"
                ),
                'new_customers_week' => (int)self::fetchOne(
                    "SELECT COUNT(*) FROM nguoidung 
                     WHERE VaiTro = 'CUSTOMER' 
                       AND NgayTao >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
                ),
            ];
        } catch (Throwable $e) {
            error_log("Get Notification Statistics Error: " . $e->getMessage());
            return [
                'total_unread' => 0,
                'new_orders' => 0,
                'low_stock_items' => 0,
                'new_customers_week' => 0,
            ];
        }
    }
}