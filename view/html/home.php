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
    <link rel="stylesheet" href="footer.css"> <!-- Thêm liên kết đến tệp CSS mới -->
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
               <button type="submit" class="search-submit-btn">
            <i class="fa-solid fa-magnifying-glass"></i>
            </button>

            </form>
            <div class="autocomplete-results" id="autocomplete-results"></div>
        </div>
        <div class="icons">
            <a href="cart.php" class="cart-icon">🛒 <span class="cart-count"></span></a>
            <a href="login.php">👤</a>
        </div>
    </header>
    <section class="hero">
        <div class="slider">
            <div class="slides">
                <div class="slide active" style="background-image: url('image/hero2.jpg');"></div>
                <div class="slide" style="background-image: url('image/hero1.jpg');"></div>
                <div class="slide" style="background-image: url('image/hero3.jpg');"></div>
            </div>
            <button class="prev">&#10094;</button>
            <button class="next">&#10095;</button>
        </div>
    </section>

    <section class="featured">
        <h2>Sản Phẩm Nổi Bật</h2>
        <div class="product-grid">
            <div class="product-card">
                <img src="image/sp1.jpg" alt="Trà Đen Hoàng Gia">
                <h3>Trà Đen Hoàng Gia </h3>
                <p class="price">350,000 VNĐ</p>
                <a href="#" class="btn-add" data-id="1">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
            </div>

            <div class="product-card">
                <img src="image/sp2.jpg" alt="Bánh Trung Thu Trứng Muối">
                <h3>Bánh Trung Thu Trứng Muối</h3>
                <p class="price">590,000 VNĐ</p>
                <a href="#" class="btn-add" data-id="2">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
            </div>

            <div class="product-card">
                <img src="image/sp3.jpg" alt="Trà Thảo Mộc">
                <h3>Trà Thảo Mộc</h3>
                <p class="price">270,000 VNĐ</p>
                <a href="#" class="btn-add" data-id="3">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
            </div>

            <div class="product-card">
                <img src="image/sp4.jpg" alt="Bánh Trung Thu Trà Xanh">
                <h3>Bánh Trung Thu Trà Xanh</h3>
                <p class="price">185,000 VNĐ</p>
                <a href="#" class="btn-add" data-id="4">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
            </div>

            <div class="product-card">
                <img src="image/sp5.jpg" alt="Lục Trà Lài">
                <h3>Lục Trà Lài Thượng Hạng</h3>
                <p class="price">250,000 VNĐ</p>
                <a href="#" class="btn-add" data-id="5">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
            </div>
        </div>
        <a href="products.php" class="btn-more">Xem thêm →</a>
    </section>

    <section class="why-choose">
        <div class="content-wrapper">
            <div id="box1" class="text-box active" style="background-image: url('image/bg5.jpg');">
                <div class="overlay"></div>
                <div class="text">
                    <h3 class="box-title">Nguồn nguyên liệu chất lượng</h3>
                    <p>Từng lá trà, từng hạt nguyên liệu đều được tuyển chọn từ những vùng đất trứ danh, giữ trọn tinh hoa thiên nhiên trong từng hương vị.</p>
                </div>
            </div>
            <div id="box2" class="text-box" style="background-image: url('image/bg1.jpg');">
                <div class="overlay"></div>
                <div class="text">
                    <h3 class="box-title">Nhân sự chuyên nghiệp</h3>
                    <p>Đội ngũ nghệ nhân và nhân viên tận tâm, giàu kinh nghiệm, không chỉ tạo ra sản phẩm, mà còn gửi gắm niềm đam mê và tình yêu vào từng chi tiết.</p>
                </div>
            </div>
            <div id="box3" class="text-box" style="background-image: url('image/bg3.jpg');">
                <div class="overlay"></div>
                <div class="text">
                    <h3 class="box-title">Dây chuyền hiện đại</h3>
                    <p>Ứng dụng công nghệ tiên tiến kết hợp bí quyết truyền thống, mỗi sản phẩm đều đạt chuẩn an toàn, đồng đều và giữ nguyên hương vị thuần khiết.</p>
                </div>
            </div>
            <div id="box4" class="text-box" style="background-image: url('image/bg4.jpg');">
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
                    <div class="product-card promo-card">
                        <div class="discount-badge">-25%</div>
                        <img src="image/sp13.jpg" alt="Trà Oolong Đặc Biệt">
                        <h3>Combo Trà Bánh Đặc Biệt</h3>
                        <div class="product-info">
                            <p class="old-price">1,200,000 VNĐ</p>
                            <p class="price promo-price">840,000 VNĐ</p>
                            <a href="#" class="btn-add" data-id="6">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
                        </div>
                    </div>

                    <div class="product-card promo-card">
                        <div class="discount-badge">-30%</div>
                        <img src="image/sp6.jpg" alt="Combo Trà Bánh Đặc Biệt">
                        <h3>Trà Sen Tây Hồ</h3>
                        <div class="product-info">
                            <p class="old-price">320,000 VNĐ</p>
                            <p class="price promo-price">240,000 VNĐ</p>
                            <a href="#" class="btn-add" data-id="7">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
                        </div>
                    </div>

                    <div class="product-card promo-card">
                        <div class="discount-badge">-15%</div>
                        <img src="image/sp7.jpg" alt="Bánh Thập Cẩm Cao Cấp">
                        <h3>Bánh Thập Cẩm Cao Cấp</h3>
                        <div class="product-info">
                            <p class="old-price">650,000 VNĐ</p>
                            <p class="price promo-price">552,500 VNĐ</p>
                            <a href="#" class="btn-add" data-id="8">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
                        </div>
                    </div>
                    
                    <div class="product-card promo-card">
                        <div class="discount-badge">-30%</div>
                        <img src="image/sp8.jpg" alt="Trà Xanh Thái Nguyên">
                        <h3>Trà Xanh Thái Nguyên</h3>
                        <div class="product-info">
                            <p class="old-price">300,000 VNĐ</p>
                            <p class="price promo-price">210,000 VNĐ</p>
                            <a href="#" class="btn-add" data-id="9">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
                        </div>
                    </div>
                    
                    <div class="product-card promo-card">
                        <div class="discount-badge">-20%</div>
                        <img src="image/sp9.jpg" alt="Bánh Dẻo Truyền Thống">
                        <h3>Bánh Dẻo Truyền Thống</h3>
                        <div class="product-info">
                            <p class="old-price">150,000 VNĐ</p>
                            <p class="price promo-price">120,000 VNĐ</p>
                            <a href="#" class="btn-add" data-id="10">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
                        </div>
                    </div>

                    <div class="product-card promo-card">
                        <div class="discount-badge">-10%</div>
                        <img src="image/sp10.jpg" alt="Trà Sen Tây Hồ">
                        <h3>Trà Đen Đặc Biệt</h3>
                        <div class="product-info">
                            <p class="old-price">400,000 VNĐ</p>
                            <p class="price promo-price">360,000 VNĐ</p>
                            <a href="#" class="btn-add" data-id="11">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
                        </div>
                    </div>

                    <div class="product-card promo-card">
                        <div class="discount-badge">-25%</div>
                        <img src="image/sp11.jpg" alt="Bánh Trung Thu Lá Dứa">
                        <h3>Bánh Trung Thu Lá Dứa</h3>
                        <div class="product-info">
                            <p class="old-price">280,000 VNĐ</p>
                            <p class="price promo-price">210,000 VNĐ</p>
                            <a href="#" class="btn-add" data-id="12">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
                        </div>
                    </div>

                    <div class="product-card promo-card">
                        <div class="discount-badge">-15%</div>
                        <img src="image/sp12.jpg" alt="Hồng Trà Cổ Thụ">
                        <h3>Hồng Trà Cổ Thụ</h3>
                        <div class="product-info">
                            <p class="old-price">450,000 VNĐ</p>
                            <p class="price promo-price">382,500 VNĐ</p>
                            <a href="#" class="btn-add" data-id="13">Thêm vào giỏ hàng <i class="fa-solid fa-basket-shopping"></i></a>
                        </div>
                    </div>
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
                    <img src="image/pt1.jpg" alt="Chọn Trà">
                    <span class="step-number">01</span>
                </div>
                <div class="card-content">
                    <h3>Chọn Trà</h3>
                    <p>Đến với chúng tôi, bạn sẽ tìm được gu uống trà của mình. Với các dòng trà ngon khắp vùng miền Việt Nam như Bạch Trà, Lục Trà, Oolong Trà, Hồng Trà, Phổ Nhĩ. Mỗi loại trà mang một hương vị khác biệt khiến cho cảm xúc thực sự thăng hoa…</p>
                </div>
            </a>

            <a href="blog.php?article=cach-pha-tra" class="inspiration-card">
                <div class="card-image-wrapper">
                    <img src="image/pt2.jpg" alt="Pha Trà">
                    <span class="step-number">02</span>
                </div>
                <div class="card-content">
                    <h3>Pha Trà</h3>
                    <p>Chúng tôi sẽ hướng dẫn bạn pha trà theo phong cách trà đạo. Bạn sẽ tìm ra được cách pha trà hợp ý mình để có thể pha trà ở nhà hoặc bất cứ đâu. Thông qua các lễ thức trà được quy định từ đời xưa của các vị tiền bối, mọi người uống trà trong sự an nhiên tâm hồn.</p>
                </div>
            </a>

            <a href="blog.php?article=thuong-tra" class="inspiration-card">
                <div class="card-image-wrapper">
                    <img src="image/pt3.jpg" alt="Thưởng Trà">
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

    <script>
        // ===== WHY CHOOSE SLIDER SCRIPT =====
        const numberSpans = document.querySelectorAll('.why-numbers span');
        const contentBoxes = document.querySelectorAll('.text-box');

        numberSpans.forEach(span => {
            span.addEventListener('mouseover', () => {
                numberSpans.forEach(s => s.classList.remove('active'));
                contentBoxes.forEach(box => box.classList.remove('active'));

                span.classList.add('active');
                const targetId = span.getAttribute('data-target');
                document.getElementById(targetId).classList.add('active');
            });
        });

        // ===== HERO SLIDER SCRIPT =====
        const slides = document.querySelectorAll('.slide');
        const prev = document.querySelector('.prev');
        const next = document.querySelector('.next');
        let index = 0;

        function showSlide(i) {
            slides.forEach((slide, idx) => {
                slide.classList.remove('active');
                if (idx === i) slide.classList.add('active');
            });
        }

        prev.addEventListener('click', () => {
            index = (index - 1 + slides.length) % slides.length;
            showSlide(index);
        });

        next.addEventListener('click', () => {
            index = (index + 1) % slides.length;
            showSlide(index);
        });

        // Tự động chuyển slide mỗi 5 giây
        setInterval(() => {
            index = (index + 1) % slides.length;
            showSlide(index);
        }, 5000);

        // ===== PROMO CAROUSEL SCRIPT =====
        const sliderWrapper = document.querySelector('.product-slider-wrapper');
        const prevBtnPromo = document.querySelector('.prev-promo');
        const nextBtnPromo = document.querySelector('.next-promo');
        // Cuộn 3 card (280px width + 30px gap)
        const scrollDistance = (280 + 30) * 3;

        nextBtnPromo.addEventListener('click', () => {
            sliderWrapper.scrollBy({
                left: scrollDistance,
                behavior: 'smooth'
            });
        });

        prevBtnPromo.addEventListener('click', () => {
            sliderWrapper.scrollBy({
                left: -scrollDistance,
                behavior: 'smooth'
            });
        });

        // Ẩn/hiện nút nếu cuộn đến đầu hoặc cuối
        sliderWrapper.addEventListener('scroll', () => {
            if (sliderWrapper.scrollLeft <= 5) {
                prevBtnPromo.style.opacity = '0.5';
                prevBtnPromo.style.pointerEvents = 'none';
            } else {
                prevBtnPromo.style.opacity = '1';
                prevBtnPromo.style.pointerEvents = 'auto';
            }

            if (sliderWrapper.scrollLeft + sliderWrapper.clientWidth >= sliderWrapper.scrollWidth - 5) {
                nextBtnPromo.style.opacity = '0.5';
                nextBtnPromo.style.pointerEvents = 'none';
            } else {
                nextBtnPromo.style.opacity = '1';
                nextBtnPromo.style.pointerEvents = 'auto';
            }
        });

        prevBtnPromo.style.opacity = '0.5';
        prevBtnPromo.style.pointerEvents = 'none';

        // ===== CẬP NHẬT SỐ LƯỢNG GIỎ HÀNG =====
        document.addEventListener('DOMContentLoaded', function() {
            // Gọi hàm updateCartBadge khi tải trang
            if (typeof updateCartBadge === 'function') {
                updateCartBadge();
            }

            // Lắng nghe sự kiện từ các nút "Thêm vào giỏ hàng"
            document.querySelectorAll('.btn-add').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Ngăn chuyển hướng ngay lập tức

                    const productCard = this.closest('.product-card');
                    const productName = productCard.querySelector('h3').textContent;
                    const productPrice = parseFloat(
                        (productCard.querySelector('.price') || productCard.querySelector('.promo-price')).textContent
                            .replace(' VNĐ', '')
                            .replace(/\./g, '')
                    );
                    const productImage = productCard.querySelector('img').getAttribute('src');
                    const productId = this.getAttribute('data-id'); // Lấy data-id từ nút

                    // Tạo dữ liệu sản phẩm
                    const itemData = {
                        id: productId,
                        name: productName,
                        price: productPrice,
                        image: productImage,
                        quantity: 1
                    };

                    // Gửi dữ liệu qua postMessage để cart.js xử lý
                    window.postMessage(itemData, window.location.origin);

                    // Cập nhật số lượng giỏ hàng
                    if (typeof updateCartBadge === 'function') {
                        updateCartBadge();
                    }
                });
            });
        });
    </script>
</body>
</html>
   <script src="../js/search.js"></script> <!-- Thêm file search.js -->