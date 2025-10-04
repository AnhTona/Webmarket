<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include __DIR__ . '/../../db.php'; // Đảm bảo đường dẫn đúng đến db.php

// Giả lập dữ liệu thông báo
$notifications = [
    ['id' => 1, 'type' => 'new_order', 'message' => 'Đơn hàng mới từ Nguyễn Văn A (HD00125)', 'time' => '12:30 AM', 'order_id' => 'HD00125'],
    ['id' => 2, 'type' => 'payment_received', 'message' => 'Khách hàng Chị Mai đã thanh toán (HD00124)', 'time' => '12:15 AM', 'order_id' => 'HD00124'],
    ['id' => 3, 'type' => 'pending_order', 'message' => 'Đơn hàng HD00123 chờ xác nhận', 'time' => '11:50 PM', 'order_id' => 'HD00123']
];
$notification_count = count($notifications);

// Giả lập dữ liệu sản phẩm (thay bằng SELECT * FROM sanpham)
function getProducts($conn) {
    $products = [
        ['MaSanPham' => 1, 'HinhAnh' => 'https://placehold.co/50x50/8f2c24/ffffff?text=P1', 'TenSanPham' => 'Trà Đào', 'Gia' => 35000, 'GiaCu' => 40000, 'DanhMuc' => 'Trà', 'Loai' => 'Trà', 'isPromo' => 1],
        ['MaSanPham' => 2, 'HinhAnh' => 'https://placehold.co/50x50/8f2c24/ffffff?text=P2', 'TenSanPham' => 'Bánh Mì', 'Gia' => 20000, 'GiaCu' => null, 'DanhMuc' => 'Bánh', 'Loai' => 'Bánh', 'isPromo' => 0],
        ['MaSanPham' => 3, 'HinhAnh' => 'https://placehold.co/50x50/8f2c24/ffffff?text=P3', 'TenSanPham' => 'Trà Sữa Đặc Biệt', 'Gia' => 45000, 'GiaCu' => 50000, 'DanhMuc' => 'Trà', 'Loai' => 'Đặc biệt', 'isPromo' => 1],
    ];
    return $products;
}

$product_list = getProducts($conn); // Sử dụng kết nối DB nếu có
$total_products = count($product_list);
$per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $per_page;
$end = min($start + $per_page, $total_products);
$product_list_paginated = array_slice($product_list, $start, $per_page);
$total_pages = ceil($total_products / $per_page);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7ISGqrIDrxlwX+uYwg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/products.css">
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
                        <a href="products.php" class="flex items-center p-3 text-white brand-bg rounded-lg shadow-md font-medium">
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
                            <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Tìm kiếm sản phẩm..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-red-300 transition duration-150">
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
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Sản Phẩm</h1>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="search-group">
                        <input type="text" id="search-input" placeholder="Tìm theo tên sản phẩm...">
                        <button id="btn-search" class="btn btn-search"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="filter-group">
                        <label for="filter-category">Danh mục:</label>
                        <select id="filter-category">
                            <option value="All">Tất cả</option>
                            <option value="Trà">Trà</option>
                            <option value="Bánh">Bánh</option>
                        </select>
                    </div>
                    <button id="btn-toggle-advanced-filter" class="btn-toggle-advanced"><i class="fas fa-sliders-h"></i> Bộ lọc nâng cao</button>
                    <div id="advanced-filters" class="advanced-filters hidden">
                        <div class="filter-group">
                            <label for="filter-type">Loại:</label>
                            <select id="filter-type">
                                <option value="All">Tất cả</option>
                                <option value="Trà">Trà</option>
                                <option value="Bánh">Bánh</option>
                                <option value="Đặc biệt">Đặc biệt</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="filter-promo">Khuyến mãi:</label>
                            <select id="filter-promo">
                                <option value="All">Tất cả</option>
                                <option value="1">Có</option>
                                <option value="0">Không</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Product Table -->
                <div class="product-table-container mt-6">
                    <table id="product-list-table" class="product-table w-full">
                        <thead>
                            <tr>
                                <th>Ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Giá hiện tại</th>
                                <th>Giá cũ</th>
                                <th>Danh mục</th>
                                <th>Loại</th>
                                <th>Khuyến mãi</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = $start; $i < $end; $i++): ?>
                                <?php $product = $product_list[$i]; ?>
                                <tr data-id="<?php echo $product['MaSanPham']; ?>">
                                    <td><img src="<?php echo htmlspecialchars($product['HinhAnh']); ?>" alt="<?php echo htmlspecialchars($product['TenSanPham']); ?>" class="w-12 h-12 object-cover rounded"></td>
                                    <td><?php echo htmlspecialchars($product['TenSanPham']); ?></td>
                                    <td><?php echo number_format($product['Gia'], 0, ',', '.') . ' VNĐ'; ?></td>
                                    <td><?php echo $product['GiaCu'] ? number_format($product['GiaCu'], 0, ',', '.') . ' VNĐ' : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($product['DanhMuc']); ?></td>
                                    <td><?php echo htmlspecialchars($product['Loai']); ?></td>
                                    <td><?php echo $product['isPromo'] ? 'Có' : 'Không'; ?></td>
                                    <td>
                                        <button class="btn-action edit-product" title="Sửa"><i class="fas fa-edit"></i></button>
                                        <button class="btn-action delete-product" title="Xóa"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    <div id="no-results-message" style="display: none; text-align: center; padding: 20px;">Không tìm thấy sản phẩm nào phù hợp.</div>
                </div>

                <!-- Pagination -->
                <div class="pagination mt-4 flex justify-center gap-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="px-3 py-1 rounded-lg <?php echo $i == $page ? 'bg-brand-bg text-white' : 'bg-gray-200 text-gray-700'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>

                <!-- Button Add Product -->
                <div class="mt-4">
                    <button id="btn-add-product" class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
                        <i class="fas fa-plus"></i> Thêm Sản Phẩm
                    </button>
                </div>

                <!-- Product Modal -->
                <div id="product-modal" class="modal">
                    <div class="modal-content">
                        <span class="close-button">&times;</span>
                        <h2 id="modal-title">THÊM SẢN PHẨM MỚI</h2>
                        <form id="product-form" enctype="multipart/form-data">
                            <input type="hidden" id="product-id" name="id">
                            <div class="form-section">
                                <h3>Thông Tin Sản Phẩm</h3>
                                <div class="form-group"><label for="name">Tên sản phẩm:</label><input type="text" id="name" name="name" required></div>
                                <div class="form-group"><label for="price">Giá hiện tại:</label><input type="number" id="price" name="price" required min="0" step="1000"></div>
                                <div class="form-group"><label for="old-price">Giá cũ:</label><input type="number" id="old-price" name="old-price" min="0" step="1000"></div>
                                <div class="form-group">
                                    <label for="category">Danh mục:</label>
                                    <select id="category" name="category" required>
                                        <option value="Trà">Trà</option>
                                        <option value="Bánh">Bánh</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="type">Loại:</label>
                                    <select id="type" name="type" required>
                                        <option value="Trà">Trà</option>
                                        <option value="Bánh">Bánh</option>
                                        <option value="Đặc biệt">Đặc biệt</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="promo">Khuyến mãi:</label>
                                    <select id="promo" name="promo" required>
                                        <option value="0">Không</option>
                                        <option value="1">Có</option>
                                    </select>
                                </div>
                                <div class="form-group"><label for="image">Ảnh sản phẩm:</label><input type="file" id="image" name="image" accept="image/*"></div>
                            </div>
                            <div class="modal-actions">
                                <button type="submit" id="btn-save-product" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                                <button type="button" class="btn btn-secondary close-button"><i class="fas fa-times"></i> Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        window.productsData = <?php echo json_encode($product_list); ?> || [];
    </script>
    <script src="../js/products.js"></script>
</body>
</html>