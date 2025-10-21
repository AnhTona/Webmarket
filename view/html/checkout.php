<?php
// checkout.php
include __DIR__ . '/../../model/db.php';
session_start();
// Lưu ý: Dữ liệu giỏ hàng sẽ được load và tính toán bằng JavaScript
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Đơn Hàng - Hương Trà</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/checkout.css"> <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="checkout-container">
        <div class="breadcrumb">
            <a href="home.php">Trang Chủ</a> / <a href="cart.php">Giỏ Hàng</a> / <span>Thanh Toán</span>
        </div>
        
        <h1>HOÀN TẤT ĐƠN HÀNG</h1>

        <form id="checkout-form" action="../../controller/process_order.php" method="POST" enctype="multipart/form-data">
            <div class="checkout-grid">
                
                <section class="checkout-details-column">
                    <h2>1. Thông tin Khách hàng & Bàn</h2>
                    
                    <div class="customer-info-group">
                        <label for="phone_number">Số Điện Thoại Thành Viên (*)</label>
                        <input type="tel" id="phone_number" name="phone_number" 
                            placeholder="Nhập SĐT để nhận ưu đãi thành viên" required 
                            maxlength="10">
                        <div id="membership-status" class="membership-status rank-default">
                            Vui lòng nhập SĐT để kiểm tra hạng.
                        </div>
                    </div>
                    
                    <div class="table-info-group">
                        <label for="table_number">Số Bàn / Mã Order (*)</label>
                        <input type="text" id="table_number" name="table_number" placeholder="Ví dụ: A10, Bàn 3, Order #001" required>
                        <small class="hint">Thông tin bắt buộc để phục vụ tại bàn.</small>
                    </div>

                    <div class="customer-info-group">
                        <label for="customer_name">Tên khách hàng (Tùy chọn)</label>
                        <input type="text" id="customer_name" name="customer_name" placeholder="Ví dụ: Anh/Chị Lan (Tên sẽ tự điền nếu là thành viên)">
                    </div>

                    <div class="customer-info-group">
                         <label for="customer_note">Ghi chú thêm</label>
                         <textarea id="customer_note" name="customer_note" rows="3" placeholder="Ví dụ: Bánh dẻo ít ngọt, mang Trà ra trước."></textarea>
                    </div>

                    <h2>2. Kiểm tra Sản phẩm</h2>
                    <div class="order-items-review" id="order-items-review">
                        <p class="empty-message">Không có sản phẩm nào trong giỏ hàng.</p>
                    </div>
                </section>
                
                <aside class="checkout-summary-column">
                    <h2>3. Phương thức Thanh toán</h2>
                    
                    <div class="payment-methods">
                        <label class="payment-option selected">
                            <input type="radio" name="payment_method" value="cash" checked>
                            <i class="fas fa-money-bill-wave"></i> Thanh toán tại quầy
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="transfer" id="radio-transfer">
                            <i class="fas fa-qrcode"></i> Chuyển khoản Thủ công
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="momo">
                            <i class="fas fa-wallet"></i> Ví điện tử (Momo/ZaloPay)
                        </label>
                    </div>

                    <div id="transfer-details" class="transfer-details-box" style="display:none;">
                        <h4>Thông tin Ngân hàng</h4>
                        <div class="bank-info">
                            <p><strong>Ngân hàng:</strong> Vietcombank</p>
                            <p><strong>Số tài khoản:</strong> 0071000888888</p>
                            <p><strong>Chủ tài khoản:</strong> CÔNG TY CP HƯƠNG TRÀ</p>
                            <p class="transfer-note"><strong>Nội dung:</strong> TTDH-[SỐ ĐƠN HÀNG]-SĐT bạn</p>
                            <img src="../../image/qr_code_vcb.png" alt="Mã QR Vietcombank" class="qr-code">
                        </div>
                        
                        <div class="form-group upload-receipt-group">
                            <label for="receipt_file"><i class="fas fa-upload"></i> Upload Biên lai (Tùy chọn)</label>
                            <input type="file" id="receipt_file" name="receipt_file" accept="../../image/*,application/pdf">
                            <small class="hint">Giúp shop xác nhận thanh toán nhanh hơn.</small>
                        </div>
                    </div>
                    
                    <div class="payment-summary">
                        <div class="summary-line">
                            <span>Tạm tính (<span id="summary-item-count">0</span> món)</span>
                            <span id="summary-subtotal">0 VNĐ</span>
                        </div>
                        <div class="summary-line discount-line" style="display:none;">
                            <span>Giảm giá (Hạng <span id="rank-display-summary">Mới</span>)</span>
                            <span id="summary-discount">0 VNĐ</span>
                        </div>
                        <div class="summary-line">
                            <span>VAT (8%)</span>
                            <span id="summary-vat">0 VNĐ</span>
                        </div>
                        <div class="summary-line total-line">
                            <strong>TỔNG THANH TOÁN</strong>
                            <strong id="summary-grand-total">0 VNĐ</strong>
                        </div>
                    </div>

                    <p class="final-note">Bằng việc bấm nút, bạn xác nhận đặt món. Đơn hàng sẽ được ghi nhận và xử lý.</p>
                    
                    <button type="submit" class="btn-submit-order" disabled id="btn-submit-order">
                         Gửi Order & Thanh Toán <i class="fas fa-check-circle"></i>
                    </button>
                    
                    <a href="cart.php" class="btn-back-to-cart">
                        <i class="fas fa-angle-left"></i> Quay lại giỏ hàng
                    </a>
                </aside>

            </div>
        </form>
    </main>

    <?php include 'footer.php'; ?>
    <script src="../js/cart.js"></script> 
    <script src="../js/checkout.js"></script> </body>
</html>