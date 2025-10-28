<?php

require_once __DIR__ . '/../../model/database.php';

class NotificationsController {

    /**
     * Lấy danh sách thông báo chưa đọc
     */
    public static function getUnreadNotifications($limit = 10) {
        // Sử dụng Database OOP
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Kiểm tra kết nối
        if (!$conn || $conn->connect_error) {
            error_log("Database connection error in NotificationsController");
            return [];
        }

        $notifications = [];

        try {
            // 1. Lấy đơn hàng mới (PLACED - chờ xác nhận) trong 24h qua
            $sql_new_orders = "
                SELECT 
                    dh.MaDonHang,
                    nd.HoTen as KhachHang,
                    dh.NgayDat,
                    dh.TongTien,
                    dh.TrangThai,
                    'new_order' as type
                FROM donhang dh
                INNER JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
                WHERE dh.TrangThai = 'PLACED'
                AND dh.NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY dh.NgayDat DESC
                LIMIT ?
            ";

            $stmt = $conn->prepare($sql_new_orders);
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                return [];
            }

            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $timeAgo = self::timeAgo($row['NgayDat']);
                $notifications[] = [
                    'id' => 'order_' . $row['MaDonHang'],
                    'type' => 'new_order',
                    'message' => "Đơn hàng mới từ " . htmlspecialchars($row['KhachHang']) . " (HD" . str_pad($row['MaDonHang'], 5, '0', STR_PAD_LEFT) . ")",
                    'time' => $timeAgo,
                    'order_id' => $row['MaDonHang'],
                    'timestamp' => strtotime($row['NgayDat']),
                    'priority' => 'high',
                    'amount' => $row['TongTien']
                ];
            }
            $stmt->close();

            // 2. Lấy đơn hàng đã xác nhận (CONFIRMED - đang chuẩn bị) trong 24h qua
            $sql_confirmed_orders = "
                SELECT 
                    dh.MaDonHang,
                    nd.HoTen as KhachHang,
                    dh.NgayDat,
                    dh.TongTien,
                    'order_confirmed' as type
                FROM donhang dh
                INNER JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
                WHERE dh.TrangThai = 'CONFIRMED'
                AND dh.NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY dh.NgayDat DESC
                LIMIT ?
            ";

            $stmt = $conn->prepare($sql_confirmed_orders);
            if ($stmt) {
                $stmt->bind_param("i", $limit);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $timeAgo = self::timeAgo($row['NgayDat']);
                    $notifications[] = [
                        'id' => 'confirmed_' . $row['MaDonHang'],
                        'type' => 'order_confirmed',
                        'message' => "Đơn hàng HD" . str_pad($row['MaDonHang'], 5, '0', STR_PAD_LEFT) . " đang được chuẩn bị",
                        'time' => $timeAgo,
                        'order_id' => $row['MaDonHang'],
                        'timestamp' => strtotime($row['NgayDat']),
                        'priority' => 'medium'
                    ];
                }
                $stmt->close();
            }

            // 3. Lấy đơn hàng đang giao (SHIPPING) trong 24h qua
            $sql_shipping_orders = "
                SELECT 
                    dh.MaDonHang,
                    nd.HoTen as KhachHang,
                    dh.NgayDat,
                    'order_shipping' as type
                FROM donhang dh
                INNER JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
                WHERE dh.TrangThai = 'SHIPPING'
                AND dh.NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY dh.NgayDat DESC
                LIMIT ?
            ";

            $stmt = $conn->prepare($sql_shipping_orders);
            if ($stmt) {
                $stmt->bind_param("i", $limit);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $timeAgo = self::timeAgo($row['NgayDat']);
                    $notifications[] = [
                        'id' => 'shipping_' . $row['MaDonHang'],
                        'type' => 'order_shipping',
                        'message' => "Đơn hàng HD" . str_pad($row['MaDonHang'], 5, '0', STR_PAD_LEFT) . " đang được giao",
                        'time' => $timeAgo,
                        'order_id' => $row['MaDonHang'],
                        'timestamp' => strtotime($row['NgayDat']),
                        'priority' => 'low'
                    ];
                }
                $stmt->close();
            }

            // 4. Lấy đơn hàng chờ xác nhận lâu (> 2 giờ)
            $sql_pending_orders = "
                SELECT 
                    dh.MaDonHang,
                    nd.HoTen as KhachHang,
                    dh.NgayDat,
                    'pending_order' as type
                FROM donhang dh
                INNER JOIN nguoidung nd ON dh.MaNguoiDung = nd.MaNguoiDung
                WHERE dh.TrangThai = 'PLACED'
                AND dh.NgayDat <= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                AND dh.NgayDat >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                ORDER BY dh.NgayDat ASC
                LIMIT ?
            ";

            $stmt = $conn->prepare($sql_pending_orders);
            if ($stmt) {
                $stmt->bind_param("i", $limit);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $timeAgo = self::timeAgo($row['NgayDat']);
                    $notifications[] = [
                        'id' => 'pending_' . $row['MaDonHang'],
                        'type' => 'pending_order',
                        'message' => "Đơn hàng HD" . str_pad($row['MaDonHang'], 5, '0', STR_PAD_LEFT) . " chờ xác nhận quá lâu",
                        'time' => $timeAgo,
                        'order_id' => $row['MaDonHang'],
                        'timestamp' => strtotime($row['NgayDat']),
                        'priority' => 'urgent'
                    ];
                }
                $stmt->close();
            }

            // 5. Kiểm tra sản phẩm sắp hết hàng (số lượng tồn < 10)
            $sql_low_stock = "
                SELECT 
                    MaSanPham,
                    TenSanPham,
                    SoLuongTon,
                    'low_stock' as type
                FROM sanpham
                WHERE SoLuongTon < 10
                AND TrangThai = 1
                ORDER BY SoLuongTon ASC
                LIMIT 5
            ";

            $result = $conn->query($sql_low_stock);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $notifications[] = [
                        'id' => 'stock_' . $row['MaSanPham'],
                        'type' => 'low_stock',
                        'message' => "Sản phẩm \"" . htmlspecialchars($row['TenSanPham']) . "\" sắp hết hàng (còn " . $row['SoLuongTon'] . ")",
                        'time' => "Vừa xong",
                        'order_id' => null,
                        'product_id' => $row['MaSanPham'],
                        'timestamp' => time(),
                        'priority' => 'medium'
                    ];
                }
            }

            // 6. Kiểm tra yêu cầu chăm sóc khách hàng chưa xử lý
            $sql_support = "
                SELECT 
                    cs.MaYeuCau,
                    nd.HoTen,
                    cs.NgayTao,
                    cs.TrangThai,
                    'support_request' as type
                FROM chamsockhachhang cs
                INNER JOIN nguoidung nd ON cs.MaNguoiDung = nd.MaNguoiDung
                WHERE cs.TrangThai IN ('OPEN', 'IN_PROGRESS')
                ORDER BY cs.NgayTao DESC
                LIMIT 5
            ";

            $result = $conn->query($sql_support);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $timeAgo = self::timeAgo($row['NgayTao']);
                    $notifications[] = [
                        'id' => 'support_' . $row['MaYeuCau'],
                        'type' => 'support_request',
                        'message' => "Yêu cầu hỗ trợ từ " . htmlspecialchars($row['HoTen']),
                        'time' => $timeAgo,
                        'order_id' => null,
                        'timestamp' => strtotime($row['NgayTao']),
                        'priority' => 'medium'
                    ];
                }
            }

            // Sắp xếp theo độ ưu tiên và thời gian
            usort($notifications, function($a, $b) {
                $priority_order = ['urgent' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
                $a_priority = $priority_order[$a['priority']] ?? 999;
                $b_priority = $priority_order[$b['priority']] ?? 999;

                if ($a_priority === $b_priority) {
                    return $b['timestamp'] - $a['timestamp'];
                }
                return $a_priority - $b_priority;
            });

            // Giới hạn số lượng
            $notifications = array_slice($notifications, 0, $limit);

        } catch (Exception $e) {
            error_log("Error in getUnreadNotifications: " . $e->getMessage());
        }

        return $notifications;
    }

    /**
     * Đếm số thông báo quan trọng chưa xử lý
     */
    public static function getUnreadCount() {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Kiểm tra kết nối
        if (!$conn || $conn->connect_error) {
            return 0;
        }

        $count = 0;

        try {
            // Đếm đơn hàng mới (PLACED)
            $sql = "
                SELECT 
                    (SELECT COUNT(*) FROM donhang WHERE TrangThai = 'PLACED' AND NgayDat >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as new_orders,
                    (SELECT COUNT(*) FROM donhang WHERE TrangThai = 'PLACED' AND NgayDat <= DATE_SUB(NOW(), INTERVAL 2 HOUR)) as pending_orders,
                    (SELECT COUNT(*) FROM sanpham WHERE SoLuongTon < 10 AND TrangThai = 1) as low_stock,
                    (SELECT COUNT(*) FROM chamsockhachhang WHERE TrangThai IN ('OPEN', 'IN_PROGRESS')) as support_requests
            ";

            $result = $conn->query($sql);
            if ($result && $row = $result->fetch_assoc()) {
                $count = (int)$row['new_orders'] + (int)$row['pending_orders'] + (int)$row['low_stock'] + (int)$row['support_requests'];
            }
        } catch (Exception $e) {
            error_log("Error in getUnreadCount: " . $e->getMessage());
        }

        return $count;
    }

    /**
     * Tính thời gian đã qua (time ago)
     */
    private static function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return "Vừa xong";
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . " phút trước";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . " giờ trước";
        } else {
            $days = floor($diff / 86400);
            return $days . " ngày trước";
        }
    }

    /**
     * Lấy icon cho từng loại thông báo
     */
    public static function getNotificationIcon($type) {
        $icons = [
            'new_order' => 'fa-shopping-cart',
            'order_confirmed' => 'fa-check-circle',
            'order_shipping' => 'fa-truck',
            'pending_order' => 'fa-clock',
            'order_completed' => 'fa-check-double',
            'low_stock' => 'fa-exclamation-triangle',
            'support_request' => 'fa-headset'
        ];

        return $icons[$type] ?? 'fa-bell';
    }

    /**
     * Lấy màu cho từng loại thông báo
     */
    public static function getNotificationColor($type) {
        $colors = [
            'new_order' => 'bg-blue-100 text-blue-600',
            'order_confirmed' => 'bg-green-100 text-green-600',
            'order_shipping' => 'bg-purple-100 text-purple-600',
            'pending_order' => 'bg-yellow-100 text-yellow-600',
            'order_completed' => 'bg-green-100 text-green-600',
            'low_stock' => 'bg-red-100 text-red-600',
            'support_request' => 'bg-orange-100 text-orange-600'
        ];

        return $colors[$type] ?? 'bg-gray-100 text-gray-600';
    }

    /**
     * Handle cho các trang khác sử dụng
     */
    public static function getNotificationsForTemplate() {
        $notifications = self::getUnreadNotifications(10);
        $notification_count = self::getUnreadCount();

        return [
            'notifications' => $notifications,
            'notification_count' => $notification_count
        ];
    }

}