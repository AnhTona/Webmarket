<?php
require_once __DIR__ . '/../../model/database.php';
session_start();

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng - Trà & Bánh Trung Thu</title>
    <!-- ✅ THÊM /Webmarket/ -->
    <link rel="stylesheet" href="/Webmarket/view/css/home.css">
    <link rel="stylesheet" href="/Webmarket/view/css/cart.css">
    <link rel="stylesheet" href="/Webmarket/view/css/search.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>

<main class="cart-container">
    <div class="empty-cart" id="empty-cart" style="display: none;">
        <h1>Giỏ hàng đang trống, quay lại mua hàng?</h1>
        <a href="/Webmarket/products" class="btn-back">Quay trở lại mua hàng</a>
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
                    <tbody id="cart-items"></tbody>
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
                    <a href="/Webmarket/checkout" class="btn-checkout">Tiến hành thanh toán</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>

<script src="/Webmarket/view/js/cart.js"></script>
<script src="/Webmarket/view/js/search.js"></script>
</body>
</html>