<?php
require_once __DIR__ . '/config.php';
requireAuth();
require_once __DIR__ . '/../controller/Customers_Controller.php';

try {
    $ctx = CustomersController::handle();
    extract($ctx, EXTR_OVERWRITE);
} catch (Exception $e) {
    handleError($e->getMessage(), 'customers.php');
}

// ✅ Set page metadata
$page_title = 'Quản Lý Khách Hàng';
$page_css = 'customers';
$page_js = 'customers';

// Bắt đầu output buffering
ob_start();
?>
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Khách Hàng</h1>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="search-group">
            <input type="text" id="search-input" placeholder="Tìm theo: Họ tên, SĐT, Email, Mã KH...">
            <button id="btn-search" class="btn btn-search"><i class="fas fa-search">Tìm</i></button>
        </div>
        <div class="filter-group">
            <label for="filter-status">Trạng thái:</label>
            <select id="filter-status">
                <option value="All">Tất cả</option>
                <option value="Hoạt động">Hoạt động</option>
                <option value="Ngừng">Ngừng</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="filter-rank">Hạng KH:</label>
            <select id="filter-rank">
                <option value="All">Tất cả</option>
                <option value="Gold">Gold</option>
                <option value="Silver">Silver</option>
                <option value="Bronze">Bronze</option>
                <option value="Mới">Mới</option>
            </select>
        </div>
        <button id="btn-toggle-advanced-filter" class="btn-toggle-advanced"><i class="fas fa-sliders-h"></i> Bộ lọc nâng cao</button>
        <div id="advanced-filters" class="advanced-filters hidden">
            <div class="filter-group">
                <label for="filter-city">Tỉnh/Thành:</label>
                <input type="text" id="filter-city" placeholder="Tỉnh/Thành...">
            </div>
            <div class="filter-group">
                <label for="filter-date-from">Ngày tạo (Từ):</label>
                <input type="date" id="filter-date-from">
            </div>
            <div class="filter-group">
                <label for="filter-date-to">Ngày tạo (Đến):</label>
                <input type="date" id="filter-date-to">
            </div>
        </div>
    </div>

    <!-- Customer Table -->
    <div class="customer-table-container mt-6">
        <table id="customer-list-table" class="customer-table w-full">
            <thead>
            <tr>
                <th>Mã KH</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>SĐT</th>
                <th>Địa chỉ</th>
                <th>Hạng</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($customer_list)): ?>
                <?php foreach ($customer_list as $customer): ?>
                    <tr data-id="<?php echo htmlspecialchars($customer['id']); ?>">
                        <td><?php echo htmlspecialchars($customer['id']); ?></td>
                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td><?php echo htmlspecialchars($customer['address']); ?></td>
                        <td class="rank-<?php echo strtolower($customer['rank']); ?>"><?php echo htmlspecialchars($customer['rank']); ?></td>
                        <td><span class="status-badge status-<?php echo ($customer['status'] == 'Hoạt động' ? 'active' : 'inactive'); ?>"><?php echo htmlspecialchars($customer['status']); ?></span></td>
                        <td><?php echo htmlspecialchars($customer['created_at']); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-start">
                                <button class="btn-action edit-customer px-3 py-2 rounded bg-yellow-500 text-white hover:bg-yellow-600"
                                        data-id="<?php echo htmlspecialchars($customer['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($customer['name']); ?>">
                                    Sửa <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action delete-customer px-3 py-2 rounded bg-red-500 text-white hover:bg-red-600"
                                        data-id="<?php echo htmlspecialchars($customer['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($customer['name']); ?>">
                                    Xóa <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center py-6 text-gray-500">Không có khách hàng nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <div id="no-results-message" style="display: none; text-align: center; padding: 20px;">Không tìm thấy khách hàng nào phù hợp.</div>
    </div>

    <!-- Customer Modal -->
    <div id="customer-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 id="modal-title">Sửa Thông Tin Khách Hàng</h2>
            <form id="customer-form">
                <input type="hidden" id="customer-id" name="id">

                <div class="form-section">
                    <h3>Thông Tin Cơ Bản</h3>
                    <div class="form-group"><label for="full-name">Họ Tên:</label><input type="text" id="full-name" name="name" required></div>
                    <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email" required></div>
                    <div class="form-group"><label for="phone">SĐT:</label><input type="tel" id="phone" name="phone" required></div>
                    <div class="form-group"><label for="dob">Ngày Sinh:</label><input type="date" id="dob" name="dob"></div>
                    <div class="form-group">
                        <label for="gender">Giới Tính:</label>
                        <select id="gender" name="gender">
                            <option value="">Chọn</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Địa Chỉ & Trạng Thái</h3>
                    <div class="form-group"><label for="address">Địa Chỉ:</label><input type="text" id="address" name="address"></div>
                    <div class="form-group"><label for="city">Tỉnh/Thành:</label><input type="text" id="city" name="city"></div>
                    <div class="form-group">
                        <label for="status">Trạng Thái:</label>
                        <select id="status" name="status" required>
                            <option value="Hoạt động">Hoạt động</option>
                            <option value="Ngừng">Ngừng</option>
                        </select>
                    </div>
                    <div class="form-group rank-display">
                        <label for="rank">Hạng Khách Hàng:</label>
                        <span id="display-rank" class="rank-badge">Mới</span>
                    </div>
                </div>

                <div class="form-section history-notes-section" id="history-notes-section">
                    <h3>Lịch Sử & Ghi Chú</h3>
                    <div class="form-group">
                        <label for="purchase-history">Lịch Sử Mua Hàng:</label>
                        <textarea id="purchase-history" rows="5" readonly></textarea>
                        <button type="button" class="btn btn-view-orders mt-2"><i class="fas fa-list-alt"></i> Xem Chi Tiết Đơn Hàng</button>
                    </div>
                    <div class="form-group">
                        <label for="notes">Ghi Chú:</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" id="btn-save-customer" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                    <button type="button" class="btn btn-secondary close-button"><i class="fas fa-times"></i> Hủy</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        window.customersData = <?php echo json_encode($customer_list ?? [], JSON_UNESCAPED_UNICODE); ?>;
    </script>
<?php
// Lấy nội dung đã capture
$content = ob_get_clean();

// Include template
require_once __DIR__ . '/template.php';
?>