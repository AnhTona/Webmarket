<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - Trà & Bánh Trung Thu</title>
    <link rel="stylesheet" href="/Webmarket/view/css/home.css">
    <link rel="stylesheet" href="/Webmarket/view/css/search.css">
    <link rel="stylesheet" href="/Webmarket/view/css/products.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>

<section class="hero">
    <div class="slider">
        <div class="slides">
            <div class="slide active" style="background-image: url('/Webmarket/image/hero2.jpg');"></div>
            <div class="slide" style="background-image: url('/Webmarket/image/hero1.jpg');"></div>
            <div class="slide" style="background-image: url('/Webmarket/image/hero3.jpg');"></div>
        </div>
        <button class="prev">&#10094;</button>
        <button class="next">&#10095;</button>
    </div>
</section>

<section class="featured">
    <h2>Sản Phẩm Nổi Bật</h2>
    <div class="product-grid"></div>
    <a href="/Webmarket/products" class="btn-more">Xem thêm →</a>
</section>

<section class="why-choose">
    <div class="content-wrapper">
        <div id="box1" class="text-box active" style="background-image: url('/Webmarket/image/bg5.webp');"><div class="overlay"></div><div class="text"><h3 class="box-title">Nguồn nguyên liệu chất lượng</h3><p>Từng lá trà, từng hạt nguyên liệu đều được tuyển chọn từ những vùng đất trứ danh, giữ trọn tinh hoa thiên nhiên trong từng hương vị.</p></div></div>
        <div id="box2" class="text-box" style="background-image: url('/Webmarket/image/bg1.webp');"><div class="overlay"></div><div class="text"><h3 class="box-title">Nhân sự chuyên nghiệp</h3><p>Đội ngũ nghệ nhân và nhân viên tận tâm, giàu kinh nghiệm, không chỉ tạo ra sản phẩm, mà còn gửi gắm niềm đam mê và tình yêu vào từng chi tiết.</p></div></div>
        <div id="box3" class="text-box" style="background-image: url('/Webmarket/image/bg3.webp');"><div class="overlay"></div><div class="text"><h3 class="box-title">Dây chuyền hiện đại</h3><p>Ứng dụng công nghệ tiên tiến kết hợp bí quyết truyền thống, mỗi sản phẩm đều đạt chuẩn an toàn, đồng đều và giữ nguyên hương vị thuần khiết.</p></div></div>
        <div id="box4" class="text-box" style="background-image: url('/Webmarket/image/bg4.webp');"><div class="overlay"></div><div class="text"><h3 class="box-title">Dịch vụ tận tâm</h3><p>Chúng tôi đồng hành cùng bạn từ giây phút chọn lựa đến khoảnh khắc thưởng thức, mang lại trải nghiệm mua sắm ấm áp và trọn vẹn.</p></div></div>
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
            <div class="product-slider promo-grid"></div>
        </div>
        <button class="carousel-btn next-promo"><i class="fas fa-chevron-right"></i></button>
    </div>
    <a href="/Webmarket/products?category-link=Khuyến Mãi" class="btn-more">Tất cả khuyến mãi →</a>
</section>

<section class="inspiration-section">
    <h2 class="inspiration-title">Thưởng Trà Theo Cách Của Riêng Bạn</h2>
    <div class="inspiration-grid">
        <a href="/Webmarket/blog?article=chon-tra" class="inspiration-card">
            <div class="card-image-wrapper"><img src="/Webmarket/image/pt1.webp" alt="Chọn Trà"><span class="step-number">01</span></div>
            <div class="card-content"><h3>Chọn Trà</h3><p>Đến với chúng tôi, bạn sẽ tìm được gu uống trà của mình...</p></div>
        </a>
        <a href="/Webmarket/blog?article=cach-pha-tra" class="inspiration-card">
            <div class="card-image-wrapper"><img src="/Webmarket/image/pt2.webp" alt="Pha Trà"><span class="step-number">02</span></div>
            <div class="card-content"><h3>Pha Trà</h3><p>Chúng tôi sẽ hướng dẫn bạn pha trà theo phong cách trà đạo...</p></div>
        </a>
        <a href="/Webmarket/blog?article=thuong-tra" class="inspiration-card">
            <div class="card-image-wrapper"><img src="/Webmarket/image/pt3.webp" alt="Thưởng Trà"><span class="step-number">03</span></div>
            <div class="card-content"><h3>Thưởng Trà</h3><p>Trà ngon, rượu ngọt, bạn hiền...</p></div>
        </a>
    </div>
</section>

<?php include 'footer.php'; ?>

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

<script src="/Webmarket/view/js/home.js"></script>
<script src="/Webmarket/view/js/search.js"></script>
</body>
</html>