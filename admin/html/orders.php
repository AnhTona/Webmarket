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
            <input type="text" id="search-input" placeholder="Tìm theo khách hàng...">
            <button id="btn-search" class="btn btn-search"><i class="fas fa-search"></i>Tìm</button>
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
        <button id="btn-toggle-advanced-filter" class="btn-toggle-advanced"><i class="fas fa-sliders-h"></i> Bộ lọc nâng cao</button>
        <div id="advanced-filters" class="advanced-filters hidden">
            <div class="filter-group">
                <label for="filter-date-from">Từ ngày:</label>
                <input type="date" id="filter-date-from">
            </div>
            <div class="filter-group">
                <label for="filter-date-to">Đến ngày:</label>
                <input type="date" id="filter-date-to">
            </div>
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
                        <button class="btn-action view-order" title="Xem chi tiết"><i class="fas fa-eye"></i> Xem</button>
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
    </div>

    <!-- Report Buttons -->
    <div class="mt-4 flex gap-4">
        <button id="btn-export-csv" class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
            <i class="fas fa-file-csv"></i> Xuất CSV
        </button>
        <button id="btn-export-excel" class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
            <i class="fas fa-file-excel"></i> Xuất Excel
        </button>
        <button id="btn-export-pdf" class="py-2 px-4 brand-bg text-white rounded-lg brand-bg-hover transition duration-200 text-sm font-medium">
            <i class="fas fa-file-pdf"></i> Xuất PDF
        </button>
    </div>

    <!-- Order Detail Modal -->
    <div id="order-detail-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 id="modal-title">CHI TIẾT ĐƠN HÀNG</h2>
            <div id="order-details" class="mt-4">
                <!-- Chi tiết đơn hàng sẽ được điền bởi JS -->
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary close-button"><i class="fas fa-times"></i> Đóng</button>
            </div>
        </div>
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