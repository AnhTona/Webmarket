<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản Phẩm - Trà & Bánh Trung Thu</title>
    <link rel="stylesheet" href="/Webmarket/view/css/products.css">
    <link rel="stylesheet" href="/Webmarket/view/css/home.css">
    <link rel="stylesheet" href="/Webmarket/view/css/search.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- ✅ SỬA: Bỏ /Webmarket/ trong include vì đã ở trong thư mục html -->
<?php include 'header.php'; ?>

<main class="products-container">
    <div class="breadcrumb">
        <a href="/Webmarket/home">Trang Chủ</a> / <a href="/Webmarket/products">Sản Phẩm</a><span id="breadcrumb-category"></span>
    </div>
    <div class="content-wrapper">
        <div class="sidebar-left">
            <h2>Danh Mục</h2>
            <ul class="category-list">
                <li class="category-item">
                    <a href="#" class="category-link" data-filter="All">Tất Cả Sản Phẩm</a>
                </li>
                <li class="category-item">
                    <a href="#" class="category-link dropdown-toggle" data-filter="Trà">
                        Trà <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-menu-item"><a href="#" data-filter="Lục Trà">Lục Trà</a></li>
                        <li class="dropdown-menu-item"><a href="#" data-filter="Hồng Trà">Hồng Trà</a></li>
                        <li class="dropdown-menu-item"><a href="#" data-filter="Bạch Trà">Bạch Trà</a></li>
                        <li class="dropdown-menu-item"><a href="#" data-filter="Oolong Trà">Oolong Trà</a></li>
                        <li class="dropdown-menu-item"><a href="#" data-filter="Phổ Nhĩ">Phổ Nhĩ</a></li>
                    </ul>
                </li>
                <li class="category-item">
                    <a href="#" class="category-link dropdown-toggle" data-filter="Bánh">
                        Bánh <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-menu-item"><a href="#" data-filter="Bánh Nướng">Bánh Nướng</a></li>
                        <li class="dropdown-menu-item"><a href="#" data-filter="Bánh Dẻo">Bánh Dẻo</a></li>
                        <li class="dropdown-menu-item"><a href="#" data-filter="Bánh Ăn Kèm">Bánh Ăn Kèm</a></li>
                    </ul>
                </li>
                <li class="category-item">
                    <a href="#" class="category-link" data-filter="Combo">Combo</a>
                </li>
                <li class="category-item">
                    <a href="#" class="category-link" data-filter="Khuyến mãi">Khuyến Mãi</a>
                </li>
            </ul>
        </div>
        <div class="main-content">
            <div class="title-filter">
                <h1>DANH SÁCH SẢN PHẨM</h1>
                <div class="filter-group">
                    <select id="sort-by">
                        <option value="default">Sắp xếp mặc định</option>
                        <option value="price_asc">Sắp xếp theo giá thấp nhất</option>
                        <option value="price_desc">Sắp xếp theo giá cao nhất</option>
                        <option value="newest">Mới nhất</option>
                        <option value="popularity_desc">Sắp xếp theo mức độ phổ biến</option>
                    </select>
                </div>
            </div>
            <div class="product-grid" id="product-grid"></div>
            <div id="no-products-message">Xin lỗi, không tìm thấy sản phẩm nào trong danh mục này.</div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<!-- Overlay & Minicart -->
<div id="overlay" aria-hidden="true"></div>
<div id="mini-cart" aria-hidden="true" role="dialog" aria-label="Giỏ hàng mini">
    <div class="minicart-header">
        <span class="title">Giỏ hàng</span>
        <button type="button" class="close-btn" aria-label="Đóng mini cart">&times;</button>
    </div>
    <div id="minicart-items-list" class="minicart-items" aria-live="polite"></div>
    <div class="minicart-footer">
        <div class="minicart-total-row">
            <span id="minicart-item-count">0 sản phẩm</span>
            <span id="minicart-total-price">0 VND</span>
        </div>
        <a href="/Webmarket/cart" class="btn-view-cart">Xem giỏ hàng</a>
    </div>
</div>

<!-- ✅ SỬA: Thêm dấu " đóng đúng -->
<script src="/Webmarket/view/js/products.js"></script>
<script src="/Webmarket/view/js/search.js"></script>
</body>
</html>