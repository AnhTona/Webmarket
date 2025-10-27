<?php
declare(strict_types=1);

require_once __DIR__ . '/../../model/database.php';
require_once __DIR__ . '/BaseController.php';

final class NotificationsController extends BaseController
{
    private const NOTIFICATION_TYPES = [
        'new_order' => ['icon' => 'fa-shopping-cart', 'color' => 'bg-blue-500', 'priority' => 'high'],
        'order_confirmed' => ['icon' => 'fa-check-circle', 'color' => 'bg-green-500', 'priority' => 'medium'],
        'low_stock' => ['icon' => 'fa-exclamation-triangle', 'color' => 'bg-yellow-500', 'priority' => 'high'],
        'new_customer' => ['icon' => 'fa-user-plus', 'color' => 'bg-purple-500', 'priority' => 'low'],
        'system' => ['icon' => 'fa-info-circle', 'color' => 'bg-gray-500', 'priority' => 'medium'],
    ];

    public static function getNotificationsForTemplate(): array
    {
        try {
            return [
                'notifications' => self::getUnreadNotifications(limit: 5),
                'notification_count' => self::getUnreadCount(),
            ];
        } catch (Throwable $e) {
            error_log("Get Notifications Error: " . $e->getMessage());
            return ['notifications' => [], 'notification_count' => 0];
        }
    }

    public static function getUnreadNotifications(int $limit = 10): array
    {
        $notifications = [];

        try {
            // ✅ FIXED: VaiTro filter
            $vaiTroFilter = "(nd.VaiTro = '' OR nd.VaiTro IS NULL OR nd.VaiTro = 'USER')";

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
                   AND {$vaiTroFilter}
                 ORDER BY dh.NgayDat DESC
                 LIMIT ?",
                [$limit]
            );

            foreach ($newOrders as $order) {
                $notifications[] = [
                    'id' => 'order_' . $order['MaDonHang'],
                    'type' => 'new_order',
                    'message' => sprintf("Đơn hàng mới từ %s (#%05d)", $order['KhachHang'], $order['MaDonHang']),
                    'time' => self::timeAgo($order['NgayDat']),
                    'order_id' => $order['MaDonHang'],
                    'timestamp' => strtotime($order['NgayDat']),
                    'priority' => 'high',
                    'amount' => (float)$order['TongTien'],
                ];
            }

            // 2. Confirmed orders
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
                   AND {$vaiTroFilter}
                 ORDER BY dh.NgayDat DESC
                 LIMIT ?",
                [$limit]
            );

            foreach ($confirmedOrders as $order) {
                $notifications[] = [
                    'id' => 'order_confirmed_' . $order['MaDonHang'],
                    'type' => 'order_confirmed',
                    'message' => sprintf("Đơn hàng #%05d đang được chuẩn bị", $order['MaDonHang']),
                    'time' => self::timeAgo($order['NgayDat']),
                    'order_id' => $order['MaDonHang'],
                    'timestamp' => strtotime($order['NgayDat']),
                    'priority' => 'medium',
                ];
            }

            // 3. Low stock products (< 10 items)
            $lowStock = self::fetchAll(
                "SELECT MaSanPham, TenSanPham, SoLuongTon
                 FROM sanpham
                 WHERE SoLuongTon < 10 AND SoLuongTon > 0
                 ORDER BY SoLuongTon ASC
                 LIMIT ?",
                [$limit]
            );

            foreach ($lowStock as $product) {
                $notifications[] = [
                    'id' => 'low_stock_' . $product['MaSanPham'],
                    'type' => 'low_stock',
                    'message' => sprintf("Sản phẩm '%s' sắp hết hàng (còn %d)", $product['TenSanPham'], $product['SoLuongTon']),
                    'time' => 'Vừa xong',
                    'product_id' => $product['MaSanPham'],
                    'timestamp' => time(),
                    'priority' => 'high',
                ];
            }

            // 4. New customers in last 7 days
            // ✅ FIXED: VaiTro filter
            $newCustomers = self::fetchAll(
                "SELECT MaNguoiDung, HoTen, Email, NgayTao
                 FROM nguoidung
                 WHERE {$vaiTroFilter}
                   AND NgayTao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 ORDER BY NgayTao DESC
                 LIMIT ?",
                [$limit]
            );

            foreach ($newCustomers as $customer) {
                $notifications[] = [
                    'id' => 'new_customer_' . $customer['MaNguoiDung'],
                    'type' => 'new_customer',
                    'message' => sprintf("Khách hàng mới: %s", $customer['HoTen']),
                    'time' => self::timeAgo($customer['NgayTao']),
                    'customer_id' => $customer['MaNguoiDung'],
                    'timestamp' => strtotime($customer['NgayTao']),
                    'priority' => 'low',
                ];
            }

            usort($notifications, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
            $notifications = array_slice($notifications, 0, $limit);

        } catch (Throwable $e) {
            error_log("Get Unread Notifications Error: " . $e->getMessage());
        }

        return $notifications;
    }

    public static function getUnreadCount(): int
    {
        try {
            $count = (int)self::fetchOne(
                "SELECT COUNT(*) FROM donhang 
                 WHERE TrangThai = 'PLACED' 
                   AND NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );

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

    public static function getNotificationIcon(string $type): string
    {
        return self::NOTIFICATION_TYPES[$type]['icon'] ?? 'fa-bell';
    }

    public static function getNotificationColor(string $type): string
    {
        return self::NOTIFICATION_TYPES[$type]['color'] ?? 'bg-gray-500';
    }

    public static function getNotificationPriority(string $type): string
    {
        return self::NOTIFICATION_TYPES[$type]['priority'] ?? 'medium';
    }

    private static function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);

        return match(true) {
            $diff < 60 => 'Vừa xong',
            $diff < 3600 => floor($diff / 60) . ' phút trước',
            $diff < 86400 => floor($diff / 3600) . ' giờ trước',
            $diff < 604800 => floor($diff / 86400) . ' ngày trước',
            default => date('d/m/Y H:i', strtotime($datetime))
        };
    }

    public static function getStatistics(): array
    {
        try {
            // ✅ FIXED: VaiTro filter
            $vaiTroFilter = "(VaiTro = '' OR VaiTro IS NULL OR VaiTro = 'USER')";

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
                     WHERE {$vaiTroFilter}
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