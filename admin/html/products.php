<?php
require_once __DIR__ . '/../controller/Product_Controller.php';
$ctx = Product_Controller::handle();
extract($ctx, EXTR_OVERWRITE);

// Thiết lập tiêu đề trang
$page_title = 'Quản Lý Sản Phẩm';

// Bắt đầu output buffering
ob_start();
?>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Sản Phẩm</h1>

    <!-- Stats Summary -->
    <div class="mb-6 stats-card bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-100 font-medium">Tổng số sản phẩm</p>
                <p class="text-3xl font-bold text-white"><?= number_format($total_products, 0, ',', '.') ?></p>
            </div>
            <i class="fas fa-box-open text-5xl text-white opacity-20"></i>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar mb-6">
        <div class="search-group">
            <input type="text" id="search-input" placeholder="Tìm theo tên sản phẩm..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" />
            <button id="btn-search" class="btn-search">
                <i class="fas fa-search"></i> Tìm kiếm
            </button>
        </div>
        <div class="filter-group">
            <label for="filter-category">Danh mục:</label>
            <select id="filter-category" name="category">
                <option value="All" <?= ($cat ?? 'All') === 'All' ? 'selected' : '' ?>>Tất cả</option>
                <?php foreach ($category_options ?? [] as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['TenDanhMuc']) ?>"
                            <?= ($cat ?? '') === $opt['TenDanhMuc'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt['TenDanhMuc']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Product Table -->
    <div class="product-table-container bg-white rounded-lg shadow-sm overflow-hidden">
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
                    $id      = $product['MaSanPham']  ?? '';
                    $ten     = $product['TenSanPham'] ?? '';
                    $gia     = $product['Gia']        ?? 0;
                    $giaCu   = $product['GiaCu']      ?? null;
                    $catName = $product['TenDanhMuc'] ?? '';
                    $qty     = $product['SoLuongTon'] ?? 0;
                    $promo   = (int)($product['IsPromo'] ?? 0);
                    $img     = $product['HinhAnh']    ?? '';
                    $moTa    = $product['MoTa']       ?? '';
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($img)): ?>
                                <img src="<?= htmlspecialchars($img) ?>"
                                     alt="<?= htmlspecialchars($ten) ?>"
                                     class="w-14 h-14 object-cover rounded-lg border border-gray-200" />
                            <?php else: ?>
                                <div class="w-14 h-14 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="font-medium"><?= htmlspecialchars($ten) ?></td>
                        <td class="text-green-600 font-semibold"><?= number_format((float)$gia, 0, ',', '.') ?> ₫</td>
                        <td class="text-gray-500"><?= $giaCu !== null ? number_format((float)$giaCu, 0, ',', '.') . ' ₫' : '-' ?></td>
                        <td><?= htmlspecialchars($catName ?: '-') ?></td>
                        <td class="text-center">
                            <span class="<?= $qty > 10 ? 'text-green-600' : ($qty > 0 ? 'text-yellow-600' : 'text-red-600') ?> font-medium">
                                <?= (int)$qty ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($promo): ?>
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">
                                    <i class="fas fa-tag"></i> Có
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Không</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center whitespace-nowrap space-x-2">
                            <button
                                    type="button"
                                    class="edit-product px-3 py-2 rounded-lg bg-yellow-500 text-white hover:bg-yellow-600 transition"
                                    data-id="<?= (int)$id ?>"
                                    data-name="<?= htmlspecialchars($ten) ?>"
                                    data-price="<?= (float)$gia ?>"
                                    data-old-price="<?= $giaCu ?? '' ?>"
                                    data-category-name="<?= htmlspecialchars($catName) ?>"
                                    data-promo="<?= $promo ?>"
                                    data-qty="<?= $qty ?>"
                                    data-image="<?= htmlspecialchars($img) ?>"
                                    data-description="<?= htmlspecialchars($moTa) ?>"
                            >
                                <i class="fa-solid fa-pen"></i> Sửa
                            </button>
                            <button
                                    type="button"
                                    class="delete-product px-3 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition"
                                    data-id="<?= (int)$id ?>"
                                    data-name="<?= htmlspecialchars($ten) ?>"
                            >
                                <i class="fa-solid fa-trash"></i> Xóa
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center py-12">
                        <div class="text-gray-400">
                            <i class="fa-solid fa-box-open text-5xl mb-3 opacity-50"></i>
                            <p class="text-lg font-medium">Không có sản phẩm nào</p>
                            <p class="text-sm mt-1">Thử điều chỉnh bộ lọc hoặc thêm sản phẩm mới</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ✅ Pagination - CĂNG GIỮA -->
<?php if ($total_pages > 1): ?>
    <div class="mt-6">
        <!-- Page Info - Căn giữa -->
        <div class="pagination-info text-center mb-3">
            Hiển thị <?= min(($page - 1) * $per_page + 1, $total_products) ?> - <?= min($page * $per_page, $total_products) ?>
            trong tổng số <strong><?= number_format($total_products, 0, ',', '.') ?></strong> sản phẩm
        </div>

        <!-- Pagination Buttons - Căn giữa -->
        <div class="pagination-buttons flex justify-center">
            <?php
            $current_query = $_GET;

            // Previous button
            if ($page > 1):
                $current_query['page'] = $page - 1;
                $prev_url = '?' . http_build_query($current_query);
                ?>
                <a href="<?= htmlspecialchars($prev_url) ?>">
                    <i class="fas fa-chevron-left"></i> Trước
                </a>
            <?php endif; ?>

            <?php
            // Page numbers
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);

            if ($start > 1):
                ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                <?php if ($start > 2): ?>
                <span class="dots">...</span>
            <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++):
                $current_query['page'] = $i;
                $url = '?' . http_build_query($current_query);
                $isActive = ($i === $page);
                ?>
                <a href="<?= htmlspecialchars($url) ?>" class="<?= $isActive ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?>
                    <span class="dots">...</span>
                <?php endif; ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
            <?php endif; ?>

            <!-- Next button -->
            <?php if ($page < $total_pages):
                $current_query['page'] = $page + 1;
                $next_url = '?' . http_build_query($current_query);
                ?>
                <a href="<?= htmlspecialchars($next_url) ?>">
                    Sau <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

    <!-- Button Add Product -->
    <div class="mt-6 text-center">
        <button id="btn-add-product"
                class="py-3 px-6 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 font-medium shadow-md hover:shadow-lg">
            <i class="fas fa-plus-circle"></i> Thêm Sản Phẩm Mới
        </button>
    </div>

    <!-- ✅ Product Modal - PHẦN NÀY BỊ THIẾU -->
    <div id="product-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modal-title">THÊM SẢN PHẨM MỚI</h3>
                <button class="close-button" type="button" aria-label="Đóng">×</button>
            </div>

            <form id="product-form" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" id="product-id" name="id">
                <input type="hidden" id="image-old" name="image_old">

                <div class="modal-body">
                    <div class="form-grid">
                        <!-- ROW 1: Tên & Danh mục -->
                        <div class="field">
                            <label for="name">Tên sản phẩm</label>
                            <input id="name" name="name" type="text" class="input" placeholder="VD: Hồng Trà Cổ Thụ (KM)" required>
                        </div>
                        <div class="field">
                            <label for="category">Danh mục</label>
                            <select id="category" name="category" class="select" required>
                                <?php if (!empty($category_options)): ?>
                                    <?php foreach ($category_options as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt['TenDanhMuc'] ?? '', ENT_QUOTES) ?>">
                                            <?= htmlspecialchars($opt['TenDanhMuc'] ?? '', ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">-- Chọn danh mục --</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- ROW 2: Giá hiện tại & Số lượng -->
                        <div class="field">
                            <label for="price">Giá hiện tại</label>
                            <div class="input-group">
                                <input id="price" name="price" type="number" min="0" step="1000" class="input" placeholder="0" required>
                                <span class="suffix">VNĐ</span>
                            </div>
                        </div>
                        <div class="field">
                            <label for="quantity">Số lượng tồn</label>
                            <input id="quantity" name="quantity" type="number" min="0" step="1" class="input" placeholder="0" required>
                        </div>

                        <!-- ROW 3: Giá cũ & Khuyến mãi -->
                        <div class="field">
                            <label for="old-price">Giá cũ</label>
                            <div class="input-group">
                                <input id="old-price" name="old-price" type="number" min="0" step="1000" class="input" placeholder="0">
                                <span class="suffix">VNĐ</span>
                            </div>
                        </div>
                        <div class="field">
                            <label for="promo">Khuyến mãi</label>
                            <select id="promo" name="promo" class="select">
                                <option value="0">Không</option>
                                <option value="1">Có</option>
                            </select>
                        </div>

                        <!-- ROW 4: Mô tả -->
                        <div class="field full">
                            <label for="description">Mô tả</label>
                            <textarea id="description" name="description" rows="3" class="textarea" placeholder="Mô tả ngắn về sản phẩm..."></textarea>
                        </div>

                        <!-- ROW 5: Ảnh -->
                        <div class="field full">
                            <label>Ảnh sản phẩm</label>
                            <div class="image-wrap">
                                <img id="image-preview" class="preview" alt="Preview" style="display:none;">
                                <div style="flex:1">
                                    <input id="image" name="image" type="file" accept="image/*" class="input">
                                    <div class="note-line">
                                        <span class="badge">Tip</span>
                                        <span>Nếu không chọn ảnh mới, hệ thống sẽ giữ ảnh cũ.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                    <button type="button" id="btn-cancel" class="btn btn-ghost">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CSS cho trang Products -->
    <link rel="stylesheet" href="../css/products.css" />

    <!-- JavaScript -->
    <script>
        window.productsData = <?= json_encode($product_list_paginated ?? [], JSON_UNESCAPED_UNICODE) ?>;
        window.categoryOptions = <?= json_encode(array_column($category_options ?? [], 'TenDanhMuc'), JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="../js/products.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/template.php';
?>