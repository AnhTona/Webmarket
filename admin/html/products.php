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

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="search-group">
            <input type="text" id="search-input" placeholder="Tìm theo tên sản phẩm..." />
            <button id="btn-search" class="btn btn-search"><i class="fas fa-search">Tìm</i></button>
        </div>
        <div class="filter-group">
            <label for="filter-category">Danh mục:</label>
            <select id="filter-category" name="category">
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
                    $id      = $product['MaSanPham']  ?? $product['id']        ?? '';
                    $ten     = $product['TenSanPham'] ?? $product['name']      ?? '';
                    $gia     = $product['Gia']        ?? 0;
                    $giaCu   = $product['GiaCu']      ?? null;
                    $catId   = $product['MaDanhMuc']  ?? $product['category_id'] ?? '';
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
                            <?php else: ?>
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

    <!-- CSS cho trang Products -->
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/products.css" />

    <!-- JavaScript -->
    <script>
        window.productsData = <?php echo json_encode($product_list_paginated ?? [], JSON_UNESCAPED_UNICODE); ?>;
        window.categoryOptions = <?php echo json_encode(array_column($category_options ?? [], 'TenDanhMuc'), JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="../js/products.js"></script>

<?php
// Lấy nội dung đã capture
$content = ob_get_clean();

// Include template
require_once __DIR__ . '/template.php';
?>