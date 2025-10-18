<?php
include __DIR__ . '/../../model/db.php';
session_start();
$cart_items = [];
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Trà & Bánh Trung Thu</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/cart.css">
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
    <main class="cart-container">
        <div class="empty-cart" id="empty-cart" style="display: none;">
            <h1>Giỏ hàng đang trống, quay lại mua hàng?</h1>
            <a href="products.php" class="btn-back">Quay trở lại mua hàng</a>
        </div>
        <div class="cart-content" id="cart-content" style="display: none;">
            <h1>Giỏ Hàng</h1>
            <div class="flex flex-wrap lg:flex-nowrap">
                <div class="w-full lg:w-3/4 pr-4">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Sản phẩm</th>
                                <th style="width: 15%;">Giá</th>
                                <th style="width: 15%;">Số lượng</th>
                                <th style="width: 20%;">Ghi chú</th>
                                <th style="width: 10%;">Tạm tính</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-items">
                        </tbody>
                    </table>
                </div>
                <div class="w-full lg:w-1/4">
                    <div class="cart-summary" id="cart-summary">
                        <h2>Tóm tắt đơn hàng</h2>
                        <div class="summary-row">
                            <span>Tạm tính</span>
                            <span id="subtotal">0 VNĐ</span>
                        </div>
                        <div class="summary-row">
                            <span>VAT (8%)</span>
                            <span id="vat">0 VNĐ</span>
                        </div>
                        <div class="summary-row total">
                            <span>Tổng cộng</span>
                            <span id="grand-total">0 VNĐ</span>
                        </div>
                        <a href="checkout.php" class="btn-checkout">Tiến hành thanh toán</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="../js/cart.js"></script>
    <script src="../js/search.js"></script> <!-- Thêm file search.js -->
</body>
</html>