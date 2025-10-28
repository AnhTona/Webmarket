<?php
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Tìm Kiếm - Trà & Bánh Trung Thu</title>
    <link rel="stylesheet" href="/Webmarket/view/css/products.css">
    <link rel="stylesheet" href="/Webmarket/view/css/home.css">
    <link rel="stylesheet" href="/Webmarket/view/css/search.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>

<main class="products-container">
    <div class="breadcrumb">
        <a href="/Webmarket/home">Trang Chủ</a> / <span>Kết Quả Tìm Kiếm: "<?php echo htmlspecialchars($keyword); ?>"</span>
    </div>
    <div class="content-wrapper">
        <div class="main-content">
            <div class="title-filter">
                <h1>KẾT QUẢ TÌM KIẾM</h1>
            </div>
            <div class="product-grid" id="product-grid"></div>
            <div id="no-products-message">Xin lỗi, không tìm thấy sản phẩm nào với từ khóa "<?php echo htmlspecialchars($keyword); ?>"</div>
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

<script src="/Webmarket/view/js/search_results.js"></script>
<script src="/Webmarket/view/js/search.js"></script>
</body>
</html>