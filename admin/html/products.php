<?php
require_once __DIR__ . '/../controller/Product_Controller.php';
$ctx = Product_Controller::handle();
extract($ctx, EXTR_OVERWRITE);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản Lý Sản Phẩm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
          integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7ISGqrIDrxlwX+uYwg=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../admin/css/products.css" />
</head>
<body class="bg-gray-100 min-h-screen font-sans">

<div id="main-layout" class="flex relative">
    <!-- Sidebar -->
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
            <a href="login.php" class="flex items-center p-3 text-gray-700 hover:bg-red-100 hover:text-red-600 rounded-lg transition duration-200">
                <i class="fa-solid fa-right-from-bracket w-5 h-5 mr-3"></i>
                <span>Đăng xuất</span>
            </a>
        </div>
    </aside>

    <!-- Content -->
    <div id="content-area" class="content-area flex-grow min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm p-4 flex items-center justify-between sticky top-0 z-20">
            <button id="menu-toggle" class="md:hidden text-gray-600 hover:text-gray-800 p-2 rounded-lg">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
            <div class="flex-grow flex justify-center mx-4 md:mx-8">
                <div class="relative w-full max-w-lg">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <form action="" method="get">
                        <input type="text" name="search"
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                               placeholder="Tìm kiếm sản phẩm..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-red-300 transition duration-150">
                    </form>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <button id="notification-bell" class="text-gray-600 hover:text-gray-800 p-2 rounded-full relative">
                    <i class="fa-solid fa-bell text-xl"></i>
                    <?php if (!empty($notification_count)): ?>
                        <span class="absolute top-1 right-1 h-3 w-3 bg-red-500 rounded-full border-2 border-white"></span>
                    <?php endif; ?>
                </button>
                <div class="notification-dropdown" id="notification-dropdown">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notification-item">
                                <span><?php echo htmlspecialchars($notif['message'] ?? ''); ?> - <?php echo htmlspecialchars($notif['time'] ?? ''); ?></span>
                                <?php if (!empty($notif['order_id'])): ?>
                                    <a href="orders.php?order_id=<?php echo htmlspecialchars((string)$notif['order_id']); ?>" class="block mt-1">Xem chi tiết</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notification-item text-center text-gray-500">Không có thông báo mới</div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-2 cursor-pointer">
                    <div class="text-sm font-medium text-gray-800 hidden sm:block">Admin.HTrà</div>
                    <img class="h-10 w-10 rounded-full object-cover border-2 border-red-300"
                         src="https://placehold.co/40x40/8f2c24/ffffff?text=AD" alt="Avatar">
                </div>
            </div>
        </header>

        <!-- Main -->
        <main class="p-4 md:p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Sản Phẩm</h1>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="search-group">
                    <input type="text" id="search-input" placeholder="Tìm theo tên sản phẩm..." />
                    <button id="btn-search" class="btn btn-search"><i class="fas fa-search">Tìm</i></button>
                </div>
                <div class="filter-group">
                    <label for="filter-category">Danh mục:</label>
                    <select id="filter-category"  name="category">
                        <option value="All">Tất cả</option>
                    </select>
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
                        <th>Số Lượng Tồn</th>
                        <th>Khuyến mãi</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($product_list_paginated)): ?>
                        <?php foreach ($product_list_paginated as $product): ?>
                            <?php
                            // Lấy an toàn các trường, có fallback theo nhiều tên cột khác nhau
                            $id      = $product['MaSanPham']  ?? $product['id']        ?? '';
                            $ten     = $product['TenSanPham'] ?? $product['name']      ?? '';
                            $gia     = $product['Gia']        ?? 0;
                            $giaCu   = $product['GiaCu']      ?? null;
                            $catId   = $product['MaDanhMuc']  ?? $product['category_id'] ?? ''; // có thể trống
                            $catName = $product['TenDanhMuc'] ?? $product['DanhMuc']   ?? '';
                            $qty     = $product['SoLuongTon'] ?? $product['SoLuong']   ?? 0;
                            $promo   = isset($product['IsPromo']) ? (int)$product['IsPromo']
                                    : (isset($product['isPromo']) ? (int)$product['isPromo'] : 0);
                            $img     = $product['HinhAnh']    ?? '';
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($img)): ?>
                                        <img src="<?= htmlspecialchars($img, ENT_QUOTES) ?>"
                                             alt="<?= htmlspecialchars($ten, ENT_QUOTES) ?>"
                                             class="w-12 h-12 object-cover rounded" />
                                    <?php else: ?>
                                        <div class="w-12 h-12 bg-gray-200 rounded"></div>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($ten, ENT_QUOTES) ?></td>
                                <td><?= number_format((float)$gia, 0, ',', '.') ?> VNĐ</td>
                                <td><?= $giaCu !== null ? number_format((float)$giaCu, 0, ',', '.') . ' VNĐ' : '-' ?></td>
                                <td><?= htmlspecialchars($catName ?: '-', ENT_QUOTES) ?></td>
                                <td><?= (int)$qty ?></td>
                                <td><?= $promo ? 'Có' : 'Không' ?></td>

                                <td class="text-center whitespace-nowrap space-x-2">
                                    <button
                                            type="button"
                                            class="edit-product px-3 py-2 rounded bg-yellow-500 text-white"
                                            data-id="<?= (int)$product['MaSanPham'] ?>"
                                            data-name="<?= htmlspecialchars($product['TenSanPham'] ?? '', ENT_QUOTES) ?>"
                                            data-price="<?= (float)($product['Gia'] ?? 0) ?>"
                                            data-old-price="<?= ($product['GiaCu'] ?? '') !== '' ? (float)$product['GiaCu'] : '' ?>"
                                            data-category-id="<?= isset($product['MaDanhMuc']) ? (int)$product['MaDanhMuc'] : '' ?>"
                                            data-category-name="<?= htmlspecialchars($product['TenDanhMuc'] ?? ($product['DanhMuc'] ?? ''), ENT_QUOTES) ?>"
                                            data-promo="<?= (int)($product['IsPromo'] ?? 0) ?>"
                                            data-qty="<?= (int)($product['SoLuongTon'] ?? 0) ?>"
                                            data-image="<?= htmlspecialchars($product['HinhAnh'] ?? '', ENT_QUOTES) ?>"
                                    >Sửa
                                    <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button
                                            type="button"
                                            class="delete-product px-3 py-2 rounded bg-red-600 text-white"
                                            data-id="<?= (int)$product['MaSanPham'] ?>"
                                            data-name="<?= htmlspecialchars($product['TenSanPham'] ?? '', ENT_QUOTES) ?>"
                                    >Xóa
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-6 text-gray-500">Không có sản phẩm.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div id="no-results-message" style="display: none; text-align: center; padding: 20px;">
                    Không tìm thấy sản phẩm nào phù hợp.
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination mt-4 flex justify-center gap-2">
                <?php
                for ($i = 1; $i <= (int)$total_pages; $i++):
                    $qs = $_GET ?? [];
                    $qs['page'] = $i;
                    $url = '?' . http_build_query($qs);
                    $isActive = ((int)$i === (int)$page);
                    ?>
                    <a href="<?php echo htmlspecialchars($url); ?>"
                       class="px-3 py-1 rounded-lg <?php echo $isActive ? 'bg-brand-bg text-white' : 'bg-gray-200 text-gray-700'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>

            <!-- Button Add Product -->
            <div class="mt-4">
                <button id="btn-add-product"
                        class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
                    <i class="fas fa-plus"></i> Thêm Sản Phẩm
                </button>
            </div>

            <!-- Product Modal -->
            <div id="product-modal" class="modal">
                <div class="modal-content">
                    <span class="close-button">&times;</span>
                    <h2 id="modal-title">THÊM SẢN PHẨM MỚI</h2>
                    <form id="product-form" enctype="multipart/form-data">
                        <input type="hidden" id="product-id" name="id" />
                        <div class="form-section">
                            <h3>Thông Tin Sản Phẩm</h3>

                            <div class="form-group">
                                <label for="name">Tên sản phẩm:</label>
                                <input type="text" id="name" name="name" required />
                            </div>

                            <div class="form-group">
                                <label for="price">Giá hiện tại:</label>
                                <input type="number" id="price" name="price" required min="0" step="1000" />
                            </div>

                            <div class="form-group">
                                <label for="old-price">Giá cũ:</label>
                                <input type="number" id="old-price" name="old-price" min="0" step="1000" />
                            </div>

                            <div class="form-group">
                                <label for="quantity">Số lượng tồn:</label>
                                <input type="number" id="quantity" name="quantity" min="0" required />
                            </div>

                            <div class="form-group">
                                <label for="category">Danh mục:</label>
                                <select id="category" name="category" required>
                                    <?php if (!empty($category_options)): ?>
                                        <?php foreach ($category_options as $opt): $v = $opt['TenDanhMuc'] ?? ''; ?>
                                            <option value="<?php echo htmlspecialchars($v); ?>">
                                                <?php echo htmlspecialchars($v); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: /* Fallback nếu controller chưa truyền $category_options */ ?>
                                        <option value="Trà">Trà</option>
                                        <option value="Bánh">Bánh</option>
                                        <option value="Cafe">Cafe</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="promo">Khuyến mãi:</label>
                                <select id="promo" name="promo" required>
                                    <option value="0">Không</option>
                                    <option value="1">Có</option>
                                </select>
                            </div>
                            <input type="hidden" id="image-old" name="image_old" />
                            <img id="image-preview" src="" alt="" style="display:none; max-height:120px; border-radius:8px;" />
                            <div class="form-group">
                                <label for="image">Ảnh sản phẩm:</label>
                                <input type="file" id="image" name="image" accept="image/*" />
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button type="submit" id="btn-save-product" class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu Thay Đổi
                            </button>
                            <button type="button" class="btn-cancel close-button">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    window.productsData = <?php echo json_encode($product_list_paginated ?? [], JSON_UNESCAPED_UNICODE); ?>;
    // NEW: xuất danh mục thực tế từ DB (controller đã trả $category_options)
    window.categoryOptions = <?php echo json_encode(array_column($category_options ?? [], 'TenDanhMuc'), JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="../js/products.js"></script>
</body>
</html>
