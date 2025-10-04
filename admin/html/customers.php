<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include __DIR__ . '/../../db.php'; // Đảm bảo đường dẫn đúng đến db.php

// Giả lập dữ liệu thông báo (thay bằng query thực tế)
$notifications = [
    ['id' => 1, 'type' => 'new_order', 'message' => 'Đơn hàng mới từ Nguyễn Văn A (HD00125)', 'time' => '12:30 AM', 'order_id' => 'HD00125'],
    ['id' => 2, 'type' => 'payment_received', 'message' => 'Khách hàng Chị Mai đã thanh toán (HD00124)', 'time' => '12:15 AM', 'order_id' => 'HD00124'],
    ['id' => 3, 'type' => 'pending_order', 'message' => 'Đơn hàng HD00123 chờ xác nhận', 'time' => '11:50 PM', 'order_id' => 'HD00123']
];
$notification_count = count($notifications);

// Hàm lấy danh sách khách hàng (sử dụng dữ liệu giả định)
function getCustomers($conn) {
    $customers = [
        ['id' => 1, 'name' => 'Nguyễn Văn A', 'email' => 'a@gmail.com', 'phone' => '0909xxxxxx', 'address' => 'HCM', 'role' => 'USER', 'rank' => 'Silver', 'status' => 'Hoạt động', 'created_at' => '2025-01-01'],
        ['id' => 2, 'name' => 'Trần Thị B', 'email' => 'b@gmail.com', 'phone' => '0912xxxxxx', 'address' => 'Hà Nội', 'role' => 'USER', 'rank' => 'Gold', 'status' => 'Hoạt động', 'created_at' => '2025-02-10'],
        ['id' => 3, 'name' => 'Lê Văn C', 'email' => 'c@gmail.com', 'phone' => '0987xxxxxx', 'address' => 'Đà Nẵng', 'role' => 'USER', 'rank' => 'Bronze', 'status' => 'Ngưng', 'created_at' => '2025-03-20'],
    ];
    return $customers;
}

$customer_list = getCustomers($conn); // Sử dụng kết nối DB nếu có, hiện tại dùng dữ liệu giả định
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Khách Hàng</title>
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

        /* Thêm style cho bảng khách hàng */
        .customer-table { width: 100%; border-collapse: collapse; background-color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .customer-table th, .customer-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .customer-table th { background-color: #8f2c24; color: white; }
        .customer-table tr:hover { background-color: #f5f5f5; }
        .customer-table .status-active { background-color: #d4edda; color: #155724; padding: 2px 8px; border-radius: 12px; }
        .customer-table .status-inactive { background-color: #f8d7da; color: #721c24; padding: 2px 8px; border-radius: 12px; }
        .customer-table .rank-gold { color: #ffd700; font-weight: bold; }
        .customer-table .rank-silver { color: #c0c0c0; font-weight: bold; }
        .customer-table .rank-bronze { color: #cd7f32; font-weight: bold; }

        /* Style cho filter bar */
        .filter-bar { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-bar .search-group { display: flex; align-items: center; gap: 10px; }
        .filter-bar .search-group input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-bar .search-group button { padding: 8px 15px; background-color: #8f2c24; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-bar .filter-group { display: flex; align-items: center; gap: 10px; }
        .filter-bar .filter-group select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-bar .advanced-filters { display: flex; gap: 10px; margin-top: 10px; }
        .filter-bar .advanced-filters.hidden { display: none; }
        .filter-bar .advanced-filters .filter-group input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-bar .btn-toggle-advanced { padding: 8px 15px; background-color: #6d1f18; color: white; border: none; border-radius: 4px; cursor: pointer; }

        /* Style cho modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background-color: white; padding: 20px; border-radius: 8px; width: 500px; max-height: 80vh; overflow-y: auto; }
        .close-button { position: absolute; top: 10px; right: 15px; font-size: 20px; cursor: pointer; color: #333; }
        .form-section { margin-bottom: 20px; }
        .form-section h3 { margin-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .modal-actions .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .modal-actions .btn-primary { background-color: #8f2c24; color: white; }
        .modal-actions .btn-primary:hover { background-color: #6d1f18; }
        .modal-actions .btn-secondary { background-color: #ccc; color: #333; }
        .modal-actions .btn-secondary:hover { background-color: #bbb; }
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
                        <a href="dashboard.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-200 rounded-lg transition duration-200">
                            <i class="fa-solid fa-house w-5 h-5 mr-3"></i>
                            <span>Trang tổng quan</span>
                        </a>
                    </li>
                    <li>
                        <a href="customers.php" class="flex items-center p-3 text-white brand-bg rounded-lg shadow-md font-medium">
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
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Khách Hàng</h1>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="search-group">
                        <input type="text" id="search-input" placeholder="Tìm theo: Họ tên, SĐT, Email, Mã KH...">
                        <button id="btn-search" class="btn btn-search"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="filter-group">
                        <label for="filter-status">Trạng thái:</label>
                        <select id="filter-status">
                            <option value="All">Tất cả</option>
                            <option value="Hoạt động">Hoạt động</option>
                            <option value="Ngưng">Ngưng</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-rank">Hạng KH:</label>
                        <select id="filter-rank">
                            <option value="All">Tất cả</option>
                            <option value="Gold">Gold</option>
                            <option value="Silver">Silver</option>
                            <option value="Bronze">Bronze</option>
                            <option value="Mới">Mới</option>
                        </select>
                    </div>
                    <button id="btn-toggle-advanced-filter" class="btn-toggle-advanced"><i class="fas fa-sliders-h"></i> Bộ lọc nâng cao</button>
                    <div id="advanced-filters" class="advanced-filters hidden">
                        <div class="filter-group">
                            <label for="filter-city">Tỉnh/Thành:</label>
                            <input type="text" id="filter-city" placeholder="Tỉnh/Thành...">
                        </div>
                        <div class="filter-group">
                            <label for="filter-date-from">Ngày tạo (Từ):</label>
                            <input type="date" id="filter-date-from">
                        </div>
                        <div class="filter-group">
                            <label for="filter-date-to">Ngày tạo (Đến):</label>
                            <input type="date" id="filter-date-to">
                        </div>
                    </div>
                </div>

                <!-- Customer Table -->
                <div class="customer-table-container mt-6">
                    <table id="customer-list-table" class="customer-table w-full">
                        <thead>
                            <tr>
                                <th>Mã KH</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>SĐT</th>
                                <th>Địa chỉ</th>
                                <th>Hạng</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customer_list as $customer): ?>
                                <tr data-id="<?php echo $customer['id']; ?>">
                                    <td><?php echo $customer['id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                    <td class="rank-<?php echo strtolower($customer['rank']); ?>"><?php echo htmlspecialchars($customer['rank']); ?></td>
                                    <td><span class="status-badge status-<?php echo ($customer['status'] == 'Hoạt động' ? 'active' : 'inactive'); ?>"><?php echo htmlspecialchars($customer['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($customer['created_at']); ?></td>
                                    <td>
                                        <button class="btn-action view-detail" title="Xem chi tiết"><i class="fas fa-eye"></i></button>
                                        <button class="btn-action edit-customer" title="Sửa thông tin"><i class="fas fa-edit"></i></button>
                                        <button class="btn-action toggle-status" title="<?php echo ($customer['status'] == 'Hoạt động' ? 'Khóa' : 'Mở khóa'); ?>"><i class="fas fa-<?php echo ($customer['status'] == 'Hoạt động' ? 'lock' : 'unlock-alt'); ?>"></i></button>
                                        <button class="btn-action delete-customer" title="Xóa khách hàng"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="no-results-message" style="display: none; text-align: center; padding: 20px;">Không tìm thấy khách hàng nào phù hợp.</div>
                </div>

                <!-- Button Add Customer -->
                <div class="mt-4">
                    <button id="btn-add-customer" class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
                        <i class="fas fa-plus"></i> Thêm Khách Hàng
                    </button>
                </div>

                <!-- Customer Modal -->
                <div id="customer-modal" class="modal">
                    <div class="modal-content">
                        <span class="close-button">&times;</span>
                        <h2 id="modal-title">THÊM KHÁCH HÀNG MỚI</h2>
                        <form id="customer-form">
                            <input type="hidden" id="customer-id" name="id">
                            
                            <div class="form-section">
                                <h3>Thông Tin Cơ Bản</h3>
                                <div class="form-group"><label for="full-name">Họ Tên:</label><input type="text" id="full-name" name="name" required></div>
                                <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email" required></div>
                                <div class="form-group"><label for="phone">SĐT:</label><input type="tel" id="phone" name="phone" required></div>
                                <div class="form-group"><label for="dob">Ngày Sinh:</label><input type="date" id="dob" name="dob"></div>
                                <div class="form-group">
                                    <label for="gender">Giới Tính:</label>
                                    <select id="gender" name="gender">
                                        <option value="">Chọn</option>
                                        <option value="Nam">Nam</option>
                                        <option value="Nữ">Nữ</option>
                                        <option value="Khác">Khác</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Địa Chỉ & Trạng Thái</h3>
                                <div class="form-group"><label for="address">Địa Chỉ:</label><input type="text" id="address" name="address"></div>
                                <div class="form-group"><label for="city">Tỉnh/Thành:</label><input type="text" id="city" name="city"></div>
                                <div class="form-group">
                                    <label for="status">Trạng Thái:</label>
                                    <select id="status" name="status" required>
                                        <option value="Hoạt động">Hoạt động</option>
                                        <option value="Ngưng">Ngưng</option>
                                    </select>
                                </div>
                                <div class="form-group rank-display">
                                    <label for="rank">Hạng Khách Hàng:</label>
                                    <span id="display-rank" class="rank-badge">Mới</span> 
                                </div>
                            </div>

                            <div class="form-section history-notes-section" id="history-notes-section">
                                <h3>Lịch Sử & Ghi Chú</h3>
                                <div class="form-group">
                                    <label for="purchase-history">Lịch Sử Mua Hàng (Mã ĐH, Ngày, Tổng tiền):</label>
                                    <textarea id="purchase-history" rows="5" readonly></textarea>
                                    <button type="button" class="btn btn-view-orders mt-2"><i class="fas fa-list-alt"></i> Xem Chi Tiết Đơn Hàng</button>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Ghi Chú:</label>
                                    <textarea id="notes" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                            
                            <div class="modal-actions">
                                <button type="submit" id="btn-save-customer" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                                <button type="button" class="btn btn-secondary close-button"><i class="fas fa-times"></i> Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        window.customersData = <?php echo json_encode($customer_list); ?> || [];
    </script>
    <script src="js/customers.js"></script>
</body>
</html>