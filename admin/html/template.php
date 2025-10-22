<?php
// Load database connection trước
if (!isset($conn)) {
    require_once __DIR__ . '/../../model/database.php';
}

// Load controller thông báo
require_once __DIR__ . '/../controller/Notification_Controller.php';

// Lấy thông báo từ controller
$notif_data = NotificationsController::getNotificationsForTemplate();
$notifications = $notif_data['notifications'];
$notification_count = $notif_data['notification_count'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Hương Trà Admin'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7ISGqrIDrxlwX+uYwg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/template.css">
</head>
<body class="bg-gray-100 min-h-screen font-sans">
<div id="main-layout" class="flex relative">
    <aside id="sidebar" class="fixed top-0 left-0 h-screen w-64 bg-white shadow-xl z-30 transform -translate-x-full md:translate-x-0 md:relative md:flex md:flex-col flex-shrink-0">
        <div class="p-6 brand-bg flex items-center justify-center h-16 shadow-md">
            <span class="text-white text-xl font-bold tracking-wider">Hương Trà Admin</span>
        </div>
        <nav class="flex-grow p-4 no-scrollbar overflow-y-auto">
            <ul class="space-y-2">
                <li>
                    <a href="dashboard.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-white brand-bg rounded-lg shadow-md font-medium' : ''; ?>">
                        <i class="fa-solid fa-house w-5 h-5 mr-3"></i>
                        <span>Trang tổng quan</span>
                    </a>
                </li>
                <li>
                    <a href="customers.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'text-white brand-bg rounded-lg shadow-md font-medium' : ''; ?>">
                        <i class="fa-solid fa-users w-5 h-5 mr-3"></i>
                        <span>Quản lý khách hàng</span>
                    </a>
                </li>
                <li>
                    <a href="products.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'text-white brand-bg rounded-lg shadow-md font-medium' : ''; ?>">
                        <i class="fa-solid fa-box-open w-5 h-5 mr-3"></i>
                        <span>Quản lý sản phẩm</span>
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'text-white brand-bg rounded-lg shadow-md font-medium' : ''; ?>">
                        <i class="fa-solid fa-receipt w-5 h-5 mr-3"></i>
                        <span>Quản lý đơn hàng</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'text-white brand-bg rounded-lg shadow-md font-medium' : ''; ?>">
                        <i class="fa-solid fa-chart-line w-5 h-5 mr-3"></i>
                        <span>Báo cáo & thống kê</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-200">
            <a href="login.php" class="flex items-center p-3 text-gray-700 hover:bg-red-100 hover:text-red-600 rounded-lg transition duration-200">
                <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-3"></i>
                <span>Đăng xuất</span>
            </a>
        </div>
    </aside>
    <div id="content-area" class="content-area flex-grow min-h-screen">
        <header class="bg-white shadow-sm p-4 flex items-center justify-between sticky top-0 z-20">
            <button id="menu-toggle" class="md:hidden text-gray-600 hover:text-gray-800 p-2 rounded-lg">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
            <div class="flex-grow flex justify-center mx-4 md:mx-8">
                <div class="relative w-full max-w-lg">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <form action="" method="get">
                        <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Tìm kiếm (Sản phẩm, Đơn hàng, Khách hàng)..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-red-300 transition duration-150">
                    </form>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <button id="notification-bell" class="text-gray-600 hover:text-gray-800 p-2 rounded-full relative transition">
                    <i class="fa-solid fa-bell text-xl"></i>
                    <?php if ($notification_count > 0): ?>
                        <span class="notification-badge"><?= $notification_count > 99 ? '99+' : $notification_count ?></span>
                    <?php endif; ?>
                </button>
                <div class="notification-dropdown" id="notification-dropdown">
                    <div class="notification-header">
                        <span>Thông báo</span>
                        <span class="text-sm"><?= $notification_count ?> mới</span>
                    </div>
                    <div class="notification-body">
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notif):
                                $icon = NotificationsController::getNotificationIcon($notif['type']);
                                $color = NotificationsController::getNotificationColor($notif['type']);
                                ?>
                                <div class="notification-item">
                                    <div class="notif-icon <?= $color ?>">
                                        <i class="fas <?= $icon ?>"></i>
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-message"><?= $notif['message'] ?></div>
                                        <div class="notif-time">
                                            <i class="far fa-clock"></i>
                                            <?= $notif['time'] ?>
                                        </div>
                                        <?php if ($notif['order_id']): ?>
                                            <div class="notif-action">
                                                <a href="orders.php?order_id=<?= htmlspecialchars($notif['order_id']) ?>">
                                                    Xem chi tiết <i class="fas fa-arrow-right text-xs"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notification-empty">
                                <i class="fa-solid fa-bell-slash"></i>
                                <p class="text-sm font-medium">Không có thông báo mới</p>
                                <p class="text-xs mt-1">Bạn đã xem hết các thông báo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($notifications)): ?>
                        <div class="notification-footer">
                            <a href="notifications.php">Xem tất cả thông báo</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-2 cursor-pointer">
                    <div class="text-sm font-medium text-gray-800 hidden sm:block">Admin.HTrà</div>
                    <img class="h-10 w-10 rounded-full object-cover border-2 border-red-300" src="https://placehold.co/40x40/8f2c24/ffffff?text=AD" alt="Avatar">
                </div>
            </div>
        </header>
        <main class="p-4 md:p-8">
            <?php
            // Nơi chứa nội dung chính của từng trang
            if (isset($content)) {
                echo $content;
            }
            ?>
        </main>
    </div>
</div>

<script src="../js/template.js"></script>
</body>
</html>