<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản Phẩm - Trà & Bánh Trung Thu</title>
    <link rel="stylesheet" href="../css/products.css">
    <link rel="stylesheet" href="../css/home.css">
         <link rel="stylesheet" href="../css/search.css"> <!-- Thêm file search.css -->
                  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> <!-- icon tìm kiếm-->

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7ISGqrIDrxlwX+uYwg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
  <header class="header">
        <div class="logo">
            <img src="image/logo.png" alt="Logo Trà & Bánh Trung Thu">
        </div>
        <nav>
            <ul>
                <li><a href="home.php">Trang Chủ</a></li>
                <li><a href="products.php" class="active">Sản Phẩm</a></li>
                <li><a href="promo.php">Khuyến Mãi</a></li>
                <li><a href="contact.php">Liên Hệ</a></li>
            </ul>
        </nav>
        <div class="search-container">
            <form class="search-form" action="search_results.php" method="GET">
                <input type="text" id="search-input" name="keyword" placeholder="Tìm kiếm sản phẩm...">
               <button type="submit" class="search-submit-btn"> <i class="fa-solid fa-magnifying-glass"></i> </button>

            </form>
            <div class="autocomplete-results" id="autocomplete-results"></div>
        </div>
        <div class="icons">
            <a href="cart.php" class="cart-icon">🛒 <span class="cart-count"></span></a>
            <a href="login.php">👤</a>
        </div>
    </header>
    <main class="products-container">
        <div class="breadcrumb">
            <a href="home.php">Trang Chủ</a> / <a href="products.php">Sản Phẩm</a><span id="breadcrumb-category"></span>
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

<script src="../js/products.js"></script>
<!-- Overlay (dùng để dim nền & đóng minicart khi click) -->
<div id="overlay" aria-hidden="true"></div>

<!-- Minicart -->
<div id="mini-cart" aria-hidden="true" role="dialog" aria-label="Giỏ hàng mini">
  <div class="minicart-header">
    <span class="title">Giỏ hàng</span>
    <button type="button" class="close-btn" aria-label="Đóng mini cart">&times;</button>
  </div>

  <div id="minicart-items-list" class="minicart-items" aria-live="polite">
    <!-- items sẽ được JS chèn vào đây -->
  </div>

  <div class="minicart-footer">
    <div class="minicart-total-row">
      <span id="minicart-item-count">0 sản phẩm</span>
      <span id="minicart-total-price">0 VND</span>
    </div>
    <a href="cart.php" class="btn-view-cart">Xem giỏ hàng</a>
  </div>
</div>
</body>
</html> 
     <script src="../js/search.js"></script> <!-- Thêm file search.js -->