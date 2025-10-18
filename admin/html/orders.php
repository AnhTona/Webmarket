<?php
require_once __DIR__ . '/../controller/Orders_Controller.php';
$ctx = OrdersController::handle();
extract($ctx, EXTR_OVERWRITE);

// nếu header có chuông thông báo:
$notifications      = $notifications ?? [];
$notification_count = is_array($notifications) ? count($notifications) : 0;
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7ISGqrIDrxlwX+uYwg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/orders.css">
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
                        <a href="dashboard.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200">
                            <i class="fa-solid fa-house w-5 h-5 mr-3"></i>
                            <span>Trang tổng quan</span>
                        </a>
                    </li>
                    <li>
                        <a href="customers.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200">
                            <i class="fa-solid fa-users w-5 h-5 mr-3"></i>
                            <span>Quản lý khách hàng</span>
                        </a>
                    </li>
                    <li>
                        <a href="tables.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200">
                            <i class="fa-solid fa-calendar-check w-5 h-5 mr-3"></i>
                            <span>Quản lý bàn & đặt bàn</span>
                        </a>
                    </li>
                    <li>
                        <a href="products.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200">
                            <i class="fa-solid fa-box-open w-5 h-5 mr-3"></i>
                            <span>Quản lý sản phẩm</span>
                        </a>
                    </li>
                    <li>
                        <a href="orders.php" class="flex items-center p-3 text-white brand-bg rounded-lg shadow-md font-medium">
                            <i class="fa-solid fa-receipt w-5 h-5 mr-3"></i>
                            <span>Quản lý đơn hàng</span>
                        </a>
                    </li>
                    <li>
                        <a href="feedback.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200">
                            <i class="fa-solid fa-envelope w-5 h-5 mr-3"></i>
                            <span>Hộp thư / Phản hồi</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200">
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
                            <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Tìm kiếm đơn hàng..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-red-300 transition duration-150">
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
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Đơn Hàng</h1>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="search-group">
                        <input type="text" id="search-input" placeholder="Tìm theo khách hàng...">
                        <button id="btn-search" class="btn btn-search"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="filter-group">
                        <label for="filter-date">Ngày:</label>
                        <input type="date" id="filter-date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="filter-status">Trạng thái:</label>
                        <select id="filter-status">
                            <option value="All">Tất cả</option>
                            <option value="Chờ xác nhận">Chờ xác nhận</option>
                            <option value="Đang chuẩn bị">Đang chuẩn bị</option>
                            <option value="Hoàn thành">Hoàn thành</option>
                        </select>
                    </div>
                    <button id="btn-toggle-advanced-filter" class="btn-toggle-advanced"><i class="fas fa-sliders-h"></i> Bộ lọc nâng cao</button>
                    <div id="advanced-filters" class="advanced-filters hidden">
                        <div class="filter-group">
                            <label for="filter-date-from">Từ ngày:</label>
                            <input type="date" id="filter-date-from">
                        </div>
                        <div class="filter-group">
                            <label for="filter-date-to">Đến ngày:</label>
                            <input type="date" id="filter-date-to">
                        </div>
                    </div>
                </div>

                <!-- Order Table -->
                <div class="order-table-container mt-6">
                    <table id="order-list-table" class="order-table w-full">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Bàn</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_list as $order): ?>
                                <tr data-id="<?php echo $order['MaDon']; ?>">
                                    <td><?php echo htmlspecialchars($order['MaDon']); ?></td>
                                    <td><?php echo htmlspecialchars($order['KhachHang']); ?></td>
                                    <td><?php echo htmlspecialchars($order['Ban']); ?></td>
                                    <td><?php echo htmlspecialchars($order['NgayDat']); ?></td>
                                    <td><?php echo number_format($order['TongTien'], 0, ',', '.') . 'đ'; ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['TrangThai'])); ?>"><?php echo htmlspecialchars($order['TrangThai']); ?></span></td>
                                    <td>
                                        <?php if ($order['TrangThai'] === 'Chờ xác nhận'): ?>
                                            <button class="btn-action confirm-order" title="Xác nhận">Xác nhận<i class="fas fa-check"></i></button>
                                            <button class="btn-action cancel-order" title="Hủy">Hủy<i class="fas fa-times"></i></button>
                                            <button class="btn-action view-order" title="Xem">Xem<i class="fas fa-eye"></i></button>
                                        <?php elseif ($order['TrangThai'] === 'Đang chuẩn bị'): ?>
                                            <button class="btn-action complete-order" title="Hoàn thành">Hoàn thành<i class="fas fa-check-double"></i></button>
                                            <button class="btn-action cancel-order" title="Hủy"><i class="fas fa-times"></i></button>
                                        <?php elseif ($order['TrangThai'] === 'Hoàn thành'): ?>
                                            <button class="btn-action view-order" title="Xem chi tiết">Xem chi tiết<i class="fas fa-eye"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="no-results-message" style="display: none; text-align: center; padding: 20px;">Không tìm thấy đơn hàng nào phù hợp.</div>
                </div>

                <!-- Report Buttons -->
                <div class="mt-4 flex gap-4">
                    <button id="btn-export-csv" class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
                        <i class="fas fa-file-csv"></i> Xuất CSV
                    </button>
                    <button id="btn-export-excel" class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </button>
                    <button id="btn-export-pdf" class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
                        <i class="fas fa-file-pdf"></i> Xuất PDF
                    </button>
                </div>

                <!-- Order Detail Modal -->
                <div id="order-detail-modal" class="modal">
                    <div class="modal-content">
                        <span class="close-button">&times;</span>
                        <h2 id="modal-title">CHI TIẾT ĐƠN HÀNG</h2>
                        <div id="order-details" class="mt-4">
                            <!-- Chi tiết đơn hàng sẽ được điền bởi JS -->
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary close-button"><i class="fas fa-times"></i> Đóng</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        window.ordersData = <?php echo json_encode($order_list ?? [], JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="../js/orders.js"></script>

</body>
</html>