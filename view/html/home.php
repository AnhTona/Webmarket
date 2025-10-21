<?php
include __DIR__ . '/../../model/db.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - Trà & Bánh Trung Thu</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/search.css">
    <link rel="stylesheet" href="../css/products.css"> <!-- Thêm để dùng mini-cart CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>
<section class="hero">
    <div class="slider">
        <div class="slides">
            <div class="slide active" style="background-image: url('../../image/hero2.jpg');"></div>
            <div class="slide" style="background-image: url('../../image/hero1.jpg');"></div>
            <div class="slide" style="background-image: url('../../image/hero3.jpg');"></div>
        </div>
        <button class="prev">&#10094;</button>
        <button class="next">&#10095;</button>
    </div>
</section>

<section class="featured">
    <h2>Sản Phẩm Nổi Bật</h2>
    <div class="product-grid">
        <!-- Sản phẩm sẽ được load tự động từ JavaScript (tối đa 7) -->
    </div>
    <a href="products.php" class="btn-more">Xem thêm →</a>
</section>

<section class="why-choose">
    <div class="content-wrapper">
        <div id="box1" class="text-box active" style="background-image: url('../../image/bg5.jpg');">
            <div class="overlay"></div>
            <div class="text">
                <h3 class="box-title">Nguồn nguyên liệu chất lượng</h3>
                <p>Từng lá trà, từng hạt nguyên liệu đều được tuyển chọn từ những vùng đất trứ danh, giữ trọn tinh hoa thiên nhiên trong từng hương vị.</p>
            </div>
        </div>
        <div id="box2" class="text-box" style="background-image: url('../../image/bg1.jpg');">
            <div class="overlay"></div>
            <div class="text">
                <h3 class="box-title">Nhân sự chuyên nghiệp</h3>
                <p>Đội ngũ nghệ nhân và nhân viên tận tâm, giàu kinh nghiệm, không chỉ tạo ra sản phẩm, mà còn gửi gắm niềm đam mê và tình yêu vào từng chi tiết.</p>
            </div>
        </div>
        <div id="box3" class="text-box" style="background-image: url('../../image/bg3.jpg');">
            <div class="overlay"></div>
            <div class="text">
                <h3 class="box-title">Dây chuyền hiện đại</h3>
                <p>Ứng dụng công nghệ tiên tiến kết hợp bí quyết truyền thống, mỗi sản phẩm đều đạt chuẩn an toàn, đồng đều và giữ nguyên hương vị thuần khiết.</p>
            </div>
        </div>
        <div id="box4" class="text-box" style="background-image: url('../../image/bg4.jpg');">
            <div class="overlay"></div>
            <div class="text">
                <h3 class="box-title">Dịch vụ tận tâm</h3>
                <p>Chúng tôi đồng hành cùng bạn từ giây phút chọn lựa đến khoảnh khắc thưởng thức, mang lại trải nghiệm mua sắm ấm áp và trọn vẹn.</p>
            </div>
        </div>
    </div>

    <div class="why-numbers">
        <span data-target="box1" class="active">01</span>
        <span data-target="box2">02</span>
        <span data-target="box3">03</span>
        <span data-target="box4">04</span>
    </div>
</section>

<section class="featured promo-section">
    <h2>Sản Phẩm Khuyến Mãi</h2>
    <div class="product-slider-container">
        <button class="carousel-btn prev-promo"><i class="fas fa-chevron-left"></i></button>

        <div class="product-slider-wrapper">
            <div class="product-slider promo-grid">
                <!-- Sản phẩm khuyến mãi sẽ được load tự động từ JavaScript (tối đa 12) -->
            </div>
        </div>
        <button class="carousel-btn next-promo"><i class="fas fa-chevron-right"></i></button>
    </div>
    <a href="products.php?category-link=Khuyến Mãi" class="btn-more">Tất cả khuyến mãi →</a>
</section>

<section class="inspiration-section">
    <h2 class="inspiration-title">Thưởng Trà Theo Cách Của Riêng Bạn</h2>
    <div class="inspiration-grid">
        <a href="blog.php?article=chon-tra" class="inspiration-card">
            <div class="card-image-wrapper">
                <img src="../../image/pt1.jpg" alt="Chọn Trà">
                <span class="step-number">01</span>
            </div>
            <div class="card-content">
                <h3>Chọn Trà</h3>
                <p>Đến với chúng tôi, bạn sẽ tìm được gu uống trà của mình. Với các dòng trà ngon khắp vùng miền Việt Nam như Bạch Trà, Lục Trà, Oolong Trà, Hồng Trà, Phổ Nhĩ. Mỗi loại trà mang một hương vị khác biệt khiến cho cảm xúc thực sự thăng hoa…</p>
            </div>
        </a>

        <a href="blog.php?article=cach-pha-tra" class="inspiration-card">
            <div class="card-image-wrapper">
                <img src="../../image/pt2.jpg" alt="Pha Trà">
                <span class="step-number">02</span>
            </div>
            <div class="card-content">
                <h3>Pha Trà</h3>
                <p>Chúng tôi sẽ hướng dẫn bạn pha trà theo phong cách trà đạo. Bạn sẽ tìm ra được cách pha trà hợp ý mình để có thể pha trà ở nhà hoặc bất cứ đâu. Thông qua các lễ thức trà được quy định từ đời xưa của các vị tiền bối, mọi người uống trà trong sự an nhiên tâm hồn.</p>
            </div>
        </a>

        <a href="blog.php?article=thuong-tra" class="inspiration-card">
            <div class="card-image-wrapper">
                <img src="../../image/pt3.jpg" alt="Thưởng Trà">
                <span class="step-number">03</span>
            </div>
            <div class="card-content">
                <h3>Thưởng Trà</h3>
                <p>Trà ngon, rượu ngọt, bạn hiền. Mỗi người tuy mỗi sở thích nhưng chỉ cần thích trà, gặp được nhau lúc uống trà, sẽ là bạn tâm giao. Hãy đến với Lá Trà Ngon Tea House để được bàn luận về các loại trà ngon, tiếp cận văn hóa uống trà cổ điển mà hiện đại…</p>
            </div>
        </a>
    </div>
</section>

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
        <a href="cart.php" class="btn-view-cart">Xem giỏ hàng</a>
    </div>
</div>

<script src="../js/home.js"></script>
<script src="../js/search.js"></script>
</body>
</html>