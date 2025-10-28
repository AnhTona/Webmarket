<?php
require_once __DIR__ . '/../controller/Orders_Controller.php';
$ctx = OrdersController::handle();
extract($ctx, EXTR_OVERWRITE);

// Thiết lập tiêu đề trang
$page_title = 'Quản Lý Đơn Hàng';

// Bắt đầu output buffering để capture nội dung
ob_start();
?>

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Đơn Hàng</h1>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="search-group">
            <input type="text" id="search-input" placeholder="Tìm theo mã đơn hoặc khách hàng...">
            <button id="btn-search" class="btn btn-search"><i class="fas fa-search"></i></button>
        </div>
        <div class="filter-group">
            <label for="filter-date">Ngày:</label>
            <input type="date" id="filter-date" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="filter-group">
            <label for="filter-status">Trạng thái:</label>
            <select id="filter-status">
                <option value="All">Tất cả</option>
                <option value="Chờ xác nhận">Chờ xác nhận</option>
                <option value="Đang chuẩn bị">Đang chuẩn bị</option>
                <option value="Hoàn thành">Hoàn thành</option>
            </select>
        </div>
    </div>

    <!-- Order Table -->
    <div class="order-table-container mt-6">
        <table id="order-list-table" class="order-table w-full">
            <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Bàn</th>
                <th>Ngày đặt</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($order_list as $order):
                $status = trim($order['TrangThai']);
                ?>
                <tr data-id="<?php echo $order['MaDon']; ?>">
                    <td><?php echo htmlspecialchars($order['MaDon']); ?></td>
                    <td><?php echo htmlspecialchars($order['KhachHang']); ?></td>
                    <td><?php echo htmlspecialchars($order['Ban']); ?></td>
                    <td><?php echo htmlspecialchars($order['NgayDat']); ?></td>
                    <td><?php echo number_format($order['TongTien'], 0, ',', '.') . 'đ'; ?></td>
                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>"><?php echo htmlspecialchars($status); ?></span></td>
                    <td>
                        <button class="btn-action view-order" title="Xem chi tiết"><i class="fas fa-eye"></i data-id="<?= (int)$row['MaDon'] ?>"> Xem</button>
                        <?php if ($status === 'Chờ xác nhận'): ?>
                            <button class="btn-action confirm-order" title="Xác nhận"><i class="fas fa-check"></i> Xác nhận</button>
                            <button class="btn-action cancel-order" title="Hủy"><i class="fas fa-times"></i> Hủy</button>
                        <?php elseif ($status === 'Đang chuẩn bị'): ?>
                            <button class="btn-action complete-order" title="Hoàn thành"><i class="fas fa-check-double"></i> Hoàn thành</button>
                            <button class="btn-action cancel-order" title="Hủy"><i class="fas fa-times"></i> Hủy</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div id="no-results-message" style="display: none; text-align: center; padding: 20px;">Không tìm thấy đơn hàng nào phù hợp.</div>
        <div id="pagination" class="pagination"></div>
        <div id="page-info" class="page-info"></div>
    </div>

    <!-- CSS cho trang Orders -->
    <link rel="stylesheet" href="../css/orders.css">

    <!-- JavaScript -->
    <script>
        window.ordersData = <?php echo json_encode($order_list ?? [], JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="../js/orders.js"></script>

<?php
// Lấy nội dung đã capture
$content = ob_get_clean();

// Include template
require_once __DIR__ . '/template.php';
?>