<?php
// Include Database class
require_once __DIR__ . '/../../model/database.php';

// Lấy connection từ Database singleton (nếu cần dùng)
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    // Log error nhưng không dừng trang vì contact page không nhất thiết cần DB
    error_log("Database connection warning: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên Hệ - Trà & Bánh Trung Thu</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/contact.css">
    <link rel="stylesheet" href="../css/search.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <section class="contact-banner" style="background-image: url('../../image/banner.jpg');"></section>

    <section class="contact-info-section">
        <div class="info-item">
            <i class="fas fa-map-marker-alt icon"></i>
            <h3>Địa Chỉ Cửa Hàng</h3>
            <p>129 Nguyễn Thị Minh Khai, P.Bến Thành, Q.1, TP. Hồ Chí Minh</p>
            <p>11-13-15 Nguyễn Trãi, P.Nguyễn Cư Trinh, Q.1, TP. Hồ Chí Minh</p>
        </div>
        <div class="info-item">
            <i class="fas fa-envelope icon"></i>
            <h3>Email</h3>
            <p>contact@huongtra.com</p>
            <p>support@huongtra.com</p>
        </div>
        <div class="info-item">
            <i class="fas fa-phone-alt icon"></i>
            <h3>Số Điện Thoại</h3>
            <p>Đặt hàng: (028) 3838 8888</p>
            <p>CSKH: 0901 234 567</p>
        </div>
        <div class="info-item">
            <i class="fas fa-clock icon"></i>
            <h3>Thời Gian Làm Việc</h3>
            <p>Thứ 2 - Thứ 6: 8:00 - 20:00</p>
            <p>Thứ 7 & Chủ Nhật: 9:00 - 18:00</p>
        </div>
    </section>

    <section class="google-map-section">
        <h2>Vị Trí Cửa Hàng</h2>
        <div class="map-container">
            <iframe id="google-map-iframe"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.516584281781!2d106.69701467500336!3d10.771960289381734!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f20d0f4d355%3A0x86b02a9b4d8d1e25!2zMTI5IE5ndXnhu4VuIFRo4buLIE1pbmggS2hhaSwgUGjGsOG7nW5nIELhur9uIFRow6BuaCwgUXXhuq1uIDEsIFRow6BuaCBwaOG7kSBI4buTIEPDrSBNaW5oLCBWaeG7h3QgTmFt!5e0!3m2!1svi!2s!4v1700000000000!5n0"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Bản đồ địa chỉ cửa hàng Hương Trà">
            </iframe>
        </div>
    </section>

    <section class="contact-form-section">
        <div class="form-wrapper">
            <h2>Gửi Tin Nhắn Cho Chúng Tôi</h2>
            <form action="submit_contact.php" method="POST" class="contact-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Họ và Tên (*)</label>
                        <input type="text" id="name" name="name" required placeholder="Nhập họ tên của bạn">
                    </div>
                    <div class="form-group">
                        <label for="email">Email (*)</label>
                        <input type="email" id="email" name="email" required placeholder="Nhập địa chỉ email">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Số Điện Thoại</label>
                        <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại">
                    </div>
                    <div class="form-group">
                        <label for="subject">Chủ Đề (*)</label>
                        <input type="text" id="subject" name="subject" required placeholder="Ví dụ: Đặt hàng/Hợp tác">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="message">Nội Dung (*)</label>
                    <textarea id="message" name="message" rows="6" required placeholder="Nhập nội dung tin nhắn của bạn"></textarea>
                </div>

                <div class="form-group full-width submit-group">
                    <button type="submit" class="btn-submit">
                        Gửi Tin Nhắn <span class="icon-arrow"><i class="fas fa-arrow-right"></i></span>
                    </button>
                    <p class="required-note">Các trường có dấu (*) là bắt buộc.</p>
                </div>
            </form>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>

<script src="../js/cart.js"></script>
<script src="../js/contact.js"></script>
<script src="../js/search.js"></script>
</body>
</html>