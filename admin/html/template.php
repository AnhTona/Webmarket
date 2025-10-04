<?php
// Xóa dòng session_start() khỏi đây
include __DIR__ . '/../../db.php'; // Đảm bảo đường dẫn đúng đến db.php

// Giả lập dữ liệu thông báo
$notifications = [
    ['id' => 1, 'type' => 'new_order', 'message' => 'Đơn hàng mới từ Nguyễn Văn A (HD00125)', 'time' => '12:30 AM', 'order_id' => 'HD00125'],
    ['id' => 2, 'type' => 'payment_received', 'message' => 'Khách hàng Chị Mai đã thanh toán (HD00124)', 'time' => '12:15 AM', 'order_id' => 'HD00124'],
    ['id' => 3, 'type' => 'pending_order', 'message' => 'Đơn hàng HD00123 chờ xác nhận', 'time' => '11:50 PM', 'order_id' => 'HD00123']
];
$notification_count = count($notifications);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Hương Trà Admin'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7ISGqrIDrxlwX+uYwg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .brand-primary { color: #8f2c24; }
        .brand-bg { background-color: #8f2c24; }
        .brand-bg-hover:hover { background-color: #6d1f18; }
        #sidebar { transition: transform 0.3s ease-in-out, width 0.3s ease-in-out; }
        .content-area { transition: margin-left 0.3s ease-in-out; }
        @keyframes fill-up {
            from { height: 0; }
            to { height: var(--final-height); }
        }
        .chart-bar {
            animation: fill-up 1.5s ease-out forwards;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            width: 300px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 50;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-dropdown.active { display: block; }
        .notification-item { padding: 10px; border-bottom: 1px solid #eee; }
        .notification-item a { color: #8f2c24; text-decoration: none; }
        .notification-item a:hover { text-decoration: underline; }
    </style>
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
                        <a href="tables.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'tables.php' ? 'text-white brand-bg rounded-lg shadow-md font-medium' : ''; ?>">
                            <i class="fa-solid fa-calendar-check w-5 h-5 mr-3"></i>
                            <span>Quản lý bàn & đặt bàn</span>
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
                        <a href="feedback.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'text-white brand-bg rounded-lg shadow-md font-medium' : ''; ?>">
                            <i class="fa-solid fa-envelope w-5 h-5 mr-3"></i>
                            <span>Hộp thư / Phản hồi</span>
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
                <a href="logout.php" class="flex items-center p-3 text-gray-700 hover:bg-red-100 hover:text-red-600 rounded-lg transition duration-200">
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
                    <button id="notification-bell" class="text-gray-600 hover:text-gray-800 p-2 rounded-full relative">
                        <i class="fa-solid fa-bell text-xl"></i>
                        <?php if ($notification_count > 0): ?>
                            <span class="absolute top-1 right-1 h-3 w-3 bg-red-500 rounded-full border-2 border-white"></span>
                        <?php endif; ?>
                    </button>
                    <div class="notification-dropdown" id="notification-dropdown">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notification-item">
                                <span><?php echo htmlspecialchars($notif['message']); ?> - <?php echo $notif['time']; ?></span>
                                <a href="orders.php?order_id=<?php echo htmlspecialchars($notif['order_id']); ?>" class="block mt-1">Xem chi tiết</a>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($notification_count === 0): ?>
                            <div class="notification-item text-center text-gray-500">Không có thông báo mới</div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menu-toggle');
            const contentArea = document.getElementById('content-area');
            const mainLayout = document.getElementById('main-layout');
            const notificationBell = document.getElementById('notification-bell');
            const notificationDropdown = document.getElementById('notification-dropdown');

            function toggleSidebar() {
                sidebar.classList.toggle('-translate-x-full');
            }

            menuToggle.addEventListener('click', toggleSidebar);

            mainLayout.addEventListener('click', function(event) {
                if (window.innerWidth < 768 && !sidebar.contains(event.target) && !menuToggle.contains(event.target) && !sidebar.classList.contains('-translate-x-full')) {
                    toggleSidebar();
                }
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('-translate-x-full');
                }
            });

            if (window.innerWidth >= 768) {
                sidebar.classList.remove('fixed', 'shadow-xl');
                sidebar.classList.add('relative');
            }

            // Xử lý thông báo
            notificationBell.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('active');
            });

            document.addEventListener('click', function(e) {
                if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>