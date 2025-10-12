<?php
require_once __DIR__ . '/../controller/Tables_Controller.php';
$ctx = TablesController::handle();
extract($ctx, EXTR_OVERWRITE);

// nếu bạn vẫn đang dùng header hiển thị thông báo:
$notifications      = $notifications ?? [];
$notification_count = is_array($notifications) ? count($notifications) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Bàn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7ISGqrIDrxlwX+uYwg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/tables.css">
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
                        <a href="tables.php" class="flex items-center p-3 text-white brand-bg rounded-lg shadow-md font-medium">
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
                        <a href="orders.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200">
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
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Bàn & Đặt Bàn</h1>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="search-group">
                        <input type="text" id="search-input" placeholder="Tìm theo: Mã bàn...">
                        <button id="btn-search" class="btn btn-search"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="filter-group">
                        <label for="filter-status">Trạng thái:</label>
                        <select id="filter-status">
                            <option value="All">Tất cả</option>
                            <option value="Trống">Trống</option>
                            <option value="Đang đặt">Đang đặt</option>
                            <option value="Đang sử dụng">Đang sử dụng</option>
                            <option value="Bảo trì">Bảo trì</option>
                        </select>
                    </div>
                    <button id="btn-toggle-advanced-filter" class="btn-toggle-advanced"><i class="fas fa-sliders-h"></i> Bộ lọc nâng cao</button>
                    <div id="advanced-filters" class="advanced-filters hidden">
                        <div class="filter-group">
                            <label for="filter-seats">Số lượng chỗ:</label>
                            <input type="number" id="filter-seats" placeholder="Số chỗ..." min="1">
                        </div>
                    </div>
                </div>

                <!-- Table List -->
                <div class="table-container mt-6">
                    <table id="table-list-table" class="table w-full">
                        <thead>
                            <tr>
                                <th>Mã bàn</th>
                                <th>Số lượng chỗ</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_list as $table): ?>
                                <tr data-id="<?php echo $table['id']; ?>">
                                    <td><?php echo $table['id']; ?></td>
                                    <td><?php echo $table['seats']; ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $table['status'])); ?>"><?php echo htmlspecialchars($table['status']); ?></span></td>
                                    <td>
                                        <?php if ($table['status'] === 'Trống'): ?>
                                            <button class="btn-action book-table" title="Đặt bàn"><i class="fas fa-calendar-check"></i></button>
                                            <button class="btn-action edit-table" title="Chỉnh sửa"><i class="fas fa-edit"></i></button>
                                            <button class="btn-action delete-table" title="Xóa"><i class="fas fa-trash"></i></button>
                                        <?php elseif ($table['status'] === 'Đang đặt'): ?>
                                            <button class="btn-action view-detail" title="Xem chi tiết"><i class="fas fa-eye"></i></button>
                                            <button class="btn-action cancel-booking" title="Hủy đặt"><i class="fas fa-times"></i></button>
                                        <?php elseif ($table['status'] === 'Đang sử dụng'): ?>
                                            <button class="btn-action checkout" title="Thanh toán"><i class="fas fa-money-bill-wave"></i></button>
                                            <button class="btn-action change-status" title="Chuyển trạng thái"><i class="fas fa-sync"></i></button>
                                        <?php elseif ($table['status'] === 'Bảo trì'): ?>
                                            <button class="btn-action edit-table" title="Chỉnh sửa"><i class="fas fa-edit"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="no-results-message" style="display: none; text-align: center; padding: 20px;">Không tìm thấy bàn nào phù hợp.</div>
                </div>

                <!-- Button Add Table -->
                <div class="mt-4">
                    <button id="btn-add-table" class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
                        <i class="fas fa-plus"></i> Thêm Bàn Mới
                    </button>
                </div>

                <!-- Table Modal -->
                <div id="table-modal" class="modal">
                    <div class="modal-content">
                        <span class="close-button">&times;</span>
                        <h2 id="modal-title">THÊM BÀN MỚI</h2>
                        <form id="table-form">
                            <input type="hidden" id="table-id" name="id">
                            <div class="form-section">
                                <h3>Thông Tin Bàn</h3>
                                <div class="form-group"><label for="seats">Số lượng chỗ:</label><input type="number" id="seats" name="seats" required min="1"></div>
                                <div class="form-group">
                                    <label for="status">Trạng thái:</label>
                                    <select id="status" name="status" required>
                                        <option value="Trống">Trống</option>
                                        <option value="Đang đặt">Đang đặt</option>
                                        <option value="Đang sử dụng">Đang sử dụng</option>
                                        <option value="Bảo trì">Bảo trì</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-actions">
                                <button type="submit" id="btn-save-table" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                                <button type="button" class="btn btn-secondary close-button"><i class="fas fa-times"></i> Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Section -->
                <div class="mt-8 bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Báo cáo Số lần Sử dụng Bàn</h2>
                    <div id="usage-report" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <?php foreach ($table_list as $table): ?>
                            <div class="p-4 border rounded-lg bg-gray-50">
                                <p class="text-sm text-gray-600">Bàn #<?php echo $table['id']; ?></p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $table['usage_count']; ?></p>
                                <p class="text-xs text-gray-500">lần sử dụng</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        window.tablesData = <?php echo json_encode($table_list ?? [], JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="../js/tables.js"></script>
</body>
</html>