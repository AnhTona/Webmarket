<?php
// view/html/order_success.php
session_start();
require_once __DIR__ . '/../../controller/OrderSuccessController.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$controller = new OrderSuccessController();
$data = $controller->getOrderDetails($orderId);

if (!$data) {
    header('Location: /Webmarket/home');
    exit;
}

$order = $data['order'];
$items = $data['items'];
$calc = $data['calculations'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng thành công</title>
    <!-- ✅ THÊM /Webmarket/ -->
    <link rel="stylesheet" href="/Webmarket/view/css/order_success.css">
</head>
<body>
<div class="success-container">
    <div class="success-icon">✓</div>
    <h1>Đặt hàng thành công!</h1>
    <p class="success-message">Cảm ơn bạn đã đặt hàng tại Hương Trà</p>

    <div class="order-info">
        <h2>Thông tin đơn hàng</h2>
        <div class="info-row">
            <span class="label">Mã đơn hàng:</span>
            <span class="value">#<?= $orderId ?></span>
        </div>
        <div class="info-row">
            <span class="label">Khách hàng:</span>
            <span class="value"><?= htmlspecialchars($order['HoTen']) ?></span>
        </div>
        <div class="info-row">
            <span class="label">Email:</span>
            <span class="value"><?= htmlspecialchars($order['Email']) ?></span>
        </div>
        <div class="info-row">
            <span class="label">Hạng thành viên:</span>
            <span class="value <?= $controller->getRankClass($order['Hang']) ?>"><?= $order['Hang'] ?></span>
        </div>
        <div class="info-row">
            <span class="label">Số bàn:</span>
            <span class="value">Bàn #<?= $order['MaBan'] ?></span>
        </div>
        <div class="info-row">
            <span class="label">Ngày đặt:</span>
            <span class="value"><?= $controller->formatDateTime($order['NgayDat']) ?></span>
        </div>
    </div>

    <div class="order-items">
        <h2>Chi tiết đơn hàng</h2>
        <table>
            <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Đơn giá</th>
                <th>SL</th>
                <th>Thành tiền</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['TenSanPham']) ?></td>
                    <td><?= $controller->formatMoney($item['DonGia']) ?></td>
                    <td><?= $item['SoLuong'] ?></td>
                    <td><?= $controller->formatMoney($item['DonGia'] * $item['SoLuong']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="payment-info">
        <h2>Chi tiết thanh toán</h2>

        <div class="info-row">
            <span class="label">Tạm tính:</span>
            <span class="value"><?= $controller->formatMoney($calc['subtotal']) ?></span>
        </div>

        <?php if ($calc['discount_amount'] > 0): ?>
            <div class="info-row discount-row">
                <span class="label">Giảm giá (Hạng <?= $order['Hang'] ?> - <?= ($calc['discount_rate'] * 100) ?>%):</span>
                <span class="value discount-value">- <?= $controller->formatMoney($calc['discount_amount']) ?></span>
            </div>
        <?php endif; ?>

        <div class="info-row">
            <span class="label">VAT (8%):</span>
            <span class="value"><?= $controller->formatMoney($calc['vat']) ?></span>
        </div>

        <div class="info-row separator"></div>

        <div class="info-row">
            <span class="label">Phương thức:</span>
            <span class="value"><?= $controller->getPaymentMethodName($order['PhuongThuc']) ?></span>
        </div>

        <div class="info-row total">
            <span class="label">TỔNG THANH TOÁN:</span>
            <span class="value"><?= $controller->formatMoney($calc['grand_total']) ?></span>
        </div>
    </div>

    <div class="action-buttons">
        <a href="/Webmarket/home" class="btn btn-primary">Về trang chủ</a>
        <button onclick="window.print()" class="btn btn-secondary">In hóa đơn</button>
    </div>
</div>

<script>
    // Xóa giỏ hàng sau khi đặt thành công
    localStorage.removeItem('cart');
</script>
</body>
</html>