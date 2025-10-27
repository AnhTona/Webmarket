<?php
// Load config và kiểm tra auth
require_once __DIR__ . '/config.php';

// Load database connection
if (!isset($conn)) {
    require_once __DIR__ . '/../../model/database.php';
}

// Load controller thông báo
require_once __DIR__ . '/../controller/Notification_Controller.php';

// Lấy thông báo từ controller
$notif_data = NotificationsController::getNotificationsForTemplate();
$notifications = $notif_data['notifications'];
$notification_count = $notif_data['notification_count'];

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Get flash messages
$error_message = getFlashMessage('error');
$success_message = getFlashMessage('success');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Hương Trà Admin' : 'Hương Trà Admin'; ?></title>

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- CSS Libraries -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
          integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/template.css">

    <!-- Page specific CSS -->
    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="../css/<?php echo htmlspecialchars($page_css); ?>.css">
    <?php endif; ?>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

<!-- Flash Messages -->
<?php if ($error_message): ?>
    <div id="flash-error" class="fixed top-4 right-4 z-50 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg" role="alert">
        <strong class="font-bold">Lỗi!</strong>
        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        <button class="absolute top-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div id="flash-success" class="fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg" role="alert">
        <strong class="font-bold">Thành công!</strong>
        <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
        <button class="absolute top-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
<?php endif; ?>

<div id="main-layout" class="flex relative">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed top-0 left-0 h-screen w-64 bg-white shadow-xl z-30 transform -translate-x-full md:translate-x-0 md:relative md:flex md:flex-col flex-shrink-0">
        <div class="p-6 brand-bg flex items-center justify-center h-16 shadow-md">
            <span class="text-white text-xl font-bold tracking-wider">Hương Trà Admin</span>
        </div>
        <nav class="flex-grow p-4 no-scrollbar overflow-y-auto">
            <ul class="space-y-2">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                $menu_items = [
                        ['page' => 'dashboard.php', 'icon' => 'fa-house', 'label' => 'Trang tổng quan'],
                        ['page' => 'customers.php', 'icon' => 'fa-users', 'label' => 'Quản lý khách hàng'],
                        ['page' => 'products.php', 'icon' => 'fa-box-open', 'label' => 'Quản lý sản phẩm'],
                        ['page' => 'orders.php', 'icon' => 'fa-receipt', 'label' => 'Quản lý đơn hàng'],
                        ['page' => 'reports.php', 'icon' => 'fa-chart-line', 'label' => 'Báo cáo & thống kê'],
                ];

                foreach ($menu_items as $item):
                    $is_active = ($current_page === $item['page']) ? 'brand-bg text-white' : 'text-gray-700 hover:bg-gray-200';
                    ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($item['page']); ?>"
                           class="flex items-center p-3 rounded-lg transition duration-200 <?php echo $is_active; ?>">
                            <i class="fa-solid <?php echo htmlspecialchars($item['icon']); ?> w-5 h-5 mr-3"></i>
                            <span><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-200">
            <a href="logout.php" class="flex items-center p-3 text-gray-700 hover:bg-red-100 hover:text-red-600 rounded-lg transition duration-200">
                <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-3"></i>
                <span>Đăng xuất</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div id="content-area" class="content-area flex-grow min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm p-4 flex items-center justify-between sticky top-0 z-20">
            <button id="menu-toggle" class="md:hidden text-gray-600 hover:text-gray-800 p-2 rounded-lg">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>

            <!-- Search Bar -->
            <div class="flex-grow flex justify-center mx-4 md:mx-8">
                <div class="relative w-full max-w-lg">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <form action="" method="get">
                        <input type="text"
                               name="search"
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                               placeholder="Tìm kiếm (Sản phẩm, Đơn hàng, Khách hàng...)"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                    </form>
                </div>
            </div>

            <!-- User Actions -->
            <div class="flex items-center space-x-4">
                <!-- Notification Bell -->
                <button id="notification-bell" class="text-gray-600 hover:text-gray-800 p-2 rounded-full relative transition">
                    <i class="fa-solid fa-bell text-xl"></i>
                    <?php if ($notification_count > 0): ?>
                        <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">
                            <?= $notification_count > 99 ? '99+' : $notification_count ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- Notification Dropdown -->
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
                                        <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="notif-time">
                                            <i class="far fa-clock"></i>
                                            <?= htmlspecialchars($notif['time']) ?>
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
                            <div class="notification-empty p-8 text-center text-gray-400">
                                <i class="fa-solid fa-bell-slash text-4xl mb-2"></i>
                                <p class="text-sm font-medium">Không có thông báo mới</p>
                                <p class="text-xs mt-1">Bạn đã xem hết các thông báo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($notifications)): ?>
                        <div class="notification-footer border-t p-3 text-center">
                            <a href="notifications.php" class="text-sm brand-primary hover:underline">
                                Xem tất cả thông báo
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- User Profile -->
                <div class="flex items-center space-x-2 cursor-pointer">
                    <div class="text-sm font-medium text-gray-800 hidden sm:block">
                        <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin.HTrà'; ?>
                    </div>
                    <img class="h-10 w-10 rounded-full object-cover border-2 border-red-300"
                         src="https://placehold.co/40x40/8f2c24/ffffff?text=AD"
                         alt="Avatar">
                </div>
            </div>
        </header>

        <!-- Main Content -->
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

<!-- Global Scripts -->
<script src="../js/utils.js"></script>
<script src="../js/base.js"></script>
<script src="../js/template.js"></script>

<!-- Page specific scripts -->
<?php if (isset($page_js)): ?>
    <script src="../js/<?php echo htmlspecialchars($page_js); ?>.js"></script>
<?php endif; ?>
</body>
</html>